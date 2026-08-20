<?php

return [
    'default_shipping_cost' => (float) env('DEFAULT_SHIPPING_COST', 30),
    'default_commission_rate' => (float) env('DEFAULT_COMMISSION_RATE', 10),
    'minimum_payout_amount' => (float) env('MINIMUM_PAYOUT_AMOUNT', 50),
];
