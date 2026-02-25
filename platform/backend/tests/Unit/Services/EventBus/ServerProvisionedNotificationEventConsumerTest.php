<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerProvisionedNotificationDispatcher;
use App\Services\EventBus\ServerProvisionedNotificationEventConsumer;
use Mockery;
use Tests\TestCase;

class ServerProvisionedNotificationEventConsumerTest extends TestCase
{
    public function test_it_delegates_to_the_provisioned_notification_dispatcher(): void
    {
        $payload = [
            'event_type' => 'server.provisioned',
            'server_id' => 123,
        ];

        $dispatcher = Mockery::mock(ServerProvisionedNotificationDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with($payload);

        $consumer = new ServerProvisionedNotificationEventConsumer($dispatcher);

        $consumer->consume($payload);
    }
}
