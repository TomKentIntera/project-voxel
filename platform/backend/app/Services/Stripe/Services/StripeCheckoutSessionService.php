<?php

declare(strict_types=1);

namespace App\Services\Stripe\Services;

use App\Services\Stripe\Helpers\StripeClientFactory;
use App\Services\Stripe\Repositories\StripeCustomerRepository;
use App\Services\Stripe\Repositories\StripePlanRepository;
use InvalidArgumentException;
use Interadigital\CoreModels\Models\User;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeCheckoutSessionService
{
    public function __construct(
        private readonly StripeClientFactory $stripeClientFactory,
        private readonly StripePlanRepository $stripePlanRepository,
        private readonly StripeCustomerRepository $stripeCustomerRepository
    ) {
    }

    public function createSubscriptionCheckoutSession(
        User $user,
        string $planName,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
        ?string $promotionCode = null
    ): Session {
        $catalogId = $this->stripePlanRepository->stripeCatalogIdForPlan($planName);

        if ($catalogId === null) {
            throw new InvalidArgumentException("Plan [{$planName}] is not configured for Stripe.");
        }

        $client = $this->stripeClientFactory->make();
        $priceId = $this->resolvePriceId($client, $catalogId);
        $customer = $this->stripeCustomerRepository->getOrCreate($user);

        $payload = [
            'mode' => 'subscription',
            'customer' => $customer->id,
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => $successUrl ?? $this->defaultSuccessUrl(),
            'cancel_url' => $cancelUrl ?? $this->defaultCancelUrl(),
            'metadata' => [
                'plan' => $planName,
                'user_id' => (string) $user->getKey(),
            ],
            'allow_promotion_codes' => true,
        ];

        if (is_string($promotionCode) && $promotionCode !== '') {
            $payload['allow_promotion_codes'] = false;
            $payload['discounts'] = [
                [
                    'promotion_code' => $promotionCode,
                ],
            ];
        }

        return $client->checkout->sessions->create($payload);
    }

    private function resolvePriceId(StripeClient $client, string $catalogId): string
    {
        if (str_starts_with($catalogId, 'price_')) {
            return $catalogId;
        }

        if (! str_starts_with($catalogId, 'prod_')) {
            throw new RuntimeException("Stripe catalog ID [{$catalogId}] is neither a product nor a price.");
        }

        $product = $client->products->retrieve($catalogId, []);
        $defaultPrice = $product->default_price ?? null;

        if (is_string($defaultPrice) && $defaultPrice !== '') {
            return $defaultPrice;
        }

        if (is_object($defaultPrice) && isset($defaultPrice->id) && is_string($defaultPrice->id)) {
            return $defaultPrice->id;
        }

        throw new RuntimeException("Stripe product [{$catalogId}] does not have a default price configured.");
    }

    private function defaultSuccessUrl(): string
    {
        return (string) config(
            'stripe.checkout_success_url',
            rtrim((string) config('app.url', 'http://localhost'), '/')
            .'/billing/success?session_id={CHECKOUT_SESSION_ID}'
        );
    }

    private function defaultCancelUrl(): string
    {
        return (string) config(
            'stripe.checkout_cancel_url',
            rtrim((string) config('app.url', 'http://localhost'), '/').'/billing/cancel'
        );
    }
}
