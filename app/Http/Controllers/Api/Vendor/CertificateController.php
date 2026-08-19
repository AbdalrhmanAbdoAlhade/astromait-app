<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
use App\Services\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private readonly CertificationService $certificationService)
    {
    }

    public function storeForProduct(Request $request, Product $product): JsonResponse
    {
        return $this->issue($request, $product);
    }

    public function storeForService(Request $request, Service $service): JsonResponse
    {
        return $this->issue($request, $service);
    }

    private function issue(Request $request, $certifiable): JsonResponse
    {
        $data = $request->validate([
            'meteorite_type' => 'nullable|string|max:255',
            'origin_location' => 'nullable|string|max:255',
            'discovery_date' => 'nullable|date',
            'analysis_report_path' => 'nullable|string',
        ]);

        try {
            $certificate = $this->certificationService->issue($certifiable, $request->user(), $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($certificate, 201);
    }
}
