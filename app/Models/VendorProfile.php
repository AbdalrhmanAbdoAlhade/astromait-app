<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'store_slug',
        'store_logo',
        'store_cover',
        'bio',
        'phone',
        'is_verified',
        'commission_rate',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'commission_rate' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'vendor_id');
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'vendor_id');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'vendor_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->is_verified;
    }
}
