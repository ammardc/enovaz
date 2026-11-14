<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Services\OrderService;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    /** Saldo + riwayat mutasi milik pengguna yang login saja. */
    public function index(Request $request)
    {
        $user = $request->user();
        $entries = $user->ledger()->with('order:id,code')->latest('id')->paginate(15);

        return view('balance.index', [
            'balance' => $user->fresh()->balance,
            'entries' => $entries,
            'presets' => config('enovaz.topup.presets'),
            'min' => config('enovaz.topup.min'),
            'max' => config('enovaz.topup.max'),
        ]);
    }

    public function topup(Request $request, OrderService $orders)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:'.config('enovaz.topup.min'), 'max:'.config('enovaz.topup.max')],
        ]);

        try {
            $order = $orders->topup($request->user(), (int) $data['amount']);
        } catch (CheckoutException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('payments.show', $order->code);
    }
}
