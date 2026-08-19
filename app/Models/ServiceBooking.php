<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'user_id',
        'status',
        'scheduled_at',
        'notes',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
