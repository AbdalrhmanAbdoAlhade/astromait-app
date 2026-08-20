<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Settlement;
use App\Models\VendorPayoutAccount;
use App\Models\VendorProfile;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalletService
{
    public function wallet(VendorProfile $vendor): VendorWallet
    {
        return VendorWallet::firstOrCreate(
            ['vendor_profile_id' => $vendor->id],
            ['currency' => 'SAR']
        );
    }

    public function recordOrderPaid(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                $wallet = $this->wallet($item->vendor);
                $gross = round((float) $item->price * $item->quantity, 2);
                $commission = round((float) $item->commission_amount, 2);
                $net = round($gross - $commission, 2);
                $key = 'sale-pending:'.$item->id;
                if (WalletTransaction::where('idempotency_key', $key)->exists()) {
                    continue;
                }
                $this->post($wallet, 'sale_pending', $net, [
                    'pending_delta' => $net,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'idempotency_key' => $key,
                    'description' => 'إضافة مستحقات معلقة بعد نجاح دفع الطلب.',
                    'metadata' => ['gross' => $gross, 'commission' => $commission],
                ]);
                $wallet->increment('gross_sales', $gross);
                $wallet->increment('platform_commission', $commission);
            }
        });
    }

    public function releaseOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                $wallet = $this->wallet($item->vendor);
                $net = round((float) $item->price * $item->quantity - (float) $item->commission_amount, 2);
                $key = 'sale-release:'.$item->id;
                if (WalletTransaction::where('idempotency_key', $key)->exists()) {
                    continue;
                }
                $this->assertBalance($wallet->pending_balance, $net, 'الرصيد المعلق غير كافٍ لتحرير الطلب.');
                $this->post($wallet, 'sale_released', $net, [
                    'pending_delta' => -$net,
                    'available_delta' => $net,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'idempotency_key' => $key,
                    'description' => 'تحويل المستحق من المعلق إلى المتاح بعد اكتمال الطلب.',
                ]);
            }
        });
    }

    public function addPayoutAccount(VendorProfile $vendor, array $data): VendorPayoutAccount
    {
        return DB::transaction(function () use ($vendor, $data) {
            if (($data['is_default'] ?? false) === true) {
                $vendor->payoutAccounts()->update(['is_default' => false]);
            }
            return $vendor->payoutAccounts()->create($data);
        });
    }

    public function requestPayout(VendorProfile $vendor, float $amount, VendorPayoutAccount $account): PayoutRequest
    {
        return DB::transaction(function () use ($vendor, $amount, $account) {
            $wallet = $this->wallet($vendor);
            $amount = round($amount, 2);
            $minimum = (float) config('marketplace.minimum_payout_amount', 50);
            if ($amount < $minimum) {
                throw new RuntimeException('الحد الأدنى للتحويل هو '.$minimum.' ريال.');
            }
            $this->assertBalance($wallet->available_balance, $amount, 'الرصيد المتاح غير كافٍ.');
            $payout = $vendor->payoutRequests()->create([
                'wallet_id' => $wallet->id,
                'payout_account_id' => $account->id,
                'payout_number' => 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'amount' => $amount,
                'currency' => $wallet->currency,
                'status' => 'pending',
                'method' => $account->type,
                'account_snapshot' => [
                    'type' => $account->type,
                    'holder_name' => $account->holder_name,
                    'bank_name' => $account->bank_name,
                    'iban_last4' => $account->iban ? substr($account->iban, -4) : null,
                    'currency' => $account->currency,
                ],
                'requested_at' => now(),
            ]);
            $this->post($wallet, 'payout_hold', -$amount, [
                'available_delta' => -$amount,
                'held_delta' => $amount,
                'payout_request_id' => $payout->id,
                'idempotency_key' => 'payout-hold:'.$payout->id,
                'description' => 'حجز مبلغ طلب التحويل للمراجعة.',
            ]);
            return $payout->fresh();
        });
    }

    public function approvePayout(PayoutRequest $payout, int $adminId): PayoutRequest
    {
        if ($payout->status !== 'pending') {
            throw new RuntimeException('لا يمكن اعتماد طلب التحويل في حالته الحالية.');
        }
        $payout->update(['status' => 'approved', 'processed_by' => $adminId]);
        return $payout->fresh();
    }

    public function rejectPayout(PayoutRequest $payout, int $adminId, string $reason): PayoutRequest
    {
        return DB::transaction(function () use ($payout, $adminId, $reason) {
            if (! in_array($payout->status, ['pending', 'approved'], true)) {
                throw new RuntimeException('لا يمكن رفض طلب التحويل في حالته الحالية.');
            }
            $wallet = $payout->wallet;
            $this->post($wallet, 'payout_released', (float) $payout->amount, [
                'held_delta' => -(float) $payout->amount,
                'available_delta' => (float) $payout->amount,
                'payout_request_id' => $payout->id,
                'idempotency_key' => 'payout-reject:'.$payout->id,
                'description' => 'إعادة مبلغ التحويل المرفوض إلى الرصيد المتاح.',
                'metadata' => ['reason' => $reason],
            ]);
            $payout->update(['status' => 'rejected', 'rejection_reason' => $reason, 'processed_by' => $adminId, 'processed_at' => now()]);
            return $payout->fresh();
        });
    }

    public function markPayoutPaid(PayoutRequest $payout, int $adminId, string $providerReference): PayoutRequest
    {
        return DB::transaction(function () use ($payout, $adminId, $providerReference) {
            if ($payout->status !== 'approved') {
                throw new RuntimeException('يجب اعتماد طلب التحويل قبل وضعه كمدفوع.');
            }
            $wallet = $payout->wallet;
            $this->post($wallet, 'payout_paid', -(float) $payout->amount, [
                'held_delta' => -(float) $payout->amount,
                'paid_delta' => (float) $payout->amount,
                'payout_request_id' => $payout->id,
                'idempotency_key' => 'payout-paid:'.$payout->id,
                'description' => 'تسجيل تنفيذ التحويل للتاجر.',
                'metadata' => ['provider_reference' => $providerReference],
            ]);
            $payout->update(['status' => 'paid', 'provider_reference' => $providerReference, 'processed_by' => $adminId, 'processed_at' => now()]);
            return $payout->fresh();
        });
    }

    public function post(VendorWallet $wallet, string $type, float $amount, array $data): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $type, $amount, $data) {
            $wallet = VendorWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            if (isset($data['idempotency_key']) && WalletTransaction::where('idempotency_key', $data['idempotency_key'])->exists()) {
                return WalletTransaction::where('idempotency_key', $data['idempotency_key'])->firstOrFail();
            }
            $wallet->pending_balance = round((float) $wallet->pending_balance + (float) ($data['pending_delta'] ?? 0), 2);
            $wallet->available_balance = round((float) $wallet->available_balance + (float) ($data['available_delta'] ?? 0), 2);
            $wallet->held_balance = round((float) $wallet->held_balance + (float) ($data['held_delta'] ?? 0), 2);
            $wallet->paid_balance = round((float) $wallet->paid_balance + (float) ($data['paid_delta'] ?? 0), 2);
            foreach (['pending_balance', 'available_balance', 'held_balance', 'paid_balance'] as $balance) {
                $this->assertNonNegative($wallet->{$balance});
            }
            $wallet->save();
            return $wallet->transactions()->create(array_merge([
                'type' => $type,
                'amount' => round($amount, 2),
                'pending_delta' => $data['pending_delta'] ?? 0,
                'available_delta' => $data['available_delta'] ?? 0,
                'held_delta' => $data['held_delta'] ?? 0,
                'paid_delta' => $data['paid_delta'] ?? 0,
                'pending_balance' => $wallet->pending_balance,
                'available_balance' => $wallet->available_balance,
                'held_balance' => $wallet->held_balance,
                'paid_balance' => $wallet->paid_balance,
                'currency' => $wallet->currency,
                'order_id' => $data['order_id'] ?? null,
                'order_item_id' => $data['order_item_id'] ?? null,
                'payout_request_id' => $data['payout_request_id'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? Str::uuid()->toString(),
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ], isset($data['reference']) ? ['reference_type' => $data['reference']::class, 'reference_id' => $data['reference']->getKey()] : []));
        });
    }

    private function assertBalance($balance, float $amount, string $message): void
    {
        if ((float) $balance + 0.0001 < $amount) {
            throw new RuntimeException($message);
        }
    }

    private function assertNonNegative($balance): void
    {
        if ((float) $balance < -0.0001) {
            throw new RuntimeException('لا يمكن أن يصبح رصيد المحفظة سالبًا.');
        }
    }
}
