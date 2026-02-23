<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications\Slack;

use App\Notifications\Slack\ServerOrderedSlackNotification;
use Tests\TestCase;

class ServerOrderedSlackNotificationTest extends TestCase
{
    public function test_it_uses_the_server_ordered_channel_from_configuration(): void
    {
        config()->set('services.slack.notifications.channels.server_ordered', 'CSERVERORDERED');
        config()->set('services.slack.notifications.channel', 'CDEFAULT');

        $notification = new ServerOrderedSlackNotification(42);

        $this->assertSame('CSERVERORDERED', $notification->channel());
    }

    public function test_it_falls_back_to_default_channel_when_server_ordered_channel_is_missing(): void
    {
        config()->set('services.slack.notifications.channels.server_ordered', '');
        config()->set('services.slack.notifications.channel', 'CDEFAULT');

        $notification = new ServerOrderedSlackNotification(42);

        $this->assertSame('CDEFAULT', $notification->channel());
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
