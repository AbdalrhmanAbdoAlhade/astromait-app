<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\VendorProfile;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(private readonly SettlementService $settlementService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(Settlement::with('vendor.user:id,name,email')->latest()->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_profile_id' => ['required', 'integer', 'exists:vendor_profiles,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);
        $settlement = $this->settlementService->create(VendorProfile::findOrFail($data['vendor_profile_id']), $data['period_start'], $data['period_end']);
        return response()->json($settlement, 201);
    }

    public function approve(Request $request, Settlement $settlement): JsonResponse
    {
        return response()->json($this->settlementService->approve($settlement, $request->user()->id));
    }
}
