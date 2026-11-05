<?php

namespace App\Http\Controllers;

use App\Services\CheckoutService;
use App\Services\InventoryCipher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = DB::table('orders as o')->join('order_items as i', 'i.order_id', '=', 'o.id')
            ->where('o.user_id', auth()->id())->select('o.*','i.product_name','i.variant_name','i.quantity')
            ->orderByDesc('o.id')->paginate(15);
        return view('orders.index', compact('orders'));
    }

    public function show(string $number)
    {
        $order = DB::table('orders')->where('number', $number)->where('user_id', auth()->id())->first();
        abort_unless($order, 404);
        $item = DB::table('order_items')->where('order_id', $order->id)->first();
        $invoice = DB::table('payment_invoices')->where('order_id', $order->id)->first();
        $timeline = DB::table('order_status_events')->where('order_id',$order->id)->orderBy('id')->get();
        return view('orders.show', compact('order','item','invoice','timeline'));
    }

    public function reveal(string $number, InventoryCipher $cipher)
    {
        $order = DB::table('orders')->where('number', $number)->where('user_id', auth()->id())->whereIn('status', ['complete','refund_processing'])->first();
        abort_unless($order, 404);
        $units = DB::table('inventory_units')->where('order_id', $order->id)->where('status', 'delivered')->get();
        $access = $units->map(fn ($unit) => $cipher->decrypt($unit->ciphertext));
        DB::table('admin_audit_logs')->insert(['admin_id' => null, 'action' => 'customer_reveal', 'subject_type' => 'order',
            'subject_id' => $order->id, 'context' => json_encode(['user_id' => auth()->id()]), 'created_at' => now()]);
        $item = DB::table('order_items')->where('order_id', $order->id)->first();
        $invoice = DB::table('payment_invoices')->where('order_id', $order->id)->first();
        $timeline = DB::table('order_status_events')->where('order_id',$order->id)->orderBy('id')->get();
        return view('orders.show', compact('order','item','invoice','access','timeline'));
    }

    public function checkout(int $variant)
    {
        $package = $this->package($variant);
        abort_unless($package, 404);
        $balance = (int) DB::table('wallet_accounts')->where('user_id', auth()->id())->value('balance_idr');
        $checkoutKey = (string) \Illuminate\Support\Str::uuid();
        $qrisReady = app(\App\Services\CasakuClient::class)->ready();
        return view('orders.checkout', compact('package','balance','checkoutKey','qrisReady'));
    }

    public function place(Request $request, int $variant, CheckoutService $service)
    {
        $data = $request->validate([
            'quantity' => ['required','integer','min:1','max:10'],
            'expected_price' => ['required','integer','min:0'],
            'method' => ['required','in:wallet,qris'],
            'checkout_key' => ['required','uuid'],
        ]);
        $result = $service->place(auth()->id(), $variant, $data['quantity'], $data['expected_price'], $data['method'], $data['checkout_key']);
        return $result['reference'] ? redirect()->route('payments.show', $result['reference']) : redirect()->route('orders.show', $result['number']);
    }

    private function package(int $id): ?object
    {
        return DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.id', $id)->where('v.active', true)->where('p.status', 'published')->whereNull('p.deleted_at')
            ->select('v.*','p.name as product_name','p.slug as product_slug','p.access_terms','p.refund_terms')
            ->selectSub(function ($q) {
                $q->from('inventory_units')->selectRaw('COUNT(*)')->whereColumn('variant_id', 'v.id')->where('status', 'available');
            }, 'stock_count')->first();
    }
}
