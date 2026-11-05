<?php

namespace App\Http\Controllers;

use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index()
    {
        $wallet = DB::table('wallet_accounts')->where('user_id', auth()->id())->first();
        return view('wallet.index', compact('wallet'));
    }

    public function transactions()
    {
        $wallet = DB::table('wallet_accounts')->where('user_id', auth()->id())->first();
        $ledger = DB::table('wallet_ledger_entries')
            ->where('wallet_account_id', $wallet?->id ?? 0)
            ->orderByDesc('id')
            ->paginate(20);

        return view('wallet.transactions', compact('ledger'));
    }

    public function topups()
    {
        $topups = DB::table('payment_invoices')
            ->where('user_id', auth()->id())
            ->where('purpose', 'wallet_topup')
            ->orderByDesc('id')
            ->paginate(20);

        return view('wallet.topups', compact('topups'));
    }

    public function topupForm()
    {
        $qrisReady = app(\App\Services\CasakuClient::class)->ready();
        return view('wallet.topup',compact('qrisReady'));
    }

    public function topup(Request $request, CheckoutService $service)
    {
        $data = $request->validate(['amount' => ['required','integer','min:1']]);
        $reference = $service->createTopup(auth()->id(), (int) $data['amount']);
        return redirect()->route('payments.show', $reference);
    }
}
