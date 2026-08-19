<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $paymentGatewayService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $this->paymentGatewayService->handleWebhook($request->all());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'ok']);
    }
}
