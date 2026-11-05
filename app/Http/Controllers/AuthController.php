<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function loginForm() { return view('auth.login'); }
    public function registerForm() { return view('auth.register'); }
    public function forgotForm() { return view('auth.forgot'); }
    public function resetForm(Request $request, string $token) { return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]); }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => ['required','string'], 'password' => ['required','string']]);
        if (! Auth::attempt($data, $request->boolean('remember')) || Auth::user()->blocked) {
            Auth::logout();
            return back()->withErrors(['username' => 'Username atau kata sandi tidak cocok, atau akun diblokir.'])->onlyInput('username');
        }
        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();
        return redirect()->intended(route('shop'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'username' => ['required','string','max:50','unique:users,username','alpha_dash'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed',PasswordRule::min(10)->letters()->numbers()],
        ]);
        $user = User::create($data);
        DB::table('wallet_accounts')->insert(['user_id' => $user->id, 'balance_idr' => 0, 'created_at' => now(), 'updated_at' => now()]);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('shop');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('shop');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required','email']]);
        Password::sendResetLink($request->only('email'));
        return back()->with('success', 'Jika email terdaftar, tautan reset akan dikirim.');
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'], 'email' => ['required','email'],
            'password' => ['required','confirmed',PasswordRule::min(10)->letters()->numbers()],
        ]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });
        return $status === Password::PasswordReset ? redirect()->route('login')->with('success', 'Kata sandi berhasil diubah.') : back()->withErrors(['email' => __($status)]);
    }

    public function profile()
    {
        return view('auth.profile', ['social' => DB::table('social_accounts')->where('user_id', auth()->id())->pluck('provider')]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required','confirmed',PasswordRule::min(10)->letters()->numbers()],
        ]);
        $request->user()->forceFill(['password' => Hash::make($request->password)])->save();
        return back()->with('success', 'Kata sandi diperbarui.');
    }

    public function googleRedirect()
    {
        abort_unless(config('services.google.client_id') && config('services.google.client_secret'), 503, 'Login Google belum dikonfigurasi.');
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request)
    {
        $google = Socialite::driver('google')->user();
        $providerId = (string) $google->getId();
        $email = $google->getEmail();
        abort_unless($providerId && $email, 422, 'Google tidak mengembalikan email yang dapat dipakai.');
        $linked = DB::table('social_accounts')->where('provider', 'google')->where('provider_id', $providerId)->first();
        if ($linked) {
            $user = User::findOrFail($linked->user_id);
        } elseif (Auth::check()) {
            $user = Auth::user();
            abort_unless(strcasecmp($user->email, $email) === 0, 403, 'Email yang sama diperlukan untuk menghubungkan Google.');
            DB::table('social_accounts')->insert(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => $providerId, 'created_at' => now(), 'updated_at' => now()]);
            return redirect()->route('profile')->with('success', 'Akun Google terhubung.');
        } else {
            if (User::where('email', $email)->exists()) {
                return redirect()->route('login')->withErrors(['username' => 'Email sudah terdaftar. Masuk dengan username dan kata sandi.']);
            }
            $user = User::create(['name' => $google->getName() ?: 'Pelanggan', 'username' => 'google_' . Str::random(10), 'email' => $email, 'password' => Str::random(64)]);
            DB::table('social_accounts')->insert(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => $providerId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('wallet_accounts')->insert(['user_id' => $user->id, 'balance_idr' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        abort_if($user->blocked, 403, 'Akun diblokir.');
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended(route('shop'));
    }
}
