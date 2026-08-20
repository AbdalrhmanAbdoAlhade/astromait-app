<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorPayoutAccount;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function show(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;
        $wallet = $this->walletService->wallet($vendor);
        return response()->json(['wallet' => $wallet]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;
        $wallet = $this->walletService->wallet($vendor);
        return response()->json($wallet->transactions()->latest()->paginate($request->integer('per_page', 20)));
    }

    public function accounts(Request $request): JsonResponse
    {
        return response()->json($request->user()->vendorProfile->payoutAccounts()->latest()->get());
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:bank,iban,wallet'],
            'holder_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:64'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        $account = $this->walletService->addPayoutAccount($request->user()->vendorProfile, $data);
        return response()->json($account, 201);
    }

    public function payouts(Request $request): JsonResponse
    {
        return response()->json($request->user()->vendorProfile->payoutRequests()->with('payoutAccount')->latest()->paginate($request->integer('per_page', 15)));
    }

    public function requestPayout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payout_account_id' => ['required', 'integer', 'exists:vendor_payout_accounts,id'],
        ]);
        $vendor = $request->user()->vendorProfile;
        $account = $vendor->payoutAccounts()->findOrFail($data['payout_account_id']);
        $payout = $this->walletService->requestPayout($vendor, (float) $data['amount'], $account);
        return response()->json($payout, 201);
    }
}
