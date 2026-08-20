<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayoutAccount extends Model
{
    protected $fillable = [
        'vendor_profile_id', 'type', 'holder_name', 'bank_name', 'iban', 'account_number',
        'currency', 'is_default', 'verified_at',
    ];

    protected $hidden = ['iban', 'account_number'];
    protected $casts = ['is_default' => 'boolean', 'verified_at' => 'datetime'];

    public function vendor(): BelongsTo { return $this->belongsTo(VendorProfile::class, 'vendor_profile_id'); }
    public function payouts(): HasMany { return $this->hasMany(PayoutRequest::class, 'payout_account_id'); }
}
