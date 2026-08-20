<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRequest extends Model
{
    protected $fillable = [
        'vendor_profile_id', 'wallet_id', 'settlement_id', 'payout_account_id', 'payout_number',
        'amount', 'currency', 'status', 'method', 'account_snapshot', 'provider_reference',
        'rejection_reason', 'processed_by', 'requested_at', 'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 'account_snapshot' => 'array',
        'requested_at' => 'datetime', 'processed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo { return $this->belongsTo(VendorProfile::class, 'vendor_profile_id'); }
    public function wallet(): BelongsTo { return $this->belongsTo(VendorWallet::class, 'wallet_id'); }
    public function settlement(): BelongsTo { return $this->belongsTo(Settlement::class); }
    public function payoutAccount(): BelongsTo { return $this->belongsTo(VendorPayoutAccount::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
