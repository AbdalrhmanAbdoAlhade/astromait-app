<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'price',
        'duration_minutes',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function certificate(): MorphOne
    {
        return $this->morphOne(Certificate::class, 'certifiable');
    }

    public function cartItems(): MorphMany
    {
        return $this->morphMany(CartItem::class, 'itemable');
    }

    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'orderable');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
