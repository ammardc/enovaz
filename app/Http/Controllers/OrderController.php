<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Services\Audit;
use App\Services\CredentialVault;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with(['items.product', 'payment'])
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, string $code)
    {
        $order = $this->ownedOrder($request, $code)->load(['items.product', 'payment']);

        if ($order->status === Order::PENDING) {
            return redirect()->route('payments.show', $order->code);
        }

        return response()->view('orders.show', ['order' => $order, 'credentials' => null])
            ->header('Cache-Control', 'no-store, private');
    }

    /** Membuka kredensial: hanya pemilik pesanan, hanya setelah pembayaran terverifikasi. */
    public function reveal(Request $request, string $code, CredentialVault $vault)
    {
        $order = $this->ownedOrder($request, $code)->load(['items.product', 'payment']);
        abort_unless($order->status === Order::PAID && ! $order->isTopup(), 403);

        $credentials = Delivery::whereIn('order_item_id', $order->items->pluck('id'))
            ->with('digitalAccount')
            ->orderBy('id')
            ->get()
            ->map(function (Delivery $d) use ($vault, $order) {
                $acc = $d->digitalAccount;

                return [
                    'email' => $vault->decrypt($acc->email_enc, CredentialVault::aad($acc->product_id, 'email')),
                    'password' => $vault->decrypt($acc->password_enc, CredentialVault::aad($acc->product_id, 'password')),
                    'product' => optional($order->items->firstWhere('id', $d->order_item_id))->product?->name,
                ];
            });

        Audit::log('credential.viewed', $order, ['count' => $credentials->count()]);

        return response()->view('orders.show', compact('order', 'credentials'))
            ->header('Cache-Control', 'no-store, private');
    }
}
