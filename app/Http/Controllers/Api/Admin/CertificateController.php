<?php

namespace App\Http\Controllers\Api\Admin;

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

    /**
     * Admin can issue a certificate directly for any product or service.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'certifiable_type' => 'required|in:product,service',
            'certifiable_id' => 'required|integer',
            'meteorite_type' => 'nullable|string|max:255',
            'origin_location' => 'nullable|string|max:255',
            'discovery_date' => 'nullable|date',
            'analysis_report_path' => 'nullable|string',
        ]);

        $certifiable = $data['certifiable_type'] === 'product'
            ? Product::findOrFail($data['certifiable_id'])
            : Service::findOrFail($data['certifiable_id']);

        $certificate = $this->certificationService->issue($certifiable, $request->user(), $data);

        return response()->json($certificate, 201);
    }
}
