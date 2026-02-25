<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerLifecycleCacheInvalidationEventConsumer;
use App\Services\LocationsCacheReader;
use Mockery;
use Tests\TestCase;

class ServerLifecycleCacheInvalidationEventConsumerTest extends TestCase
{
    public function test_it_invalidates_the_locations_cache(): void
    {
        $reader = Mockery::mock(LocationsCacheReader::class);
        $reader->shouldReceive('forgetCachedPayload')->once();

        $consumer = new ServerLifecycleCacheInvalidationEventConsumer($reader);

        $consumer->consume([
            'event_type' => 'server.provisioned',
            'server_id' => 123,
        ]);
    }
}
