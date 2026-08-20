<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    protected $fillable = ['settlement_id', 'order_item_id', 'gross_amount', 'commission_amount', 'refund_amount', 'net_amount'];
    protected $casts = ['gross_amount' => 'decimal:2', 'commission_amount' => 'decimal:2', 'refund_amount' => 'decimal:2', 'net_amount' => 'decimal:2'];
    public function settlement(): BelongsTo { return $this->belongsTo(Settlement::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
