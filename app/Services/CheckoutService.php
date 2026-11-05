<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutService
{
    public function __construct(private CasakuClient $casaku) {}

    public function place(int $userId, int $variantId, int $quantity, int $expectedPrice, string $method, string $checkoutKey): array
    {
        if (! in_array($method, ['wallet','qris'], true)) throw ValidationException::withMessages(['method' => 'Metode pembayaran tidak valid.']);
        if ($method === 'qris' && ! $this->casaku->ready()) throw ValidationException::withMessages(['method' => 'QRIS belum tersedia.']);
        if ($existing = $this->existing($userId,$checkoutKey)) return $existing;
        try {
        $result = DB::transaction(function () use ($userId, $variantId, $quantity, $expectedPrice, $method, $checkoutKey) {
            if ($existing = $this->existing($userId,$checkoutKey)) return $existing + ['existing'=>true];
            $variant = DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')
                ->where('v.id', $variantId)->where('v.active', true)->where('p.status', 'published')->whereNull('p.deleted_at')
                ->select('v.*','p.name as product_name','p.access_terms','p.refund_terms','p.usage_instructions')
                ->lockForUpdate()->first();
            if (! $variant || $variant->price_idr === null) throw ValidationException::withMessages(['product' => 'Paket belum bisa dibeli.']);
            if ((int) $variant->price_idr !== $expectedPrice) throw ValidationException::withMessages(['product' => 'Harga berubah. Periksa lagi detail paket sebelum membeli.']);
            if ($quantity < 1 || $quantity > min(10, (int) $variant->limit_per_customer)) throw ValidationException::withMessages(['quantity' => 'Jumlah melewati batas paket.']);
            $prior = DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')
                ->where('o.user_id',$userId)->where('i.variant_id',$variantId)
                ->whereIn('o.status',['waiting_payment','processing','complete','review'])->sum('i.quantity');
            if ($prior + $quantity > $variant->limit_per_customer) throw ValidationException::withMessages(['quantity'=>'Batas pembelian akun untuk paket ini telah tercapai.']);
            $units = DB::table('inventory_units')->where('variant_id', $variantId)->where('status', 'available')
                ->orderBy('id')->limit($quantity)->lockForUpdate()->pluck('id');
            if ($units->count() !== $quantity) throw ValidationException::withMessages(['quantity' => 'Stok tidak cukup.']);
            $total = $quantity * (int) $variant->price_idr;
            $number = 'ENV-'.now()->format('ymd').'-'.strtoupper(Str::random(8));
            $orderId = DB::table('orders')->insertGetId([
                'number' => $number, 'user_id' => $userId, 'idempotency_key' => $checkoutKey, 'status' => $method === 'wallet' ? 'processing' : 'waiting_payment',
                'payment_method' => $method, 'total_idr' => $total,
                'policy_version' => DB::table('content_pages')->where('slug', 'ketentuan-pembelian')->value('version'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            OrderTimeline::record($orderId,$method === 'wallet' ? 'processing' : 'waiting_payment');
            DB::table('order_items')->insert([
                'order_id' => $orderId, 'variant_id' => $variantId, 'product_name' => $variant->product_name,
                'variant_name' => $variant->name, 'quantity' => $quantity, 'unit_price_idr' => $variant->price_idr,
                'terms_snapshot' => json_encode(['access' => $variant->access_terms, 'refund' => $variant->refund_terms, 'instructions' => $variant->usage_instructions]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('inventory_units')->whereIn('id', $units)->update([
                'status' => $method === 'wallet' ? 'delivered' : 'reserved', 'order_id' => $orderId,
                'reserved_until' => $method === 'qris' ? now()->addMinutes(20) : null,
                'delivered_at' => $method === 'wallet' ? now() : null, 'updated_at' => now(),
            ]);
            if ($method === 'wallet') {
                $wallet = DB::table('wallet_accounts')->where('user_id', $userId)->lockForUpdate()->first();
                if (! $wallet || $wallet->balance_idr < $total) throw ValidationException::withMessages(['method' => 'Saldo tidak cukup.']);
                $after = $wallet->balance_idr - $total;
                DB::table('wallet_accounts')->where('id', $wallet->id)->update(['balance_idr' => $after, 'updated_at' => now()]);
                DB::table('wallet_ledger_entries')->insert([
                    'wallet_account_id' => $wallet->id, 'kind' => 'purchase', 'amount_idr' => -$total,
                    'balance_after_idr' => $after, 'reference' => 'order:'.$orderId, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('orders')->where('id', $orderId)->update(['status' => 'complete', 'paid_at' => now(), 'updated_at' => now()]);
                OrderTimeline::record($orderId,'complete','Dibayar dengan saldo dan akses dikirim.');
                return ['number' => $number, 'reference' => null];
            }
            $reference = 'QR-'.strtoupper((string) Str::ulid());
            DB::table('payment_invoices')->insert([
                'reference' => $reference, 'user_id' => $userId, 'order_id' => $orderId,
                'purpose' => 'order', 'status' => 'creating', 'base_amount_idr' => $total,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return ['number' => $number, 'reference' => $reference];
        }, 3);
        } catch (QueryException $e) {
            if ($existing = $this->existing($userId,$checkoutKey)) return $existing;
            throw $e;
        }
        if ($result['reference'] && ! ($result['existing'] ?? false)) {
            $this->activateInvoice($result['reference']);
        }
        return $result;
    }

    private function existing(int $userId, string $key): ?array
    {
        $order = DB::table('orders')->where('user_id',$userId)->where('idempotency_key',$key)->first();
        if (! $order) return null;
        return ['number'=>$order->number,'reference'=>DB::table('payment_invoices')->where('order_id',$order->id)->value('reference'),'existing'=>true];
    }

    public function createTopup(int $userId, int $amount): string
    {
        if (! $this->casaku->ready()) throw ValidationException::withMessages(['amount' => 'Top up QRIS belum tersedia.']);
        $min = config('store.topup_min'); $max = config('store.topup_max');
        if ($amount < $min || $amount > $max) throw ValidationException::withMessages(['amount' => 'Nominal di luar batas top up.']);
        $reference = 'TU-'.strtoupper((string) Str::ulid());
        DB::table('payment_invoices')->insert([
            'reference' => $reference, 'user_id' => $userId, 'purpose' => 'wallet_topup',
            'status' => 'creating', 'base_amount_idr' => $amount,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->activateInvoice($reference);
        return $reference;
    }

    private function activateInvoice(string $reference): void
    {
        $invoice = DB::table('payment_invoices')->where('reference', $reference)->first();
        try {
            $result = $this->casaku->generate((int) $invoice->base_amount_idr);
            DB::table('payment_invoices')->where('id', $invoice->id)->update([
                'status' => 'pending', 'provider_transaction_id' => $result['id'],
                'final_amount_idr' => $result['amount'], 'qr_string' => $result['qr'],
                'expires_at' => $result['expires_at'], 'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            // An ambiguous provider timeout must not create another invoice automatically.
            DB::table('payment_invoices')->where('id', $invoice->id)->update([
                'status' => 'review', 'review_reason' => 'Pembuatan QRIS ambigu: '.get_class($e), 'updated_at' => now(),
            ]);
            if ($invoice->order_id) DB::table('orders')->where('id', $invoice->order_id)->update(['status' => 'review', 'updated_at' => now()]);
            if ($invoice->order_id) OrderTimeline::record($invoice->order_id,'review','Pembuatan QRIS perlu pemeriksaan.');
            report($e);
        }
    }
}
