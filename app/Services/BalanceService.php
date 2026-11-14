<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\BalanceEntry;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Satu-satunya jalan untuk mengubah saldo.
 * Setiap perubahan: kunci baris user (SELECT ... FOR UPDATE), cek, ubah users.balance,
 * lalu tulis satu baris ledger. Semua di dalam satu transaksi.
 */
class BalanceService
{
    /** Tambah saldo. Wajib dipanggil di dalam transaksi DB. */
    public function credit(int $userId, int $amount, string $type, ?Order $order = null, ?string $note = null): BalanceEntry
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal kredit harus positif.');
        }

        return $this->apply($userId, $amount, $type, $order, $note);
    }

    /** Kurangi saldo. Gagal bila saldo kurang. Wajib dipanggil di dalam transaksi DB. */
    public function debit(int $userId, int $amount, string $type, ?Order $order = null, ?string $note = null): BalanceEntry
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal debit harus positif.');
        }

        return $this->apply($userId, -$amount, $type, $order, $note);
    }

    private function apply(int $userId, int $delta, string $type, ?Order $order, ?string $note): BalanceEntry
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Perubahan saldo harus di dalam transaksi DB.');
        }

        $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
        $after = $user->balance + $delta;

        if ($after < 0) {
            throw new CheckoutException('Saldo tidak cukup. Saldo kamu '.rupiah($user->balance).'.');
        }

        // Kunci unik (order_id, type) di DB menolak kredit/debit ganda untuk pesanan yang sama.
        $entry = BalanceEntry::create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'type' => $type,
            'amount' => $delta,
            'balance_after' => $after,
            'note' => $note ? mb_substr($note, 0, 255) : null,
        ]);

        $user->forceFill(['balance' => $after])->save();

        Audit::log('balance.'.$type, $user, ['amount' => $delta, 'balance_after' => $after, 'order' => $order?->code]);

        return $entry;
    }
}
