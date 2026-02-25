<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications\Slack;

use App\Notifications\Slack\ServerProvisionedSlackNotification;
use Tests\TestCase;

class ServerProvisionedSlackNotificationTest extends TestCase
{
    public function test_it_uses_the_servers_channel_from_configuration(): void
    {
        config()->set('slack.channels.servers', 'CSERVERS');
        config()->set('slack.channels.orders', 'CORDERS');

        $notification = new ServerProvisionedSlackNotification(
            serverId: 42,
            serverUuid: 'srv-42',
            userId: 7,
            userEmail: 'player@example.com',
            serverName: 'Skyblock Realm',
        );

        $this->assertSame('CSERVERS', $notification->channel());
    }

    public function test_it_falls_back_to_orders_channel_when_servers_channel_is_missing(): void
    {
        config()->set('slack.channels.servers', '');
        config()->set('slack.channels.orders', 'CORDERS');

        $notification = new ServerProvisionedSlackNotification(
            serverId: 42,
            serverUuid: 'srv-42',
            userId: 7,
            userEmail: 'player@example.com',
            serverName: 'Skyblock Realm',
        );

        $this->assertSame('CORDERS', $notification->channel());
    }

    public function test_it_formats_content_with_server_and_user_context(): void
    {
        $notification = new ServerProvisionedSlackNotification(
            serverId: 42,
            serverUuid: 'srv-42',
            userId: 7,
            userEmail: 'player@example.com',
            serverName: 'Skyblock Realm',
        );

        $this->assertSame(
            ':white_check_mark: Server provisioned (server_id=42, server_uuid=srv-42, user_id=7, user_email=player@example.com, name=Skyblock Realm).',
            $notification->content(),
        );
    }
}
