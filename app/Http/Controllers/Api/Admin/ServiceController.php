<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::with(['vendor.user:id,name', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function approve(Service $service): JsonResponse
    {
        $service->update(['status' => 'active', 'rejection_reason' => null]);

        return response()->json($service->fresh());
    }

    public function reject(Request $request, Service $service): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string']);

        $service->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

        return response()->json($service->fresh());
    }
}
