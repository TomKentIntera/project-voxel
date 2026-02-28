<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerOrderedConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class ServerOrderedConsumerTest extends TestCase
{
    use RefreshDatabase;

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
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'status' => ServerStatus::NEW->value,
        ]);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.ordered.v1', [
                'event_id' => 'evt-ordered-1',
                'occurred_at' => '2026-02-25T00:00:00Z',
                'server_id' => (int) $server->id,
                'server_uuid' => (string) $server->uuid,
                'user_id' => (int) $user->id,
                'plan' => 'panda',
                'config' => [],
            ]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerOrderedConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(1, $processed);
        $server->refresh();
        $this->assertSame(ServerStatus::PROVISIONING->value, $server->status);
        $this->assertDatabaseHas('server_events', [
            'server_id' => $server->id,
            'type' => ServerEventType::SERVER_PROVISIONING_STARTED->value,
        ]);
    }

    public function test_it_routes_server_provisioned_events_to_the_provisioned_consumer(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'status' => ServerStatus::PROVISIONING->value,
            'initialised' => false,
        ]);

        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.provisioned', [
                'event_id' => 'evt-provisioned-1',
                'occurred_at' => '2026-02-25T00:00:00Z',
                'server_id' => (int) $server->id,
            ]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerOrderedConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(1, $processed);
        $server->refresh();
        $this->assertSame(ServerStatus::PROVISIONED->value, $server->status);
        $this->assertTrue((bool) $server->initialised);
        $this->assertDatabaseHas('server_events', [
            'server_id' => $server->id,
            'type' => ServerEventType::SERVER_PROVISIONED->value,
        ]);
    }

    public function test_it_ignores_unmapped_event_types(): void
    {
        Http::fakeSequence()
            ->push($this->receiveMessageResponseXml('server.unknown', [
                'event_id' => 'evt-unknown-1',
                'occurred_at' => '2026-02-25T00:00:00Z',
            ]), 200)
            ->push($this->deleteMessageResponseXml(), 200);

        $consumer = app(ServerOrderedConsumer::class);

        $processed = $consumer->consumeBatch(maxMessages: 1, waitTimeSeconds: 0);

        $this->assertSame(0, $processed);
        $this->assertDatabaseCount('server_events', 0);
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
