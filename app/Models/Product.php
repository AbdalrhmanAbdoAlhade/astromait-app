<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
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
        'compare_price',
        'stock',
        'sku',
        'weight_grams',
        'status',
        'rejection_reason',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'weight_grams' => 'decimal:3',
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

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function certificate(): MorphOne
    {
        return $this->morphOne(Certificate::class, 'certifiable');
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }

    public function activeAuction(): HasOne
    {
        return $this->hasOne(Auction::class)->whereIn('status', ['scheduled', 'live']);
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
