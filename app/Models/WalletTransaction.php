<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'type', 'amount', 'pending_delta', 'available_delta', 'held_delta', 'paid_delta',
        'pending_balance', 'available_balance', 'held_balance', 'paid_balance', 'currency',
        'idempotency_key', 'reference_type', 'reference_id', 'order_id', 'order_item_id',
        'payout_request_id', 'metadata', 'description', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 'pending_delta' => 'decimal:2', 'available_delta' => 'decimal:2',
        'held_delta' => 'decimal:2', 'paid_delta' => 'decimal:2',
        'pending_balance' => 'decimal:2', 'available_balance' => 'decimal:2',
        'held_balance' => 'decimal:2', 'paid_balance' => 'decimal:2', 'metadata' => 'array',
    ];

    public function wallet(): BelongsTo { return $this->belongsTo(VendorWallet::class, 'wallet_id'); }
    public function reference(): MorphTo { return $this->morphTo(); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
