<?php

declare(strict_types=1);

namespace App\Services\Stripe\Services;

use App\Services\Stripe\Helpers\BillingDriverEnum;

class StripeBillingDecisionService
{
    public function selectedDriver(): BillingDriverEnum
    {
        return BillingDriverEnum::fromConfig(config('stripe.billing_driver'));
    }

    public function usesCustomImplementation(): bool
    {
        return $this->selectedDriver() === BillingDriverEnum::Custom;
    }

    public function usesCashier(): bool
    {
        return $this->selectedDriver() === BillingDriverEnum::Cashier;
    }
}
