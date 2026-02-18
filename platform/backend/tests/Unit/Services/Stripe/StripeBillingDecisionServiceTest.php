<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stripe;

use App\Services\Stripe\Helpers\BillingDriverEnum;
use App\Services\Stripe\Services\StripeBillingDecisionService;
use Tests\TestCase;

class StripeBillingDecisionServiceTest extends TestCase
{
    public function test_it_defaults_to_custom_driver_when_missing_or_invalid(): void
    {
        config()->set('stripe.billing_driver', null);

        $service = new StripeBillingDecisionService();
        $this->assertSame(BillingDriverEnum::Custom, $service->selectedDriver());
        $this->assertTrue($service->usesCustomImplementation());
        $this->assertFalse($service->usesCashier());

        config()->set('stripe.billing_driver', 'something-else');

        $this->assertSame(BillingDriverEnum::Custom, $service->selectedDriver());
    }

    public function test_it_can_resolve_cashier_when_configured(): void
    {
        config()->set('stripe.billing_driver', 'cashier');

        $service = new StripeBillingDecisionService();
        $this->assertSame(BillingDriverEnum::Cashier, $service->selectedDriver());
        $this->assertFalse($service->usesCustomImplementation());
        $this->assertTrue($service->usesCashier());
    }
}
