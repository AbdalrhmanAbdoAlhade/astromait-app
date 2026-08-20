<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorPayoutAccount;
use App\Models\VendorProfile;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financialVendor(): VendorProfile
{
    $user = User::factory()->create();
    return VendorProfile::create([
        'user_id' => $user->id,
        'store_name' => 'Astromait Store',
        'store_slug' => 'astromait-store-'.uniqid(),
        'commission_rate' => 10,
        'status' => 'approved',
        'is_verified' => true,
    ]);
}

test('a paid order creates pending vendor earnings only once and releases them', function () {
    $vendor = financialVendor();
    $customer = User::factory()->create();
    $order = Order::create([
        'order_number' => 'ORD-TEST-001',
        'user_id' => $customer->id,
        'subtotal' => 100,
        'discount' => 0,
        'shipping_cost' => 0,
        'total' => 100,
        'status' => 'paid',
    ]);
    $item = $order->items()->create([
        'orderable_type' => App\Models\Product::class,
        'orderable_id' => 1,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price' => 100,
        'commission_amount' => 10,
    ]);

    $service = app(WalletService::class);
    $service->recordOrderPaid($order->fresh('items'));
    $service->recordOrderPaid($order->fresh('items'));

    $wallet = $vendor->fresh()->wallet;
    expect((float) $wallet->pending_balance)->toBe(90.0)
        ->and($wallet->transactions()->count())->toBe(1);

    $service->releaseOrder($order->fresh('items'));
    $wallet->refresh();
    expect((float) $wallet->pending_balance)->toBe(0.0)
        ->and((float) $wallet->available_balance)->toBe(90.0);
});

test('a vendor payout is held, rejected back to available, and can be paid', function () {
    $vendor = financialVendor();
    $walletService = app(WalletService::class);
    $wallet = $walletService->wallet($vendor);
    $walletService->post($wallet, 'manual_credit', 150, [
        'available_delta' => 150,
        'idempotency_key' => 'test-credit-150',
    ]);
    $account = $vendor->payoutAccounts()->create([
        'type' => 'bank',
        'holder_name' => 'Astromait Store',
        'bank_name' => 'Test Bank',
        'iban' => 'SA0000000000000000000000',
        'currency' => 'SAR',
        'is_default' => true,
    ]);

    $payout = $walletService->requestPayout($vendor, 100, $account);
    $wallet->refresh();
    expect($payout->status)->toBe('pending')
        ->and((float) $wallet->available_balance)->toBe(50.0)
        ->and((float) $wallet->held_balance)->toBe(100.0);

    $walletService->rejectPayout($payout, 1, 'مراجعة إضافية');
    $wallet->refresh();
    expect((float) $wallet->available_balance)->toBe(150.0)
        ->and((float) $wallet->held_balance)->toBe(0.0);

    $payout = $walletService->requestPayout($vendor, 100, $account);
    $walletService->approvePayout($payout, 1);
    $walletService->markPayoutPaid($payout->fresh(), 1, 'BANK-123');
    $wallet->refresh();
    expect((float) $wallet->available_balance)->toBe(50.0)
        ->and((float) $wallet->held_balance)->toBe(0.0)
        ->and((float) $wallet->paid_balance)->toBe(100.0);
});
