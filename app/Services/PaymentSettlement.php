<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentSettlement
{
    public function paid(string $transactionId, int $amount, ?string $paidAt, string $eventHash): string
    {
        return DB::transaction(function () use ($transactionId, $amount, $paidAt, $eventHash) {
            $invoice = DB::table('payment_invoices')->where('provider_transaction_id', $transactionId)->lockForUpdate()->first();
            if (DB::table('payment_events')->where('event_hash', $eventHash)->exists()) return 'duplicate';
            $event = ['invoice_id' => $invoice?->id, 'event_hash' => $eventHash,
                'provider_transaction_id' => $transactionId, 'status' => 'paid', 'amount_idr' => $amount,
                'outcome' => 'unmatched', 'created_at' => now(), 'updated_at' => now()];
            $eventId = DB::table('payment_events')->insertGetId($event);
            if (! $invoice) return 'unmatched';
            if ($invoice->status === 'paid') {
                DB::table('payment_events')->where('id', $eventId)->update(['outcome' => 'duplicate']);
                return 'duplicate';
            }
            if ($invoice->final_amount_idr === null || (int) $invoice->final_amount_idr !== $amount) {
                $this->review($invoice, 'Nominal pembayaran tidak cocok.');
                DB::table('payment_events')->where('id', $eventId)->update(['outcome' => 'amount_mismatch']);
                return 'review';
            }
            try { $paidTime = $paidAt ? Carbon::parse($paidAt) : now(); }
            catch (\Throwable) { $paidTime = now(); }
            if ($invoice->purpose === 'order' && $invoice->expires_at && $paidTime->greaterThan(Carbon::parse($invoice->expires_at))) {
                $this->review($invoice, 'Pembayaran terdeteksi setelah batas waktu.');
                DB::table('payment_events')->where('id', $eventId)->update(['outcome' => 'late_payment']);
                return 'review';
            }
            if ($invoice->purpose === 'wallet_topup') {
                $wallet = DB::table('wallet_accounts')->where('user_id', $invoice->user_id)->lockForUpdate()->first();
                if (! $wallet) {
                    $this->review($invoice, 'Akun saldo tidak ditemukan.');
                    return 'review';
                }
                $reference = 'topup:'.$invoice->id;
                if (! DB::table('wallet_ledger_entries')->where('reference', $reference)->exists()) {
                    $after = (int) $wallet->balance_idr + (int) $invoice->base_amount_idr;
                    DB::table('wallet_accounts')->where('id', $wallet->id)->update(['balance_idr' => $after, 'updated_at' => now()]);
                    DB::table('wallet_ledger_entries')->insert([
                        'wallet_account_id' => $wallet->id, 'kind' => 'topup',
                        'amount_idr' => $invoice->base_amount_idr, 'balance_after_idr' => $after,
                        'reference' => $reference, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            } elseif ($invoice->purpose === 'order' && $invoice->order_id) {
                $order = DB::table('orders')->where('id', $invoice->order_id)->lockForUpdate()->first();
                $expected = DB::table('order_items')->where('order_id', $order->id)->sum('quantity');
                $units = DB::table('inventory_units')->where('order_id', $order->id)->lockForUpdate()->get();
                if ($units->count() !== $expected || $units->contains(fn ($unit) => $unit->status !== 'reserved')) {
                    $this->review($invoice, 'Reservasi stok tidak utuh; pembayaran perlu pemeriksaan.');
                    DB::table('payment_events')->where('id', $eventId)->update(['outcome' => 'stock_mismatch']);
                    return 'review';
                }
                DB::table('inventory_units')->where('order_id', $order->id)->update([
                    'status' => 'delivered', 'reserved_until' => null, 'delivered_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('orders')->where('id', $order->id)->update(['status' => 'complete', 'paid_at' => $paidTime, 'updated_at' => now()]);
                OrderTimeline::record($order->id,'complete','Pembayaran QRIS terverifikasi dan akses dikirim.');
            } else {
                $this->review($invoice, 'Jenis invoice tidak dikenal.');
                return 'review';
            }
            DB::table('payment_invoices')->where('id', $invoice->id)->update(['status' => 'paid', 'paid_at' => $paidTime, 'updated_at' => now()]);
            DB::table('payment_events')->where('id', $eventId)->update(['outcome' => 'applied']);
            return 'applied';
        }, 3);
    }

    public function expired(string $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $invoice = DB::table('payment_invoices')->where('provider_transaction_id', $transactionId)->lockForUpdate()->first();
            if (! $invoice || ! in_array($invoice->status, ['pending','creating'], true)) return;
            DB::table('payment_invoices')->where('id', $invoice->id)->update(['status' => 'expired', 'updated_at' => now()]);
            if ($invoice->order_id) {
                DB::table('inventory_units')->where('order_id', $invoice->order_id)->where('status', 'reserved')->update([
                    'status' => 'available', 'order_id' => null, 'reserved_until' => null, 'updated_at' => now(),
                ]);
                $changed = DB::table('orders')->where('id', $invoice->order_id)->where('status', 'waiting_payment')->update(['status' => 'expired', 'updated_at' => now()]);
                if ($changed) OrderTimeline::record($invoice->order_id,'expired','Provider mengonfirmasi tagihan kedaluwarsa.');
            }
        }, 3);
    }

    private function review(object $invoice, string $reason): void
    {
        DB::table('payment_invoices')->where('id', $invoice->id)->update(['status' => 'review', 'review_reason' => $reason, 'updated_at' => now()]);
        if ($invoice->order_id) {
            DB::table('orders')->where('id', $invoice->order_id)->update(['status' => 'review', 'updated_at' => now()]);
            OrderTimeline::record($invoice->order_id,'review',$reason);
        }
    }
}
