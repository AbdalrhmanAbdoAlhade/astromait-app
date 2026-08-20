<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    protected $fillable = [
        'vendor_profile_id', 'settlement_number', 'period_start', 'period_end', 'status',
        'gross_sales', 'platform_commission', 'refunds', 'net_amount', 'currency',
        'approved_at', 'paid_at', 'approved_by',
    ];

    protected $casts = [
        'period_start' => 'date', 'period_end' => 'date', 'gross_sales' => 'decimal:2',
        'platform_commission' => 'decimal:2', 'refunds' => 'decimal:2', 'net_amount' => 'decimal:2',
        'approved_at' => 'datetime', 'paid_at' => 'datetime',
    ];

    public function vendor(): BelongsTo { return $this->belongsTo(VendorProfile::class, 'vendor_profile_id'); }
    public function items(): HasMany { return $this->hasMany(SettlementItem::class); }
    public function payouts(): HasMany { return $this->hasMany(PayoutRequest::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
