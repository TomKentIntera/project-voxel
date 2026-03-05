<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stripe;

use App\Jobs\SendSlackNotification;
use App\Notifications\Slack\ServerOrderedSlackNotification;
use App\Services\Stripe\Repositories\StripeCustomerRepository;
use App\Services\Stripe\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Interadigital\CoreEvents\EventBus\EventBusClient;
use Interadigital\CoreEvents\Events\ServerOrdered;
use Interadigital\CoreModels\Models\ReferralCode;
use Interadigital\CoreModels\Models\ReferralTransaction;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Mockery;
use Stripe\Customer;
use Tests\TestCase;

class StripeWebhookServiceSlackNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_a_server_ordered_slack_notification_for_new_orders(): void
    {
        Queue::fake();

        $eventBusClient = Mockery::mock(EventBusClient::class);
        $eventBusClient->shouldReceive('publish')
            ->once()
            ->withArgs(static fn (ServerOrdered $event): bool => $event->serverId > 0);
        $stripeCustomerRepository = Mockery::mock(StripeCustomerRepository::class);
        $stripeCustomerRepository->shouldReceive('getOrCreate')->never();
        $stripeCustomerRepository->shouldReceive('applyCredit')->never();

        $service = new StripeWebhookService($eventBusClient, $stripeCustomerRepository);

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'stripe_tx_id' => 'sub_test_123',
            'initialised' => false,
        ]);

        $service->handleEvent([
            'id' => 'evt_test_123',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689600,
            'data' => [
                'object' => [
                    'subscription' => 'sub_test_123',
                ],
            ],
        ]);

        Queue::assertPushed(
            SendSlackNotification::class,
            static function (SendSlackNotification $job) use ($server): bool {
                return $job->notification instanceof ServerOrderedSlackNotification
                    && $job->notification->content() === sprintf(
                        ':package: Server ordered successfully (server_id=%d).',
                        (int) $server->id,
                    );
            }
        );
    }

    public function test_it_links_subscription_to_server_on_checkout_session_completed(): void
    {
        $eventBusClient = Mockery::mock(EventBusClient::class);
        $eventBusClient->shouldReceive('publish')->never();
        $stripeCustomerRepository = Mockery::mock(StripeCustomerRepository::class);
        $stripeCustomerRepository->shouldReceive('getOrCreate')->never();
        $stripeCustomerRepository->shouldReceive('applyCredit')->never();

        $service = new StripeWebhookService($eventBusClient, $stripeCustomerRepository);

        $server = Server::factory()->create([
            'uuid' => 'server-uuid-123',
            'stripe_tx_id' => null,
        ]);

        $service->handleEvent([
            'id' => 'evt_test_checkout_completed',
            'type' => 'checkout.session.completed',
            'created' => 1735689600,
            'data' => [
                'object' => [
                    'subscription' => 'sub_test_checkout_123',
                    'metadata' => [
                        'server_uuid' => 'server-uuid-123',
                    ],
                ],
            ],
        ]);

        $server->refresh();
        $this->assertSame('sub_test_checkout_123', $server->stripe_tx_id);
    }

    public function test_it_applies_referral_credit_for_first_three_paid_invoices_only(): void
    {
        Queue::fake();

        $eventBusClient = Mockery::mock(EventBusClient::class);
        $eventBusClient->shouldReceive('publish')->once();

        $referrer = User::factory()->create([
            'email' => 'referrer@example.com',
            'name' => 'Referrer User',
        ]);
        $buyer = User::factory()->create([
            'email' => 'buyer@example.com',
            'name' => 'Buyer User',
        ]);

        $referralCode = ReferralCode::factory()->create([
            'user_id' => $referrer->id,
            'referral_percent' => 10,
            'valid_for_invoice_count' => 3,
        ]);

        $server = Server::factory()->create([
            'user_id' => $buyer->id,
            'stripe_tx_id' => 'sub_referral_123',
            'referral_id' => $referralCode->id,
            'referral_paid' => false,
            'initialised' => false,
        ]);

        $stripeCustomerRepository = Mockery::mock(StripeCustomerRepository::class);
        $stripeCustomerRepository->shouldReceive('getOrCreate')
            ->times(3)
            ->withArgs(static fn (User $user): bool => (int) $user->id === (int) $referrer->id)
            ->andReturn(new Customer('cus_referrer_123'));
        $stripeCustomerRepository->shouldReceive('applyCredit')
            ->times(3)
            ->with('cus_referrer_123', 1000, 'usd', Mockery::type('string'));

        $service = new StripeWebhookService($eventBusClient, $stripeCustomerRepository);

        $service->handleEvent([
            'id' => 'evt_referral_invoice',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689600,
            'data' => [
                'object' => [
                    'subscription' => 'sub_referral_123',
                    'id' => 'in_referral_1',
                    'amount_paid' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $server->refresh();
        $this->assertFalse((bool) $server->referral_paid);
        $this->assertDatabaseHas('referral_transactions', [
            'user_id' => $referrer->id,
            'server_id' => $server->id,
            'referral_id' => $referralCode->id,
            'amount' => 10.0,
            'stripe_invoice_id' => 'in_referral_1',
        ]);

        $service->handleEvent([
            'id' => 'evt_referral_invoice_2',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689601,
            'data' => [
                'object' => [
                    'subscription' => 'sub_referral_123',
                    'id' => 'in_referral_2',
                    'amount_paid' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $service->handleEvent([
            'id' => 'evt_referral_invoice_3',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689602,
            'data' => [
                'object' => [
                    'subscription' => 'sub_referral_123',
                    'id' => 'in_referral_3',
                    'amount_paid' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        // Duplicate invoice webhook should not double-credit.
        $service->handleEvent([
            'id' => 'evt_referral_invoice_3_duplicate',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689603,
            'data' => [
                'object' => [
                    'subscription' => 'sub_referral_123',
                    'id' => 'in_referral_3',
                    'amount_paid' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        // Fourth unique invoice should not credit because max is 3 invoices.
        $service->handleEvent([
            'id' => 'evt_referral_invoice_4',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689604,
            'data' => [
                'object' => [
                    'subscription' => 'sub_referral_123',
                    'id' => 'in_referral_4',
                    'amount_paid' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $server->refresh();
        $this->assertTrue((bool) $server->referral_paid);
        $this->assertSame(
            3,
            ReferralTransaction::query()->where('server_id', $server->id)->count()
        );
    }

    public function test_it_caps_referral_credits_per_server_across_multiple_servers(): void
    {
        Queue::fake();

        $eventBusClient = Mockery::mock(EventBusClient::class);
        $eventBusClient->shouldReceive('publish')->never();

        $referrer = User::factory()->create([
            'email' => 'referrer-multi@example.com',
            'name' => 'Referrer Multi',
        ]);
        $buyer = User::factory()->create([
            'email' => 'buyer-multi@example.com',
            'name' => 'Buyer Multi',
        ]);

        $referralCode = ReferralCode::factory()->create([
            'user_id' => $referrer->id,
            'referral_percent' => 10,
            'valid_for_invoice_count' => 3,
        ]);

        $serverA = Server::factory()->create([
            'user_id' => $buyer->id,
            'stripe_tx_id' => 'sub_referral_multi_a',
            'referral_id' => $referralCode->id,
            'referral_paid' => false,
            'initialised' => true,
        ]);
        $serverB = Server::factory()->create([
            'user_id' => $buyer->id,
            'stripe_tx_id' => 'sub_referral_multi_b',
            'referral_id' => $referralCode->id,
            'referral_paid' => false,
            'initialised' => true,
        ]);

        $stripeCustomerRepository = Mockery::mock(StripeCustomerRepository::class);
        $stripeCustomerRepository->shouldReceive('getOrCreate')
            ->times(4)
            ->withArgs(static fn (User $user): bool => (int) $user->id === (int) $referrer->id)
            ->andReturn(new Customer('cus_referrer_multi_123'));
        $stripeCustomerRepository->shouldReceive('applyCredit')
            ->times(4)
            ->with('cus_referrer_multi_123', 1000, 'usd', Mockery::type('string'));

        $service = new StripeWebhookService($eventBusClient, $stripeCustomerRepository);

        $events = [
            ['subscription' => 'sub_referral_multi_a', 'invoice_id' => 'in_multi_1'],
            ['subscription' => 'sub_referral_multi_b', 'invoice_id' => 'in_multi_2'],
            ['subscription' => 'sub_referral_multi_b', 'invoice_id' => 'in_multi_3'],
            // Should credit because cap is per server, not per user.
            ['subscription' => 'sub_referral_multi_a', 'invoice_id' => 'in_multi_4'],
        ];

        foreach ($events as $index => $event) {
            $service->handleEvent([
                'id' => 'evt_multi_'.$index,
                'type' => 'invoice.payment_succeeded',
                'created' => 1735689700 + $index,
                'data' => [
                    'object' => [
                        'subscription' => $event['subscription'],
                        'id' => $event['invoice_id'],
                        'amount_paid' => 10000,
                        'currency' => 'usd',
                    ],
                ],
            ]);
        }

        $this->assertSame(
            4,
            ReferralTransaction::query()
                ->where('referral_id', $referralCode->id)
                ->count()
        );

        $serverA->refresh();
        $this->assertFalse((bool) $serverA->referral_paid);

        $this->assertSame(
            2,
            ReferralTransaction::query()->where('server_id', $serverA->id)->count()
        );
        $this->assertSame(
            2,
            ReferralTransaction::query()->where('server_id', $serverB->id)->count()
        );
    }
}
