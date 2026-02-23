<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications\Slack;

use App\Notifications\Slack\ServerOrderedSlackNotification;
use Tests\TestCase;

class ServerOrderedSlackNotificationTest extends TestCase
{
    public function test_it_uses_the_orders_channel_from_configuration(): void
    {
        config()->set('slack.channels.orders', 'CORDERS');
        config()->set('slack.channels.servers', 'CSERVERS');

        $notification = new ServerOrderedSlackNotification(42);

        $this->assertSame('CORDERS', $notification->channel());
    }

    public function test_it_falls_back_to_servers_channel_when_orders_channel_is_missing(): void
    {
        config()->set('slack.channels.orders', '');
        config()->set('slack.channels.servers', 'CSERVERS');

        $notification = new ServerOrderedSlackNotification(42);

        $this->assertSame('CSERVERS', $notification->channel());
    }

    public function test_it_formats_content_with_server_id(): void
    {
        $notification = new ServerOrderedSlackNotification(42);

        $this->assertSame(
            ':package: Server ordered successfully (server_id=42).',
            $notification->content(),
        );
    }
}
