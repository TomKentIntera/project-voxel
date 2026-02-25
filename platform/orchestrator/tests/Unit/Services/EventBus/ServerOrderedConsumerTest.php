<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerOrderedConsumer;
use App\Services\EventBus\ServerOrderedLifecycleEventConsumer;
use App\Services\EventBus\ServerProvisionedLifecycleEventConsumer;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ServerOrderedConsumerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.event_bus', [
            'server_orders_queue_url' => 'http://localstack:4566/000000000000/server-orders-orchestrator',
            'region' => 'us-east-1',
            'key' => 'test',
            'secret' => 'test',
            'session_token' => null,
        ]);
    }

    public function test_it_routes_server_ordered_events_to_the_server_ordered_consumer(): void
    {
        $orderedConsumer = Mockery::mock(ServerOrderedLifecycleEventConsumer::class);
        $orderedConsumer->shouldReceive('consume')
            ->once()
            ->withArgs(static fn (array $payload): bool => ($payload['event_type'] ?? null) === 'server.ordered.v1');
        $this->app->instance(ServerOrderedLifecycleEventConsumer::class, $orderedConsumer);

        $provisionedConsumer = Mockery::mock(ServerProvisionedLifecycleEventConsumer::class);
        $provisionedConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerProvisionedLifecycleEventConsumer::class, $provisionedConsumer);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.ordered.v1', [
                'event_id' => 'evt-ordered-1',
                'occurred_at' => '2026-02-25T00:00:00Z',
                'server_id' => 10,
                'server_uuid' => 'srv-10',
                'user_id' => 7,
                'plan' => 'panda',
                'config' => [],
            ]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerOrderedConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(1, $processed);
    }

    public function test_it_routes_server_provisioned_events_to_the_provisioned_consumer(): void
    {
        $orderedConsumer = Mockery::mock(ServerOrderedLifecycleEventConsumer::class);
        $orderedConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerOrderedLifecycleEventConsumer::class, $orderedConsumer);

        $provisionedConsumer = Mockery::mock(ServerProvisionedLifecycleEventConsumer::class);
        $provisionedConsumer->shouldReceive('consume')
            ->once()
            ->withArgs(static fn (array $payload): bool => ($payload['event_type'] ?? null) === 'server.provisioned');
        $this->app->instance(ServerProvisionedLifecycleEventConsumer::class, $provisionedConsumer);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.provisioned', [
                'event_id' => 'evt-provisioned-1',
                'occurred_at' => '2026-02-25T00:00:00Z',
                'server_id' => 10,
            ]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerOrderedConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(1, $processed);
    }

    public function test_it_ignores_unmapped_event_types(): void
    {
        $orderedConsumer = Mockery::mock(ServerOrderedLifecycleEventConsumer::class);
        $orderedConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerOrderedLifecycleEventConsumer::class, $orderedConsumer);

        $provisionedConsumer = Mockery::mock(ServerProvisionedLifecycleEventConsumer::class);
        $provisionedConsumer->shouldNotReceive('consume');
        $this->app->instance(ServerProvisionedLifecycleEventConsumer::class, $provisionedConsumer);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.unknown', [
                'event_id' => 'evt-unknown-1',
                'occurred_at' => '2026-02-25T00:00:00Z',
            ]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerOrderedConsumer::class);

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
}
