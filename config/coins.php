<?php

return [
    'earn_rate' => (float) env('COINS_EARN_RATE', 0.5),
    'per_bill_limit' => (int) env('COINS_PER_BILL_LIMIT', 30),
    'user_monthly_limit' => (int) env('COINS_USER_MONTHLY_LIMIT', 100),
    'shared_monthly_limit' => (int) env('COINS_SHARED_MONTHLY_LIMIT', 500),
    'large_bill_gst_threshold' => (float) env('COINS_LARGE_BILL_GST_THRESHOLD', 1000),
    'redemption_coins_per_rupee' => (int) env('COINS_PER_RUPEE', 10),
];
