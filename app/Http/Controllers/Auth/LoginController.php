<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use App\Services\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, Captcha $captcha)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:128'],
            'captcha' => ['required', 'string', 'max:10'],
        ], ['captcha.required' => 'Isi kode verifikasi.']);

        $username = strtolower(trim($data['username']));
        $key = 'login:'.sha1($username).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        // CAPTCHA dicek sebelum password: bot tidak bisa menguji password tanpa menyelesaikannya.
        if (! $captcha->check($data['captcha'])) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            Audit::log('auth.captcha_failed', null, ['username' => mb_substr($username, 0, 32)]);
            throw ValidationException::withMessages(['captcha' => 'Kode verifikasi salah atau kedaluwarsa.']);
        }

        // validate() memeriksa password tanpa langsung login: akun ber-2FA masih harus lolos langkah kedua.
        if (! Auth::validate(['username' => $username, 'password' => $data['password']])) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                $target = User::where('username', $username)->first();
                Audit::log('auth.login_locked', $target, ['attempts' => self::MAX_ATTEMPTS]);
            }

            // Pesan sama untuk username salah maupun password salah (tidak membocorkan akun yang ada).
            throw ValidationException::withMessages(['username' => 'Username atau password salah.']);
        }

        RateLimiter::clear($key);
        $user = User::where('username', $username)->firstOrFail();
        $request->session()->regenerate(); // cegah session fixation

        if ($user->hasTwoFactor()) {
            // Belum login: simpan "menunggu 2FA" (berlaku 5 menit), lalu minta kode authenticator.
            $request->session()->put(TwoFactorChallengeController::SESSION_KEY, [
                'user_id' => $user->id,
                'expires' => now()->addMinutes(5)->getTimestamp(),
            ]);
            Audit::log('auth.password_ok_2fa_pending', $user, [], $user->id);

            return redirect()->route('login.2fa');
        }

        Auth::login($user);
        Audit::log('auth.login', $user);

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    public function destroy(Request $request)
    {
        Audit::log('auth.logout', $request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Kamu sudah keluar.');
    }
}
