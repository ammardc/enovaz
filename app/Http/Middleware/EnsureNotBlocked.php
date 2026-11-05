<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureNotBlocked
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->blocked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email'=>'Akun diblokir. Hubungi bantuan.']);
        }
        return $next($request);
    }
}
