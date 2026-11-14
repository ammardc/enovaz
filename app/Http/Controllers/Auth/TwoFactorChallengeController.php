<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Langkah kedua login: kode 6 digit dari aplikasi authenticator. */
class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'login.2fa_pending';
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return response()->view('auth.two-factor')->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Totp $totp)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['username' => 'Sesi verifikasi berakhir. Masuk lagi.']);
        }

        $request->validate(['code' => ['required', 'string', 'max:12']], ['code.required' => 'Isi kode 6 digit.']);

        $key = '2fa:'.$user->id.'|'.$request->ip();
        if (! $totp->verifyFor($user, $request->input('code'))) {
            RateLimiter::hit($key, 300);
            Audit::log('auth.2fa_failed', $user, [], $user->id);

            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                // Terlalu banyak salah: batalkan, harus mulai lagi dari password + CAPTCHA.
                $request->session()->forget(self::SESSION_KEY);
                Audit::log('auth.2fa_locked', $user, ['attempts' => self::MAX_ATTEMPTS], $user->id);

                return redirect()->route('login')->withErrors(['username' => 'Terlalu banyak kode salah. Masuk lagi dari awal.']);
            }

            throw ValidationException::withMessages(['code' => 'Kode salah atau sudah dipakai.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget(self::SESSION_KEY);
        Auth::login($user);
        $request->session()->regenerate();
        Audit::log('auth.login', $user, ['2fa' => true]);

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || ($pending['expires'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        $user = User::find($pending['user_id'] ?? 0);

        return $user && $user->hasTwoFactor() ? $user : null;
    }
}
