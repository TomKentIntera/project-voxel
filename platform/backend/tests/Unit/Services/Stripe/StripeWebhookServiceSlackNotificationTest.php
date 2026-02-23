<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stripe;

use App\Jobs\SendSlackNotification;
use App\Notifications\Slack\ServerOrderedSlackNotification;
use App\Services\Stripe\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Interadigital\CoreEvents\EventBus\EventBusClient;
use Interadigital\CoreEvents\Events\ServerOrdered;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Mockery;
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

        $service = new StripeWebhookService($eventBusClient);

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
}
