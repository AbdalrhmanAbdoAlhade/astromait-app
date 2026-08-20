<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_profile_id', 'currency', 'pending_balance', 'available_balance',
        'held_balance', 'paid_balance', 'gross_sales', 'platform_commission', 'refunded_amount',
    ];

    protected $casts = [
        'pending_balance' => 'decimal:2', 'available_balance' => 'decimal:2',
        'held_balance' => 'decimal:2', 'paid_balance' => 'decimal:2',
        'gross_sales' => 'decimal:2', 'platform_commission' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo { return $this->belongsTo(VendorProfile::class, 'vendor_profile_id'); }
    public function transactions(): HasMany { return $this->hasMany(WalletTransaction::class, 'wallet_id'); }
    public function payouts(): HasMany { return $this->hasMany(PayoutRequest::class, 'wallet_id'); }
}
