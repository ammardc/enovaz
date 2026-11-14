<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin wajib 2FA: tanpa 2FA aktif, semua halaman admin dialihkan ke halaman setel 2FA. */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && ! $user->hasTwoFactor()) {
            return redirect()->route('admin.2fa.show')->with('error', 'Aktifkan verifikasi 2 langkah dulu sebelum memakai panel admin.');
        }

        return $next($request);
    }
}
