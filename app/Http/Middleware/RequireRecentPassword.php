<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tindakan admin sensitif (ubah role, tangani transaksi) butuh autentikasi ulang. */
class RequireRecentPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $at = (int) $request->session()->get('auth.reauth_at', 0);

        if (time() - $at <= (int) config('enovaz.reauth_seconds')) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            return redirect()->guest(route('password.confirm'));
        }

        $request->session()->put('url.intended', url()->previous());

        return redirect()->route('password.confirm')
            ->with('status', 'Konfirmasi password dulu, lalu ulangi tindakanmu.');
    }
}
