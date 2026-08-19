<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Coupon::with('vendor.user:id,name')->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'vendor_id' => 'nullable|exists:vendor_profiles,id',
        ]);

        $data['is_active'] = true;

        return response()->json(Coupon::create($data), 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $data = $request->validate([
            'is_active' => 'sometimes|boolean',
            'usage_limit' => 'sometimes|nullable|integer|min:1',
            'expires_at' => 'sometimes|nullable|date',
        ]);

        $coupon->update($data);

        return response()->json($coupon->fresh());
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json(['message' => 'تم حذف الكوبون.']);
    }
}
