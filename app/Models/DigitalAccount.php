<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAccount extends Model
{
    public const AVAILABLE = 'available';
    public const RESERVED = 'reserved';
    public const SOLD = 'sold';
    public const DISABLED = 'disabled';

    protected $fillable = ['product_id', 'email_enc', 'password_enc', 'status', 'reserved_order_item_id', 'reserved_until'];

    // Ciphertext tidak pernah ikut serialisasi/JSON.
    protected $hidden = ['email_enc', 'password_enc'];

    protected function casts(): array
    {
        return ['reserved_until' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservedItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'reserved_order_item_id');
    }
}
