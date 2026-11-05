<?php

namespace App\Http\Controllers;

use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class AdminTwoFactorController extends Controller
{
    public function form(Request $request, Totp $totp)
    {
        abort_unless(in_array($request->user()->role,['owner','admin','support'],true),403);
        $setup = ! $request->user()->admin_totp_secret;
        if ($setup && ! $request->session()->has('admin_totp_setup')) $request->session()->put('admin_totp_setup',$totp->secret());
        $secret = $setup ? $request->session()->get('admin_totp_setup') : null;
        $uri = $setup ? 'otpauth://totp/'.rawurlencode('E-Novaz:'.$request->user()->email).'?secret='.$secret.'&issuer=E-Novaz' : null;
        return view('admin.2fa',compact('setup','secret','uri'));
    }

    public function verify(Request $request, Totp $totp)
    {
        $data = $request->validate(['code'=>['required','digits:6']]);
        $user = $request->user();
        abort_unless(in_array($user->role,['owner','admin','support'],true),403);
        if (! $user->admin_totp_secret) {
            $secret = $request->session()->get('admin_totp_setup');
            abort_unless(is_string($secret),422);
            $step = $totp->verify($secret,$data['code']);
            if ($step === null) return back()->withErrors(['code'=>'Kode tidak cocok.']);
            DB::table('users')->where('id',$user->id)->update([
                'admin_totp_secret'=>Crypt::encryptString($secret),'admin_totp_last_step'=>$step,'updated_at'=>now(),
            ]);
            $request->session()->forget('admin_totp_setup');
        } else {
            $step = $totp->verify(Crypt::decryptString($user->admin_totp_secret),$data['code'],$user->admin_totp_last_step);
            if ($step === null) return back()->withErrors(['code'=>'Kode tidak cocok atau sudah digunakan.']);
            DB::table('users')->where('id',$user->id)->update(['admin_totp_last_step'=>$step,'updated_at'=>now()]);
        }
        $request->session()->regenerate();
        $request->session()->put(['admin_2fa_user'=>$user->id,'admin_2fa_at'=>now()->timestamp]);
        return redirect()->intended(route('admin.index'));
    }
}
