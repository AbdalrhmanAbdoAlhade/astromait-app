<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorProfile;
use App\Services\VendorOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(private readonly VendorOnboardingService $vendorOnboardingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = VendorProfile::with('user:id,name,email,phone');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function approve(Request $request, VendorProfile $vendorProfile): JsonResponse
    {
        return response()->json(
            $this->vendorOnboardingService->approve($vendorProfile, $request->user())
        );
    }

    public function reject(Request $request, VendorProfile $vendorProfile): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string']);

        return response()->json(
            $this->vendorOnboardingService->reject($vendorProfile, $data['reason'])
        );
    }

    public function suspend(Request $request, VendorProfile $vendorProfile): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string']);

        return response()->json(
            $this->vendorOnboardingService->suspend($vendorProfile, $data['reason'])
        );
    }
}
