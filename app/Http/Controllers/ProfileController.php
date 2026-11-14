<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Profil pengguna yang sedang login. Tidak ada ID di URL, jadi tidak bisa membuka profil orang lain. */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->loadCount('orders');

        return response()->view('profile.show', compact('user'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function updateName(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $user = $request->user();
        $user->name = $data['name']; // hanya nama; username, role, saldo tidak bisa diubah dari sini
        $user->save();

        Audit::log('profile.name_changed', $user);

        return back()->with('status', 'Nama diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'max:128', 'confirmed', $request->user()->passwordRule(), 'different:current_password'],
        ], [
            'password.different' => 'Password baru harus berbeda dari yang lama.',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password_hash)) {
            Audit::log('profile.password_change_failed', $user);
            throw ValidationException::withMessages(['current_password' => 'Password lama salah.']);
        }

        $user->password_hash = $data['password']; // di-hash Argon2id oleh cast "hashed"
        $user->save();

        // Sesi ini tetap masuk; sesi lain (perangkat lain) keluar otomatis lewat AuthenticateSession.
        $request->session()->regenerate();
        $request->session()->put('password_hash_'.Auth::getDefaultDriver(), $user->getAuthPassword());

        Audit::log('profile.password_changed', $user);

        return back()->with('status', 'Password diganti. Perangkat lain otomatis keluar.');
    }
}
