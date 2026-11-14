<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\BalanceEntry;
use App\Models\Delivery;
use App\Models\DigitalAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Casaku\CasakuGateway;
use App\Services\Casaku\GatewayException;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly CasakuGateway $gateway,
        private readonly BalanceService $balance,
    ) {
    }

    /**
     * Memeriksa status tagihan ke Casaku dan, bila sah, menyerahkan akun satu kali.
     * Status dari browser tidak pernah dipercaya. Aman dipanggil berulang (idempoten).
     *
     * @return string paid|pending|expired|review_required|refunded
     */
    public function verify(Payment $payment, string $source): string
    {
        $payment->refresh();

        if ($payment->status === Payment::PAID) {
            return Payment::PAID;
        }
        if (! in_array($payment->status, [Payment::PENDING, Payment::EXPIRED], true)) {
            return $payment->status;
        }

        $remote = $this->gateway->checkStatus($payment->gateway_transaction_id); // GatewayException diteruskan

        if ($remote['transaction_id'] !== null && $remote['transaction_id'] !== $payment->gateway_transaction_id) {
            $this->flagReview($payment, 'transaction_id_mismatch');

            return Payment::REVIEW;
        }

        if ($remote['status'] !== 'paid') {
            if (in_array($remote['status'], ['expired', 'cancel'], true) && $payment->status === Payment::PENDING) {
                $this->release($payment, 'remote_'.$remote['status']);

                return Payment::EXPIRED;
            }

            return $payment->status === Payment::EXPIRED ? Payment::EXPIRED : Payment::PENDING;
        }

        return $this->fulfil($payment, $remote['paid_at'], $source);
    }

    private function fulfil(Payment $payment, ?CarbonInterface $paidAt, string $source): string
    {
        return DB::transaction(function () use ($payment, $paidAt, $source) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($p->status === Payment::PAID) {
                return Payment::PAID; // pemeriksaan berulang tidak membuat penyerahan baru
            }

            $order = Order::whereKey($p->order_id)->lockForUpdate()->firstOrFail();

            if ($order->isTopup()) {
                // Tidak ada stok yang dijaga, jadi bayar terlambat (expired) tetap dikreditkan.
                $this->creditTopup($p, $order, $paidAt, $source);

                return Payment::PAID;
            }

            $items = $order->items()->get();
            $accounts = DigitalAccount::whereIn('reserved_order_item_id', $items->pluck('id'))
                ->where('status', DigitalAccount::RESERVED)
                ->lockForUpdate()
                ->get();

            $intact = $p->status === Payment::PENDING
                && $items->every(function ($item) use ($accounts) {
                    $mine = $accounts->where('reserved_order_item_id', $item->id);

                    return $mine->count() === (int) $item->quantity
                        && $mine->every(fn ($a) => (int) $a->product_id === (int) $item->product_id);
                });

            if (! $intact) {
                // Pembayaran terlambat atau reservasi tidak sesuai: admin yang memutuskan.
                $p->update(['status' => Payment::REVIEW, 'paid_at' => $paidAt ?? now()]);
                $order->update(['status' => Order::REVIEW]);
                Audit::log('payment.review_required', $p, ['reason' => 'late_or_reservation_mismatch', 'source' => $source]);

                return Payment::REVIEW;
            }

            $this->deliverAccounts($accounts);

            $p->update(['status' => Payment::PAID, 'paid_at' => $paidAt ?? now()]);
            $order->update(['status' => Order::PAID]);
            Audit::log('payment.verified', $p, ['source' => $source, 'accounts' => $accounts->count()]);

            return Payment::PAID;
        });
    }

    /** Melepas reservasi tagihan yang dipastikan belum dibayar. */
    public function release(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($p->status !== Payment::PENDING) {
                return;
            }

            $order = Order::whereKey($p->order_id)->lockForUpdate()->firstOrFail();
            $this->releaseAccounts($order);
            $p->update(['status' => Payment::EXPIRED]);
            $order->update(['status' => Order::EXPIRED]);
            Audit::log('payment.expired', $p, ['reason' => $reason], null);
        });
    }

    public function flagReview(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (in_array($p->status, [Payment::PAID, Payment::REFUNDED], true)) {
                return;
            }
            $p->update(['status' => Payment::REVIEW]);
            Order::whereKey($p->order_id)->update(['status' => Order::REVIEW]);
            Audit::log('payment.review_required', $p, ['reason' => $reason], null);
        });
    }

    /** Dipanggil scheduler tiap menit. */
    public function expireOverdue(): int
    {
        $released = 0;

        Payment::where('status', Payment::PENDING)
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (Payment $p) use (&$released) {
                try {
                    $remote = $this->gateway->checkStatus($p->gateway_transaction_id);

                    if ($remote['status'] === 'paid') {
                        $this->fulfil($p, $remote['paid_at'], 'scheduler');

                        return;
                    }

                    if ($remote['status'] === 'pending') {
                        if ($p->expires_at->gt(now()->subMinutes(5))) {
                            return; // beri jeda, notifikasi DANA bisa terlambat
                        }
                        $this->gateway->cancel($p->gateway_transaction_id);
                    }
                } catch (GatewayException $e) {
                    report($e);

                    return; // jangan lepas stok bila status belum bisa dipastikan
                }

                $this->release($p, 'expired');
                $released++;
            });

        return $released;
    }

    /** Admin: penuhi pesanan bermasalah. Tetap butuh bukti status paid dari Casaku. */
    public function adminFulfil(Payment $payment, string $note): void
    {
        $remote = $this->gateway->checkStatus($payment->gateway_transaction_id);
        if ($remote['status'] !== 'paid') {
            throw new CheckoutException('Casaku tidak mencatat tagihan ini sebagai lunas. Pesanan tidak boleh dipenuhi.');
        }

        DB::transaction(function () use ($payment, $note, $remote) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($p->status !== Payment::REVIEW) {
                throw new CheckoutException('Pembayaran ini tidak sedang menunggu review.');
            }

            $order = Order::whereKey($p->order_id)->lockForUpdate()->firstOrFail();

            if ($order->isTopup()) {
                $this->creditTopup($p, $order, $p->paid_at ?? $remote['paid_at'], 'admin');
                Audit::log('payment.review_fulfilled', $p, ['note' => mb_substr($note, 0, 500)]);

                return;
            }

            $toDeliver = collect();

            foreach ($order->items()->get() as $item) {
                $reserved = DigitalAccount::where('reserved_order_item_id', $item->id)
                    ->where('status', DigitalAccount::RESERVED)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()->get();

                $missing = $item->quantity - $reserved->count();
                $extra = $missing > 0
                    ? DigitalAccount::where('product_id', $item->product_id)->where('status', DigitalAccount::AVAILABLE)
                        ->orderBy('id')->limit($missing)->lockForUpdate()->get()
                    : collect();

                if ($reserved->count() + $extra->count() < $item->quantity) {
                    throw new CheckoutException('Stok tidak cukup untuk memenuhi pesanan. Tambah stok dulu.');
                }

                $extra->each(fn ($a) => $a->forceFill(['reserved_order_item_id' => $item->id]));
                $toDeliver = $toDeliver->merge($reserved->take($item->quantity))->merge($extra);
            }

            $this->deliverAccounts($toDeliver);
            $p->update(['status' => Payment::PAID, 'paid_at' => $p->paid_at ?? $remote['paid_at'] ?? now()]);
            $order->update(['status' => Order::PAID]);
            Audit::log('payment.review_fulfilled', $p, ['note' => mb_substr($note, 0, 500)]);
        });
    }

    /** Admin: batalkan pesanan bermasalah. Pengembalian dana dilakukan manual di luar aplikasi. */
    public function adminRefund(Payment $payment, string $note): void
    {
        DB::transaction(function () use ($payment, $note) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($p->status !== Payment::REVIEW) {
                throw new CheckoutException('Pembayaran ini tidak sedang menunggu review.');
            }
            $order = Order::whereKey($p->order_id)->lockForUpdate()->firstOrFail();
            $this->releaseAccounts($order);
            $p->update(['status' => Payment::REFUNDED]);
            $order->update(['status' => Order::CANCELLED]);
            Audit::log('payment.review_refunded', $p, ['note' => mb_substr($note, 0, 500)]);
        });
    }

    /**
     * Kredit saldo dari top-up yang lunas. Nominal = yang benar-benar dibayar (termasuk kode unik).
     * Dipanggil di dalam transaksi dengan baris payment & order sudah terkunci.
     */
    private function creditTopup(Payment $p, Order $order, ?CarbonInterface $paidAt, string $source): void
    {
        $this->balance->credit($order->user_id, $p->expected_amount, BalanceEntry::TOPUP, $order, 'Isi saldo via QRIS');
        $p->update(['status' => Payment::PAID, 'paid_at' => $paidAt ?? now()]);
        $order->update(['status' => Order::PAID]);
        Audit::log('payment.verified', $p, ['source' => $source, 'topup' => $p->expected_amount]);
    }

    /** Tandai akun terjual dan catat penyerahan. Wajib di dalam transaksi, akun sudah terkunci. */
    public function deliverAccounts($accounts): void
    {
        foreach ($accounts as $account) {
            $account->forceFill(['status' => DigitalAccount::SOLD, 'reserved_until' => null])->save();
            Delivery::create([
                'order_item_id' => $account->reserved_order_item_id,
                'digital_account_id' => $account->id, // UNIQUE: satu akun hanya bisa diserahkan sekali
                'delivered_at' => now(),
            ]);
        }
    }

    private function releaseAccounts(Order $order): void
    {
        DigitalAccount::whereIn('reserved_order_item_id', $order->items()->pluck('id'))
            ->where('status', DigitalAccount::RESERVED)
            ->update(['status' => DigitalAccount::AVAILABLE, 'reserved_order_item_id' => null, 'reserved_until' => null]);
    }
}
