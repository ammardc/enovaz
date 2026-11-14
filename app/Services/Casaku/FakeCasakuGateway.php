<?php

namespace App\Services\Casaku;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Simulasi Casaku untuk pengembangan lokal dan pengujian. Ditolak di production (lihat AppServiceProvider).
 */
class FakeCasakuGateway implements CasakuGateway
{
    public function createQris(int $baseAmount, int $expiresMinutes): array
    {
        $id = 'FAKE'.Str::upper(Str::random(12));
        $total = $baseAmount + random_int(1, 999);

        Cache::put($this->key($id), ['status' => 'pending', 'amount' => $total, 'paid_at' => null], now()->addDay());

        return [
            'transaction_id' => $id,
            'total_amount' => $total,
            'qr_string' => "SIMULASI-QRIS-ENOVAZ|{$id}|{$total}",
        ];
    }

    public function checkStatus(string $transactionId): array
    {
        $state = Cache::get($this->key($transactionId));

        return [
            'transaction_id' => $state ? $transactionId : null,
            'status' => $state['status'] ?? 'expired',
            'paid_at' => ! empty($state['paid_at']) ? Carbon::parse($state['paid_at']) : null,
        ];
    }

    public function cancel(string $transactionId): void
    {
        $this->set($transactionId, 'cancel');
    }

    /** Hanya untuk mode simulasi: menandai tagihan seolah sudah dibayar dari DANA. */
    public function markPaid(string $transactionId): void
    {
        $this->set($transactionId, 'paid', now()->toIso8601String());
    }

    private function set(string $id, string $status, ?string $paidAt = null): void
    {
        $state = Cache::get($this->key($id));
        if ($state) {
            Cache::put($this->key($id), array_merge($state, ['status' => $status, 'paid_at' => $paidAt]), now()->addDay());
        }
    }

    private function key(string $id): string
    {
        return 'fake-casaku:'.$id;
    }
}
