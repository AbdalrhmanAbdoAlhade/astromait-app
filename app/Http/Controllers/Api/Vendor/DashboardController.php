<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly CommissionService $commissionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        $orderItems = OrderItem::where('vendor_id', $vendor->id)->get();

        $totalSales = $orderItems->sum(fn (OrderItem $item) => $item->price * $item->quantity);
        $totalCommission = $orderItems->sum('commission_amount');

        return response()->json([
            'products_count' => $vendor->products()->count(),
            'active_products_count' => $vendor->products()->where('status', 'active')->count(),
            'services_count' => $vendor->services()->count(),
            'auctions_count' => $vendor->auctions()->count(),
            'orders_count' => $orderItems->count(),
            'total_sales' => round($totalSales, 2),
            'total_commission' => round($totalCommission, 2),
            'net_payout' => round($totalSales - $totalCommission, 2),
        ]);
    }
}
