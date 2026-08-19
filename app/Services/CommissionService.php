<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\VendorProfile;

class CommissionService
{
    /**
     * Calculate the platform's commission amount for a given sale amount,
     * using the vendor's own commission_rate (percentage).
     */
    public function calculate(VendorProfile $vendor, float $amount): float
    {
        $rate = (float) $vendor->commission_rate;

        return round($amount * ($rate / 100), 2);
    }

    /**
     * Compute and persist the commission for an order item.
     */
    public function applyToOrderItem(OrderItem $orderItem): OrderItem
    {
        $vendor = $orderItem->vendor;
        $lineTotal = (float) $orderItem->price * $orderItem->quantity;

        $orderItem->update([
            'commission_amount' => $this->calculate($vendor, $lineTotal),
        ]);

        return $orderItem->fresh();
    }

    /**
     * Net payout amount for a vendor on a given order item (sale minus commission).
     */
    public function netPayout(OrderItem $orderItem): float
    {
        $lineTotal = (float) $orderItem->price * $orderItem->quantity;

        return round($lineTotal - (float) $orderItem->commission_amount, 2);
    }
}
