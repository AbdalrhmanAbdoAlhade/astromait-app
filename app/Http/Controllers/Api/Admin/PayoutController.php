<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function index(Request $request): JsonResponse
    {
        $payouts = PayoutRequest::with(['vendor.user:id,name,email', 'payoutAccount'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 20));
        return response()->json($payouts);
    }

    public function approve(Request $request, PayoutRequest $payoutRequest): JsonResponse
    {
        return response()->json($this->walletService->approvePayout($payoutRequest, $request->user()->id));
    }

    public function reject(Request $request, PayoutRequest $payoutRequest): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        return response()->json($this->walletService->rejectPayout($payoutRequest, $request->user()->id, $data['reason']));
    }

    public function markPaid(Request $request, PayoutRequest $payoutRequest): JsonResponse
    {
        $data = $request->validate(['provider_reference' => ['required', 'string', 'max:255']]);
        return response()->json($this->walletService->markPayoutPaid($payoutRequest, $request->user()->id, $data['provider_reference']));
    }
}
