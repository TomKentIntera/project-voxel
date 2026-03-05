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

    public function test_it_applies_referral_credit_and_records_ledger_once(): void
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
            'referral_percent' => 15,
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
            ->once()
            ->withArgs(static fn (User $user): bool => (int) $user->id === (int) $referrer->id)
            ->andReturn(new Customer('cus_referrer_123'));
        $stripeCustomerRepository->shouldReceive('applyCredit')
            ->once()
            ->with('cus_referrer_123', 1500, 'usd', Mockery::type('string'));

        $service = new StripeWebhookService($eventBusClient, $stripeCustomerRepository);

        $service->handleEvent([
            'id' => 'evt_referral_invoice',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689600,
            'data' => [
                'object' => [
                    'subscription' => 'sub_referral_123',
                    'amount_paid' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $server->refresh();
        $this->assertTrue((bool) $server->referral_paid);
        $this->assertDatabaseHas('referral_transactions', [
            'user_id' => $referrer->id,
            'server_id' => $server->id,
            'referral_id' => $referralCode->id,
            'amount' => 15.0,
        ]);

        $service->handleEvent([
            'id' => 'evt_referral_invoice_repeat',
            'type' => 'invoice.payment_succeeded',
            'created' => 1735689601,
            'data' => [
                'object' => [
                    'subscription' => 'sub_referral_123',
                    'amount_paid' => 10000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            ReferralTransaction::query()->where('server_id', $server->id)->count()
        );
    }
}
