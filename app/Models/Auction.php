<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'product_id',
        'start_price',
        'current_price',
        'min_bid_increment',
        'start_at',
        'end_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_price' => 'decimal:2',
            'current_price' => 'decimal:2',
            'min_bid_increment' => 'decimal:2',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class)->orderByDesc('amount');
    }

    public function highestBid(): HasOne
    {
        return $this->hasOne(Bid::class)->orderByDesc('amount');
    }

    public function winner(): HasOne
    {
        return $this->hasOne(AuctionWinner::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }
}
