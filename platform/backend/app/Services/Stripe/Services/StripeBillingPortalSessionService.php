<?php

declare(strict_types=1);

namespace App\Services\Stripe\Services;

use App\Services\Stripe\Helpers\StripeClientFactory;
use App\Services\Stripe\Repositories\StripeCustomerRepository;
use Interadigital\CoreModels\Models\User;
use RuntimeException;

class StripeBillingPortalSessionService
{
    public function __construct(
        private readonly StripeClientFactory $stripeClientFactory,
        private readonly StripeCustomerRepository $stripeCustomerRepository
    ) {
    }

    public function createCustomerPortalUrl(User $user, ?string $returnUrl = null): string
    {
        $customer = $this->stripeCustomerRepository->getOrCreate($user);
        $client = $this->stripeClientFactory->make();

        $session = $client->billingPortal->sessions->create([
            'customer' => $customer->id,
            'return_url' => $returnUrl ?? $this->defaultReturnUrl(),
        ]);

        if (! is_string($session->url) || $session->url === '') {
            throw new RuntimeException('Stripe billing portal URL is missing from response.');
        }

        return $session->url;
    }

    private function defaultReturnUrl(): string
    {
        return (string) config(
            'stripe.portal_return_url',
            rtrim((string) env('FRONTEND_URL', 'http://localhost'), '/').'/dashboard'
        );
    }
}

