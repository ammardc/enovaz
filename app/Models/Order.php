<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const EXPIRED = 'expired';
    public const REVIEW = 'review_required';
    public const CANCELLED = 'cancelled';

    public const KIND_PURCHASE = 'purchase';
    public const KIND_TOPUP = 'topup';

    public const METHOD_QRIS = 'qris';
    public const METHOD_BALANCE = 'balance';

    public const LABELS = [
        self::PENDING => ['Menunggu pembayaran', 'pill-wait'],
        self::PAID => ['Lunas', 'pill-paid'],
        self::EXPIRED => ['Kedaluwarsa', 'pill-muted'],
        self::REVIEW => ['Perlu review', 'pill-review'],
        self::CANCELLED => ['Dibatalkan', 'pill-muted'],
    ];

    protected $fillable = ['user_id', 'code', 'kind', 'payment_method', 'total', 'status'];

    protected function casts(): array
    {
        return ['total' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function deliveries(): HasManyThrough
    {
        return $this->hasManyThrough(Delivery::class, OrderItem::class);
    }

    public function isTopup(): bool
    {
        return $this->kind === self::KIND_TOPUP;
    }

    /** Judul singkat untuk daftar pesanan. */
    public function title(): string
    {
        if ($this->isTopup()) {
            return 'Isi saldo';
        }
        $item = $this->items->first();

        return $item ? $item->product->name.' × '.$item->quantity : $this->code;
    }

    public function methodLabel(): string
    {
        return $this->payment_method === self::METHOD_BALANCE ? 'Saldo' : 'QRIS';
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->status][0] ?? $this->status;
    }

    public function statusClass(): string
    {
        return self::LABELS[$this->status][1] ?? 'pill-muted';
    }
}
