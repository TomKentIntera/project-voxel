<?php

declare(strict_types=1);

namespace App\Services\Stripe\Helpers;

class StripeEnvironmentResolver
{
    private const PRODUCTION = 'production';

    private const STAGING = 'staging';

    public function planEnvironment(): string
    {
        $configured = strtolower((string) config('stripe.plan_environment', self::STAGING));

        return $configured === self::PRODUCTION
            ? self::PRODUCTION
            : self::STAGING;
    }
}
