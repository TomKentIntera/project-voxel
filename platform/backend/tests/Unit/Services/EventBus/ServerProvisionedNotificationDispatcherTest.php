<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Jobs\SendSlackNotification;
use App\Mail\ServerProvisionedMail;
use App\Notifications\Slack\ServerProvisionedSlackNotification;
use App\Services\EventBus\ServerProvisionedNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class ServerProvisionedNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_user_email_and_queues_slack_notification_for_provisioned_servers(): void
    {
        config()->set('slack.channels.servers', 'CSERVERS');
        config()->set('slack.channels.orders', 'CORDERS');

        Mail::fake();
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'player@example.com',
        ]);

        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => 'srv-123',
            'config' => json_encode(['name' => 'Skyblock Realm']),
        ]);

        $dispatcher = app(ServerProvisionedNotificationDispatcher::class);

        $dispatcher->dispatch([
            'event_type' => 'server.provisioned',
            'server_id' => $server->id,
        ]);

        Mail::assertSent(
            ServerProvisionedMail::class,
            static function (ServerProvisionedMail $mail) use ($user, $server): bool {
                return $mail->hasTo($user->email)
                    && $mail->serverId === (int) $server->id
                    && $mail->serverUuid === 'srv-123'
                    && $mail->serverName === 'Skyblock Realm';
            }
        );

        Queue::assertPushed(
            SendSlackNotification::class,
            static function (SendSlackNotification $job) use ($server, $user): bool {
                return $job->notification instanceof ServerProvisionedSlackNotification
                    && $job->notification->channel() === 'CSERVERS'
                    && $job->notification->content() === sprintf(
                        ':white_check_mark: Server provisioned (server_id=%d, server_uuid=srv-123, user_id=%d, user_email=player@example.com, name=Skyblock Realm).',
                        (int) $server->id,
                        (int) $user->id,
                    );
            }
        );
    }

    public function test_it_skips_notifications_when_event_payload_does_not_include_server_id(): void
    {
        Mail::fake();
        Queue::fake();

        $dispatcher = app(ServerProvisionedNotificationDispatcher::class);
        $dispatcher->dispatch([
            'event_type' => 'server.provisioned',
        ]);

        Mail::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_it_still_sends_email_when_slack_channel_is_not_configured(): void
    {
        config()->set('slack.channels.servers', '');
        config()->set('slack.channels.orders', '');

        Mail::fake();
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'player@example.com',
        ]);

        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => 'srv-123',
            'config' => json_encode(['name' => 'Skyblock Realm']),
        ]);

        $dispatcher = app(ServerProvisionedNotificationDispatcher::class);
        $dispatcher->dispatch([
            'event_type' => 'server.provisioned',
            'server_id' => $server->id,
        ]);

        Mail::assertSent(ServerProvisionedMail::class);
        Queue::assertNothingPushed();
    }
}
