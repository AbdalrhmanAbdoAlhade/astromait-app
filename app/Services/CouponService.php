<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;

class CouponService
{
    public function findValid(string $code): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            throw new \RuntimeException('كود الخصم غير موجود.');
        }

        if (! $coupon->isValid()) {
            throw new \RuntimeException('كود الخصم منتهي أو غير فعال.');
        }

        return $coupon;
    }

    /**
     * Validate a coupon against a cart subtotal and, if vendor-scoped,
     * against the vendors present in the cart.
     *
     * @param  array<int>  $cartVendorIds  Vendor profile IDs present in the cart
     */
    public function validate(Coupon $coupon, float $subtotal, array $cartVendorIds = []): void
    {
        if (! $coupon->isValid()) {
            throw new \RuntimeException('كود الخصم منتهي أو غير فعال.');
        }

        if ($subtotal < (float) $coupon->min_order_amount) {
            throw new \RuntimeException("الحد الأدنى للطلب لاستخدام هذا الكود هو {$coupon->min_order_amount}");
        }

        if ($coupon->vendor_id && ! in_array($coupon->vendor_id, $cartVendorIds, true)) {
            throw new \RuntimeException('هذا الكود خاص بمنتجات تاجر معين غير موجود في سلتك.');
        }
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === 'percentage') {
            return round($subtotal * ((float) $coupon->value / 100), 2);
        }

        return min((float) $coupon->value, $subtotal);
    }

    public function recordUsage(Coupon $coupon, User $user, Order $order): void
    {
        $coupon->usages()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        $coupon->increment('used_count');
    }
}
