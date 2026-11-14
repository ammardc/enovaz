<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit;
use App\Services\QrSvg;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Setel 2FA admin. Rute ini dilindungi konfirmasi ulang password (middleware reauth). */
class TwoFactorController extends Controller
{
    private const SETUP_KEY = 'admin.2fa_setup_secret';

    public function show(Request $request, Totp $totp)
    {
        $user = $request->user();

        if ($user->hasTwoFactor()) {
            return view('admin.two-factor', ['enabled' => true, 'user' => $user]);
        }

        // Rahasia sementara hanya di sesi sampai admin membuktikan aplikasinya menghasilkan kode yang benar.
        $secret = $request->session()->get(self::SETUP_KEY) ?: $totp->generateSecret();
        $request->session()->put(self::SETUP_KEY, $secret);

        return response()->view('admin.two-factor', [
            'enabled' => false,
            'user' => $user,
            'secret' => $secret,
            'qr' => QrSvg::render($totp->uri($secret, $user->username), 220),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Totp $totp)
    {
        $user = $request->user();
        abort_if($user->hasTwoFactor(), 409);

        $request->validate(['code' => ['required', 'string', 'max:12']], ['code.required' => 'Isi kode 6 digit dari aplikasi.']);

        $secret = (string) $request->session()->get(self::SETUP_KEY);
        $step = $secret !== '' ? $totp->match($secret, $request->input('code')) : null;

        if ($step === null) {
            Audit::log('admin.2fa_setup_failed', $user);
            throw ValidationException::withMessages(['code' => 'Kode tidak cocok. Pastikan jam HP benar, lalu coba kode terbaru.']);
        }

        $totp->enable($user, $secret, $step);
        $request->session()->forget(self::SETUP_KEY);
        Audit::log('admin.2fa_enabled', $user);

        return redirect()->route('admin.dashboard')->with('status', 'Verifikasi 2 langkah aktif. Berikutnya login akan meminta kode dari aplikasi.');
    }
}
