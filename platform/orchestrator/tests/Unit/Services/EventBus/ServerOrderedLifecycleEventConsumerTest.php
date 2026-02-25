<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerOrderedLifecycleEventConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreEvents\Events\ServerOrdered;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class ServerOrderedLifecycleEventConsumerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_server_as_provisioning_and_records_event(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => 'srv-ordered-1',
            'status' => ServerStatus::NEW->value,
        ]);

        $consumer = new ServerOrderedLifecycleEventConsumer();

        $consumer->consume([
            'event_id' => 'evt-ordered-1',
            'event_type' => ServerOrdered::eventType(),
            'occurred_at' => '2026-02-25T00:00:00Z',
            'correlation_id' => 'sub-123',
            'server_id' => (int) $server->id,
            'server_uuid' => 'srv-ordered-1',
            'user_id' => (int) $user->id,
            'plan' => 'panda',
            'config' => ['name' => 'Skyblock Realm'],
            'stripe_subscription_id' => 'sub-123',
        ]);

        $server->refresh();

        $this->assertSame(ServerStatus::PROVISIONING->value, $server->status);
        $this->assertDatabaseHas('server_events', [
            'server_id' => $server->id,
            'type' => ServerEventType::SERVER_PROVISIONING_STARTED->value,
        ]);
    }

    public function test_it_is_idempotent_for_duplicate_event_ids(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => 'srv-ordered-1',
            'status' => ServerStatus::NEW->value,
        ]);

        ServerEvent::factory()->create([
            'server_id' => $server->id,
            'actor_id' => null,
            'type' => ServerEventType::SERVER_PROVISIONING_STARTED->value,
            'meta' => [
                'event_id' => 'evt-ordered-1',
            ],
        ]);

        $consumer = new ServerOrderedLifecycleEventConsumer();

        $consumer->consume([
            'event_id' => 'evt-ordered-1',
            'event_type' => ServerOrdered::eventType(),
            'occurred_at' => '2026-02-25T00:00:00Z',
            'correlation_id' => 'sub-123',
            'server_id' => (int) $server->id,
            'server_uuid' => 'srv-ordered-1',
            'user_id' => (int) $user->id,
            'plan' => 'panda',
            'config' => ['name' => 'Skyblock Realm'],
            'stripe_subscription_id' => 'sub-123',
        ]);

        $this->assertSame(
            1,
            ServerEvent::query()
                ->where('server_id', $server->id)
                ->where('type', ServerEventType::SERVER_PROVISIONING_STARTED->value)
                ->count(),
        );
    }
}
