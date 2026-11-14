<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Satu baris mutasi saldo. Append-only: tidak pernah diubah atau dihapus.
 * Hanya dibuat lewat App\Services\BalanceService.
 */
class BalanceEntry extends Model
{
    public const TOPUP = 'topup';
    public const PURCHASE = 'purchase';
    public const REFUND = 'refund';
    public const ADJUSTMENT = 'adjustment';

    public const LABELS = [
        self::TOPUP => 'Isi saldo',
        self::PURCHASE => 'Pembelian',
        self::REFUND => 'Pengembalian',
        self::ADJUSTMENT => 'Isi saldo (admin)',
    ];

    protected $table = 'balance_ledger';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'order_id', 'type', 'amount', 'balance_after', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Lapis aplikasi; lapis database: akun runtime tanpa hak UPDATE/DELETE (grants.sql).
        static::updating(fn () => throw new LogicException('Mutasi saldo tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Mutasi saldo tidak boleh dihapus.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }
}
