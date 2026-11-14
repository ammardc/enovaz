<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\BalanceEntry;
use App\Models\DigitalAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Casaku\CasakuGateway;
use App\Services\Casaku\GatewayException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly CasakuGateway $gateway,
        private readonly BalanceService $balance,
        private readonly PaymentService $payments,
    ) {
    }

    /**
     * Beli produk dengan saldo: satu transaksi DB untuk kunci stok, potong saldo,
     * catat ledger, dan serahkan akun. Gagal di langkah mana pun = semuanya dibatalkan.
     */
    public function checkoutWithBalance(User $user, int $productId, int $quantity): Order
    {
        $this->checkQuantity($quantity);

        return DB::transaction(function () use ($user, $productId, $quantity) {
            [$product, $accounts] = $this->lockStock($productId, $quantity);

            $order = Order::create([
                'user_id' => $user->id,
                'code' => $this->newCode(),
                'kind' => Order::KIND_PURCHASE,
                'payment_method' => Order::METHOD_BALANCE,
                'total' => $product->price * $quantity, // harga dari DB, bukan dari browser
                'status' => Order::PENDING,
            ]);

            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ]);

            // Melempar CheckoutException bila saldo kurang -> seluruh transaksi di-rollback.
            $this->balance->debit($user->id, $order->total, BalanceEntry::PURCHASE, $order, $product->name.' x '.$quantity);

            $accounts->each(fn ($a) => $a->forceFill(['reserved_order_item_id' => $item->id]));
            $this->payments->deliverAccounts($accounts);

            $order->update(['status' => Order::PAID]);
            Audit::log('order.paid_with_balance', $order, ['product_id' => $product->id, 'quantity' => $quantity]);

            return $order;
        });
    }

    /** Isi saldo: pesanan jenis topup + tagihan QRIS. Saldo baru bertambah setelah Casaku mencatat lunas. */
    public function topup(User $user, int $amount): Order
    {
        $this->ensureQrisEnabled();
        $min = (int) config('enovaz.topup.min');
        $max = (int) config('enovaz.topup.max');
        if ($amount < $min || $amount > $max) {
            throw new CheckoutException('Nominal isi saldo harus antara '.rupiah($min).' dan '.rupiah($max).'.');
        }

        $this->checkPendingLimit($user);
        $minutes = (int) config('services.casaku.expires_minutes', 15);

        $order = DB::transaction(function () use ($user, $amount) {
            $order = Order::create([
                'user_id' => $user->id,
                'code' => $this->newCode('TOP'),
                'kind' => Order::KIND_TOPUP,
                'payment_method' => Order::METHOD_QRIS,
                'total' => $amount,
                'status' => Order::PENDING,
            ]);
            Audit::log('topup.created', $order, ['amount' => $amount]);

            return $order;
        });

        $this->createInvoice($order, $minutes);

        return $order;
    }

    /**
     * Membuat pesanan, mereservasi stok, lalu membuat tagihan QRIS.
     * Harga selalu diambil dari database, tidak pernah dari browser.
     */
    public function checkout(User $user, int $productId, int $quantity): Order
    {
        $this->ensureQrisEnabled();
        $this->checkQuantity($quantity);
        $this->checkPendingLimit($user);

        $minutes = (int) config('services.casaku.expires_minutes', 15);

        // 1) Transaksi DB singkat: kunci baris stok, reservasi, catat pesanan.
        $order = DB::transaction(function () use ($user, $productId, $quantity, $minutes) {
            [$product, $accounts] = $this->lockStock($productId, $quantity);

            $order = Order::create([
                'user_id' => $user->id,
                'code' => $this->newCode(),
                'kind' => Order::KIND_PURCHASE,
                'payment_method' => Order::METHOD_QRIS,
                'total' => $product->price * $quantity,
                'status' => Order::PENDING,
            ]);

            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ]);

            DigitalAccount::whereIn('id', $accounts->pluck('id'))->update([
                'status' => DigitalAccount::RESERVED,
                'reserved_order_item_id' => $item->id,
                'reserved_until' => now()->addMinutes($minutes + 2),
            ]);

            Audit::log('order.created', $order, ['product_id' => $product->id, 'quantity' => $quantity]);

            return $order;
        });

        // 2) Panggilan jaringan ke Casaku di luar transaksi DB.
        $this->createInvoice($order, $minutes);

        return $order;
    }

    private function createInvoice(Order $order, int $minutes): void
    {
        try {
            $invoice = $this->gateway->createQris($order->total, $minutes);
        } catch (GatewayException $e) {
            report($e);
            $this->cancelBeforeInvoice($order);
            throw new CheckoutException('Tagihan QRIS gagal dibuat. Silakan coba lagi beberapa saat lagi.');
        }

        Payment::create([
            'order_id' => $order->id,
            'gateway_transaction_id' => $invoice['transaction_id'],
            'base_amount' => $order->total,
            'expected_amount' => $invoice['total_amount'],
            'qr_string' => $invoice['qr_string'],
            'status' => Payment::PENDING,
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }

    /** QRIS bisa dimatikan (maintenance). Ditolak di server, bukan hanya disembunyikan di tampilan. */
    private function ensureQrisEnabled(): void
    {
        if (! config('enovaz.qris_enabled')) {
            throw new CheckoutException('Pembayaran QRIS sedang maintenance. Gunakan saldo.');
        }
    }

    private function checkQuantity(int $quantity): void
    {
        $max = (int) config('enovaz.max_quantity');
        if ($quantity < 1 || $quantity > $max) {
            throw new CheckoutException("Jumlah harus antara 1 dan {$max}.");
        }
    }

    private function checkPendingLimit(User $user): void
    {
        $pending = $user->orders()->where('status', Order::PENDING)->count();
        if ($pending >= (int) config('enovaz.max_pending_orders')) {
            throw new CheckoutException('Kamu masih punya tagihan yang belum dibayar. Selesaikan atau tunggu sampai kedaluwarsa dulu.');
        }
    }

    /** Kunci baris produk dan akun stok (SELECT ... FOR UPDATE). Wajib di dalam transaksi. */
    private function lockStock(int $productId, int $quantity): array
    {
        $product = Product::whereKey($productId)->where('is_active', true)->lockForUpdate()->first();
        if (! $product) {
            throw new CheckoutException('Produk tidak tersedia.');
        }

        $accounts = DigitalAccount::where('product_id', $product->id)
            ->where('status', DigitalAccount::AVAILABLE)
            ->orderBy('id')
            ->limit($quantity)
            ->lockForUpdate()
            ->get();

        if ($accounts->count() < $quantity) {
            throw new CheckoutException('Stok tidak cukup. Tersisa '.$accounts->count().' akun.');
        }

        return [$product, $accounts];
    }

    private function cancelBeforeInvoice(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $itemIds = $order->items()->pluck('id');
            DigitalAccount::whereIn('reserved_order_item_id', $itemIds)
                ->where('status', DigitalAccount::RESERVED)
                ->update(['status' => DigitalAccount::AVAILABLE, 'reserved_order_item_id' => null, 'reserved_until' => null]);
            $order->update(['status' => Order::CANCELLED]);
            Audit::log('order.cancelled', $order, ['reason' => 'invoice_failed']);
        });
    }

    private function newCode(string $prefix = 'ENV'): string
    {
        do {
            $code = $prefix.'-'.now('Asia/Jakarta')->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
