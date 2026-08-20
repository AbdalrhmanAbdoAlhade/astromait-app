<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\VendorProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SettlementService
{
    public function __construct(private readonly WalletService $walletService) {}

    public function create(VendorProfile $vendor, string $start, string $end): Settlement
    {
        return DB::transaction(function () use ($vendor, $start, $end) {
            $eligible = \App\Models\OrderItem::with('order')
                ->where('vendor_id', $vendor->id)
                ->whereHas('order', fn ($q) => $q->where('status', 'completed')->whereBetween('updated_at', [$start.' 00:00:00', $end.' 23:59:59']))
                ->whereDoesntHave('settlementItem')
                ->lockForUpdate()
                ->get();
            if ($eligible->isEmpty()) {
                throw new RuntimeException('لا توجد عناصر طلب مؤهلة لهذه الفترة.');
            }
            foreach ($eligible as $item) {
                $this->walletService->releaseOrder($item->order->fresh('items'));
            }
            $settlement = Settlement::create([
                'vendor_profile_id' => $vendor->id,
                'settlement_number' => 'SET-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'period_start' => $start,
                'period_end' => $end,
                'status' => 'draft',
                'currency' => 'SAR',
            ]);
            $gross = $commission = $net = 0;
            foreach ($eligible as $item) {
                $lineGross = round((float) $item->price * $item->quantity, 2);
                $lineCommission = round((float) $item->commission_amount, 2);
                $lineNet = round($lineGross - $lineCommission, 2);
                $settlement->items()->create([
                    'order_item_id' => $item->id,
                    'gross_amount' => $lineGross,
                    'commission_amount' => $lineCommission,
                    'refund_amount' => 0,
                    'net_amount' => $lineNet,
                ]);
                $gross += $lineGross;
                $commission += $lineCommission;
                $net += $lineNet;
            }
            $settlement->update(['gross_sales' => $gross, 'platform_commission' => $commission, 'net_amount' => $net]);
            return $settlement->fresh('items');
        });
    }

    public function approve(Settlement $settlement, int $adminId): Settlement
    {
        if ($settlement->status !== 'draft') {
            throw new RuntimeException('لا يمكن اعتماد التسوية في حالتها الحالية.');
        }
        $settlement->update(['status' => 'approved', 'approved_by' => $adminId, 'approved_at' => now()]);
        return $settlement->fresh();
    }
}
