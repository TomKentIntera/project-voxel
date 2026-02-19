<?php

return [
    'billing_driver' => env('STRIPE_BILLING_DRIVER', 'custom'),

    // Determines which key in plans.planList[*].stripe_subscription is used.
    'plan_environment' => env(
        'STRIPE_PLAN_ENVIRONMENT',
        env('APP_ENV') === 'production' ? 'production' : 'staging'
    ),

    'checkout_success_url' => env(
        'STRIPE_CHECKOUT_SUCCESS_URL',
        rtrim((string) env('FRONTEND_URL', 'http://localhost'), '/')
            .'/billing/success?session_id={CHECKOUT_SESSION_ID}'
    ),

    'checkout_cancel_url' => env(
        'STRIPE_CHECKOUT_CANCEL_URL',
        rtrim((string) env('FRONTEND_URL', 'http://localhost'), '/').'/billing/cancel'
    ),
];
