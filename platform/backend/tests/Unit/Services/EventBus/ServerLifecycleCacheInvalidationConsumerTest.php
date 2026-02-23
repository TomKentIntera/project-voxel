<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerLifecycleCacheInvalidationConsumer;
use App\Services\LocationsCacheReader;
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
        $reader = Mockery::mock(LocationsCacheReader::class);
        $reader->shouldReceive('forgetCachedPayload')->once();
        $this->app->instance(LocationsCacheReader::class, $reader);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.provisioned'), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerLifecycleCacheInvalidationConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(1, $processed);
    }

    public function test_it_ignores_non_lifecycle_events(): void
    {
        $reader = Mockery::mock(LocationsCacheReader::class);
        $reader->shouldNotReceive('forgetCachedPayload');
        $this->app->instance(LocationsCacheReader::class, $reader);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.ordered.v1'), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerLifecycleCacheInvalidationConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(0, $processed);
    }

    private function receiveMessageResponseXml(string $eventType): string
    {
        $payload = json_encode(['event_type' => $eventType], JSON_THROW_ON_ERROR);

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
