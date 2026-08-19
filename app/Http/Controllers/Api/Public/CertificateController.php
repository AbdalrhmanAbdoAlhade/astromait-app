<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private readonly CertificationService $certificationService)
    {
    }

    /**
     * Public QR-scan verification endpoint — no auth required.
     */
    public function verify(string $number, Request $request): JsonResponse
    {
        $certificate = $this->certificationService->verify(
            $number,
            $request->ip(),
            $request->userAgent()
        );

        if (! $certificate) {
            return response()->json(['message' => 'الشهادة غير موجودة.'], 404);
        }

        return response()->json($certificate);
    }
}
