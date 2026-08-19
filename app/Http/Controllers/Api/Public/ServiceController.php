<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()
            ->where('status', 'active')
            ->with(['category', 'vendor', 'certificate']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function show(Service $service): JsonResponse
    {
        if ($service->status !== 'active') {
            abort(404);
        }

        $service->load(['category', 'vendor', 'certificate']);

        return response()->json($service);
    }
}
