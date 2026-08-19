<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ties Cart + Coupon + Commission + Shipping together to turn a cart
 * into an Order. Referenced by the checkout controller in Phase 4.
 */
class OrderService
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CommissionService $commissionService,
        private readonly ShippingService $shippingService,
    ) {
    }

    public function checkout(User $user, Cart $cart, Address $address, ?string $couponCode = null): Order
    {
        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('السلة فارغة.');
        }

        return DB::transaction(function () use ($user, $cart, $address, $couponCode) {
            $subtotal = $cart->total();
            $shippingCost = $this->shippingService->calculateCost($address);

            $coupon = null;
            $discount = 0.0;

            if ($couponCode) {
                $coupon = $this->couponService->findValid($couponCode);
                $vendorIds = $cart->items->pluck('vendor_id')->unique()->all();
                $this->couponService->validate($coupon, $subtotal, $vendorIds);
                $discount = $this->couponService->calculateDiscount($coupon, $subtotal);
            }

            $order = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shippingCost,
                'total' => max($subtotal - $discount + $shippingCost, 0),
                'status' => 'pending',
                'coupon_id' => $coupon?->id,
            ]);

            foreach ($cart->items as $item) {
                $orderItem = $order->items()->create([
                    'orderable_type' => $item->itemable_type,
                    'orderable_id' => $item->itemable_id,
                    'vendor_id' => $item->vendor_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]);

                $this->commissionService->applyToOrderItem($orderItem);
            }

            if ($coupon) {
                $this->couponService->recordUsage($coupon, $user, $order);
            }

            $cart->items()->delete();

            return $order->fresh(['items']);
        });
    }
}
