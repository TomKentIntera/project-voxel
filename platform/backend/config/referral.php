<?php

return [
    'default_discount_percent' => (int) env('REFERRAL_DEFAULT_DISCOUNT_PERCENT', 50),
    'default_referral_percent' => (int) env('REFERRAL_DEFAULT_REFERRAL_PERCENT', 15),
    'ledger_period_days' => (int) env('REFERRAL_LEDGER_PERIOD_DAYS', 90),
];
