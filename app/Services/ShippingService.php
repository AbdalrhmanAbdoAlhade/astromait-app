<?php

namespace App\Services;

use App\Models\Address;
use App\Models\ShippingZone;

class ShippingService
{
    public function calculateCost(Address $address): float
    {
        $zone = $this->resolveZone($address);

        return $zone ? (float) $zone->cost : (float) config('marketplace.default_shipping_cost', 30);
    }

    public function estimatedDays(Address $address): int
    {
        $zone = $this->resolveZone($address);

        return $zone ? $zone->estimated_days : 5;
    }

    private function resolveZone(Address $address): ?ShippingZone
    {
        return ShippingZone::where('is_active', true)
            ->where('country', $address->country)
            ->first();
    }
}
