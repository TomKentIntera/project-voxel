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

    public function test_it_sends_user_email_and_queues_slack_notification(): void
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

        $server->load('user');

        $dispatcher = app(ServerProvisionedNotificationDispatcher::class);
        $dispatcher->dispatch($server);

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

    public function test_it_skips_slack_when_no_channel_is_configured(): void
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

        $server->load('user');

        $dispatcher = app(ServerProvisionedNotificationDispatcher::class);
        $dispatcher->dispatch($server);

        Mail::assertSent(ServerProvisionedMail::class);
        Queue::assertNothingPushed();
    }
}
