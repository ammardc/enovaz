<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(in_array($request->user()?->role,['owner','admin','support'],true),403);
        $recent = $request->session()->get('admin_2fa_at');
        if ($request->session()->get('admin_2fa_user') !== $request->user()->id || ! $recent || now()->timestamp - $recent > 1800) {
            return redirect()->route('admin.2fa');
        }
        return $next($request);
    }
}
