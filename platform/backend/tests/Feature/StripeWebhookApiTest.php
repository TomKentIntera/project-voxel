<?php

namespace Tests\Feature;

use App\Services\Stripe\Services\StripeWebhookService;
use Mockery\MockInterface;
use Tests\TestCase;

class StripeWebhookApiTest extends TestCase
{
    public function test_it_accepts_a_valid_signed_stripe_webhook(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'subscription' => 'sub_test_123',
                    'metadata' => [
                        'server_uuid' => 'server-uuid',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $v1 = hash_hmac('sha256', $signedPayload, 'whsec_test');
        $signature = "t={$timestamp},v1={$v1}";

        $this->mock(StripeWebhookService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('handleEvent')->once();
        });

        $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Stripe-Signature' => $signature,
            ],
            $payload
        )
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);
    }

    public function test_it_rejects_webhook_with_invalid_signature(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Stripe-Signature' => 'invalid',
            ],
            $payload
        )
            ->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid Stripe webhook payload.',
            ]);
    }
}

