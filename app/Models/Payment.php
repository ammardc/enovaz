<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const EXPIRED = 'expired';
    public const REVIEW = 'review_required';
    public const REFUNDED = 'refunded';

    protected $fillable = ['order_id', 'gateway_transaction_id', 'base_amount', 'expected_amount', 'qr_string', 'status', 'expires_at', 'paid_at'];

    protected function casts(): array
    {
        return [
            'base_amount' => 'integer',
            'expected_amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uniqueCode(): int
    {
        return $this->expected_amount - $this->base_amount;
    }
}
