<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConfirmPasswordController extends Controller
{
    public function show()
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request)
    {
        $request->validate(['password' => ['required', 'string', 'max:128']]);

        if (! Hash::check($request->input('password'), $request->user()->password_hash)) {
            Audit::log('auth.reauth_failed', $request->user());
            throw ValidationException::withMessages(['password' => 'Password salah.']);
        }

        $request->session()->put('auth.reauth_at', time());
        Audit::log('auth.reauth_success', $request->user());

        return redirect()->intended($request->user()->isAdmin() ? route('admin.dashboard') : route('home'));
    }
}
