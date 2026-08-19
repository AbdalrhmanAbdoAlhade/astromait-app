<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        return response()->json($vendor->coupons()->latest()->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $data['vendor_id'] = $vendor->id;
        $data['is_active'] = true;

        return response()->json(Coupon::create($data), 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $this->authorizeOwnership($request, $coupon);

        $data = $request->validate([
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'sometimes|nullable|date',
            'usage_limit' => 'sometimes|nullable|integer|min:1',
        ]);

        $coupon->update($data);

        return response()->json($coupon->fresh());
    }

    public function destroy(Request $request, Coupon $coupon): JsonResponse
    {
        $this->authorizeOwnership($request, $coupon);
        $coupon->delete();

        return response()->json(['message' => 'تم حذف الكوبون.']);
    }

    private function authorizeOwnership(Request $request, Coupon $coupon): void
    {
        $vendor = $request->user()->vendorProfile;

        if (! $vendor || (int) $coupon->vendor_id !== (int) $vendor->id) {
            abort(403);
        }
    }
}
