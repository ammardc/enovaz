<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Casaku\GatewayException;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), Order::LABELS) ? $request->query('status') : null;
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 32);

        $orders = Order::with(['user:id,username', 'payment', 'items.product:id,name'])
            ->when($status, fn ($w) => $w->where('status', $status))
            ->when($q !== '', fn ($w) => $w->where('code', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status', 'q'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'payment', 'items.product', 'items.deliveries']);

        return view('admin.orders.show', compact('order'));
    }

    public function resolve(Request $request, Order $order, PaymentService $payments)
    {
        $data = $request->validate([
            'action' => ['required', 'in:fulfil,refund'],
            'note' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        abort_unless($order->payment, 404);

        try {
            $data['action'] === 'fulfil'
                ? $payments->adminFulfil($order->payment, $data['note'])
                : $payments->adminRefund($order->payment, $data['note']);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        } catch (GatewayException $e) {
            report($e);

            return back()->with('error', 'Status di Casaku belum bisa diperiksa. Coba lagi.');
        }

        return back()->with('status', $data['action'] === 'fulfil'
            ? 'Pesanan dipenuhi dan akun diserahkan.'
            : 'Pesanan dibatalkan. Lakukan pengembalian dana secara manual ke pembeli.');
    }
}
