<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use App\Services\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request, Captcha $captcha)
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);

        // CAPTCHA dulu: tanpa itu bot bisa menebak username terdaftar dari pesan "sudah dipakai".
        $request->validate(['captcha' => ['required', 'string', 'max:10']], ['captcha.required' => 'Isi kode verifikasi.']);
        if (! $captcha->check($request->input('captcha'))) {
            throw ValidationException::withMessages(['captcha' => 'Kode verifikasi salah atau kedaluwarsa.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9._]+$/', 'unique:users,username'],
            'password' => ['required', 'string', 'max:128', 'confirmed', Password::defaults()],
        ]);

        $user = new User;
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->password_hash = $data['password']; // di-hash Argon2id oleh cast "hashed"
        $user->role = User::ROLE_PEMBELI;          // input "role" apa pun diabaikan
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();
        Audit::log('auth.registered', $user);

        return redirect()->route('home')->with('status', 'Akun berhasil dibuat. Selamat berbelanja!');
    }
}
