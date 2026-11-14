<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_item_id', 'digital_account_id', 'delivered_at'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime'];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function digitalAccount(): BelongsTo
    {
        return $this->belongsTo(DigitalAccount::class);
    }
}
