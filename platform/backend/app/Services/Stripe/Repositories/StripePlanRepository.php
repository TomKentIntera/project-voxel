<?php

declare(strict_types=1);

namespace App\Services\Stripe\Repositories;

use App\Services\Stripe\Helpers\StripeEnvironmentResolver;

class StripePlanRepository
{
    public function __construct(
        private readonly StripeEnvironmentResolver $environmentResolver
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return array_values(array_filter(
            config('plans.planList', []),
            static fn (mixed $plan): bool => is_array($plan)
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByName(string $planName): ?array
    {
        foreach ($this->all() as $plan) {
            if (($plan['name'] ?? null) === $planName) {
                return $plan;
            }
        }

        return null;
    }

    public function stripeCatalogIdForPlan(string $planName): ?string
    {
        $plan = $this->findByName($planName);

        if ($plan === null) {
            return null;
        }

        $subscriptionConfig = $plan['stripe_subscription'] ?? null;

        if (! is_array($subscriptionConfig)) {
            return null;
        }

        $environment = $this->environmentResolver->planEnvironment();
        $catalogId = $subscriptionConfig[$environment] ?? null;

        return is_string($catalogId) && $catalogId !== ''
            ? $catalogId
            : null;
    }
}
