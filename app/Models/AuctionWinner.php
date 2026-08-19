<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionWinner extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'user_id',
        'final_amount',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'final_amount' => 'decimal:2',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
