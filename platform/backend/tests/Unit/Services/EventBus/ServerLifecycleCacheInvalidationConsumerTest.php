<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerLifecycleCacheInvalidationConsumer;
use App\Services\EventBus\ServerLifecycleCacheInvalidationEventConsumer;
use App\Services\EventBus\ServerProvisionedNotificationEventConsumer;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ServerLifecycleCacheInvalidationConsumerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.event_bus', [
            'server_lifecycle_queue_url' => 'http://localstack:4566/000000000000/server-lifecycle-backend',
            'region' => 'us-east-1',
            'key' => 'test',
            'secret' => 'test',
            'session_token' => null,
        ]);
    }

    public function test_it_invalidates_locations_cache_for_supported_event_types(): void
    {
        $cacheInvalidationConsumer = Mockery::mock(ServerLifecycleCacheInvalidationEventConsumer::class);
        $cacheInvalidationConsumer->shouldReceive('consume')
            ->once()
            ->withArgs(static function (array $payload): bool {
                return ($payload['event_type'] ?? null) === 'server.provisioned'
                    && ($payload['server_id'] ?? null) === 123;
            });
        $this->app->instance(ServerLifecycleCacheInvalidationEventConsumer::class, $cacheInvalidationConsumer);

        $provisionedNotificationConsumer = Mockery::mock(ServerProvisionedNotificationEventConsumer::class);
        $provisionedNotificationConsumer->shouldReceive('consume')
            ->once()
            ->withArgs(static function (array $payload): bool {
                return ($payload['event_type'] ?? null) === 'server.provisioned'
                    && ($payload['server_id'] ?? null) === 123;
            });
        $this->app->instance(ServerProvisionedNotificationEventConsumer::class, $provisionedNotificationConsumer);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.provisioned', ['server_id' => 123]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerLifecycleCacheInvalidationConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(1, $processed);
    }

    public function test_it_ignores_non_lifecycle_events(): void
    {
        $cacheInvalidationConsumer = Mockery::mock(ServerLifecycleCacheInvalidationEventConsumer::class);
        $cacheInvalidationConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerLifecycleCacheInvalidationEventConsumer::class, $cacheInvalidationConsumer);

        $provisionedNotificationConsumer = Mockery::mock(ServerProvisionedNotificationEventConsumer::class);
        $provisionedNotificationConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerProvisionedNotificationEventConsumer::class, $provisionedNotificationConsumer);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.ordered.v1'), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerLifecycleCacheInvalidationConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(0, $processed);
    }

    public function test_it_fans_out_to_only_cache_invalidation_for_migrated_events(): void
    {
        $cacheInvalidationConsumer = Mockery::mock(ServerLifecycleCacheInvalidationEventConsumer::class);
        $cacheInvalidationConsumer->shouldReceive('consume')
            ->once()
            ->withArgs(static function (array $payload): bool {
                return ($payload['event_type'] ?? null) === 'server.migrated'
                    && ($payload['server_id'] ?? null) === 456;
            });
        $this->app->instance(ServerLifecycleCacheInvalidationEventConsumer::class, $cacheInvalidationConsumer);

        $provisionedNotificationConsumer = Mockery::mock(ServerProvisionedNotificationEventConsumer::class);
        $provisionedNotificationConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerProvisionedNotificationEventConsumer::class, $provisionedNotificationConsumer);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.migrated', ['server_id' => 456]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerLifecycleCacheInvalidationConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(1, $processed);
    }

    public function test_it_skips_missing_queue_errors_without_failing_the_consumer_loop(): void
    {
        $cacheInvalidationConsumer = Mockery::mock(ServerLifecycleCacheInvalidationEventConsumer::class);
        $cacheInvalidationConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerLifecycleCacheInvalidationEventConsumer::class, $cacheInvalidationConsumer);

        $provisionedNotificationConsumer = Mockery::mock(ServerProvisionedNotificationEventConsumer::class);
        $provisionedNotificationConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerProvisionedNotificationEventConsumer::class, $provisionedNotificationConsumer);

        Http::fakeSequence()
            ->push($this->nonExistentQueueErrorXml(), 400);

        $consumer = app(ServerLifecycleCacheInvalidationConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(0, $processed);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function receiveMessageResponseXml(string $eventType, array $payload = []): string
    {
        $payload = json_encode([
            ...$payload,
            'event_type' => $eventType,
        ], JSON_THROW_ON_ERROR);

        return <<<XML
<?xml version="1.0"?>
<ReceiveMessageResponse>
  <ReceiveMessageResult>
    <Message>
      <MessageId>msg-1</MessageId>
      <ReceiptHandle>receipt-1</ReceiptHandle>
      <Body><![CDATA[$payload]]></Body>
    </Message>
  </ReceiveMessageResult>
</ReceiveMessageResponse>
XML;
    }

    private function deleteMessageResponseXml(): string
    {
        return <<<XML
<?xml version="1.0"?>
<DeleteMessageResponse>
  <DeleteMessageResult />
</DeleteMessageResponse>
XML;
    }

    private function nonExistentQueueErrorXml(): string
    {
        return <<<XML
<?xml version='1.0' encoding='utf-8'?>
<ErrorResponse xmlns="http://queue.amazonaws.com/doc/2012-11-05/"><Error><Code>AWS.SimpleQueueService.NonExistentQueue</Code><Message>The specified queue does not exist for this wsdl version.</Message><Type>Sender</Type></Error><RequestId>test-request-id</RequestId></ErrorResponse>
XML;
    }
}
