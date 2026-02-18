<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stripe;

use App\Services\Stripe\Helpers\StripeEnvironmentResolver;
use App\Services\Stripe\Repositories\StripePlanRepository;
use Tests\TestCase;

class StripePlanRepositoryTest extends TestCase
{
    public function test_it_uses_staging_mapping_by_default(): void
    {
        config()->set('stripe.plan_environment', 'staging');
        config()->set('plans.planList', [
            [
                'name' => 'parrot',
                'stripe_subscription' => [
                    'staging' => 'prod_stage',
                    'production' => 'prod_live',
                ],
            ],
        ]);

        $repository = new StripePlanRepository(new StripeEnvironmentResolver());

        $this->assertSame('prod_stage', $repository->stripeCatalogIdForPlan('parrot'));
    }

    public function test_it_uses_production_mapping_when_configured(): void
    {
        config()->set('stripe.plan_environment', 'production');
        config()->set('plans.planList', [
            [
                'name' => 'parrot',
                'stripe_subscription' => [
                    'staging' => 'prod_stage',
                    'production' => 'prod_live',
                ],
            ],
        ]);

        $repository = new StripePlanRepository(new StripeEnvironmentResolver());

        $this->assertSame('prod_live', $repository->stripeCatalogIdForPlan('parrot'));
    }

    public function test_it_returns_null_when_plan_or_mapping_is_missing(): void
    {
        config()->set('stripe.plan_environment', 'staging');
        config()->set('plans.planList', [
            [
                'name' => 'rabbit',
            ],
        ]);

        $repository = new StripePlanRepository(new StripeEnvironmentResolver());

        $this->assertNull($repository->stripeCatalogIdForPlan('does-not-exist'));
        $this->assertNull($repository->stripeCatalogIdForPlan('rabbit'));
    }
}
