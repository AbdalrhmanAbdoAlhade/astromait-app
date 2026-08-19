<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        $items = OrderItem::where('vendor_id', $vendor->id)
            ->with(['order.user:id,name,phone', 'orderable'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($items);
    }
}
