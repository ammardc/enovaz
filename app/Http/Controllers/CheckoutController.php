<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(Request $request, string $slug, OrderService $orders)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('enovaz.max_quantity')],
            'method' => ['nullable', 'in:'.Order::METHOD_QRIS.','.Order::METHOD_BALANCE],
        ]);

        $product = Product::active()->where('slug', $slug)->firstOrFail();
        $byBalance = ($data['method'] ?? Order::METHOD_QRIS) === Order::METHOD_BALANCE;

        try {
            $order = $byBalance
                ? $orders->checkoutWithBalance($request->user(), $product->id, (int) $data['quantity'])
                : $orders->checkout($request->user(), $product->id, (int) $data['quantity']);
        } catch (CheckoutException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return $byBalance
            ? redirect()->route('orders.show', $order->code)->with('status', 'Dibayar dengan saldo. Akunmu sudah siap.')
            : redirect()->route('payments.show', $order->code);
    }
}
