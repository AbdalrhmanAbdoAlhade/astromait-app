<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CartService $cartService,
    ) {
    }

    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string']);

        $cart = $this->cartService->getOrCreateCart($request->user());
        $subtotal = $cart->total();
        $vendorIds = $cart->items->pluck('vendor_id')->unique()->all();

        $coupon = $this->couponService->findValid($data['code']);
        $this->couponService->validate($coupon, $subtotal, $vendorIds);
        $discount = $this->couponService->calculateDiscount($coupon, $subtotal);

        return response()->json([
            'coupon' => $coupon,
            'discount' => $discount,
            'total_after_discount' => max($subtotal - $discount, 0),
        ]);
    }
}
