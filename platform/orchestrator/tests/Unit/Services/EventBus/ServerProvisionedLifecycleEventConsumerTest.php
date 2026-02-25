<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EventBus;

use App\Services\EventBus\ServerProvisionedLifecycleEventConsumer;
use App\Services\EventBus\ServerProvisionedNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Interadigital\CoreModels\Models\User;
use Mockery;
use Tests\TestCase;

class ServerProvisionedLifecycleEventConsumerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_server_provisioned_records_event_and_dispatches_notifications(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'status' => ServerStatus::PROVISIONING->value,
            'initialised' => false,
        ]);

        $dispatcher = Mockery::mock(ServerProvisionedNotificationDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(static fn (Server $resolved): bool => (int) $resolved->id === (int) $server->id);

        $consumer = new ServerProvisionedLifecycleEventConsumer($dispatcher);

        $consumer->consume([
            'event_id' => 'evt-provisioned-1',
            'event_type' => 'server.provisioned',
            'occurred_at' => '2026-02-25T00:00:00Z',
            'server_id' => (int) $server->id,
        ]);

        $server->refresh();

        $this->assertSame(ServerStatus::PROVISIONED->value, $server->status);
        $this->assertTrue((bool) $server->initialised);

        $this->assertDatabaseHas('server_events', [
            'server_id' => $server->id,
            'type' => ServerEventType::SERVER_PROVISIONED->value,
        ]);
    }

    public function test_it_is_idempotent_for_duplicate_event_ids(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'status' => ServerStatus::PROVISIONING->value,
            'initialised' => false,
        ]);

        ServerEvent::factory()->create([
            'server_id' => $server->id,
            'actor_id' => null,
            'type' => ServerEventType::SERVER_PROVISIONED->value,
            'meta' => [
                'event_id' => 'evt-provisioned-1',
            ],
        ]);

        $dispatcher = Mockery::mock(ServerProvisionedNotificationDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $consumer = new ServerProvisionedLifecycleEventConsumer($dispatcher);

        $consumer->consume([
            'event_id' => 'evt-provisioned-1',
            'event_type' => 'server.provisioned',
            'server_id' => (int) $server->id,
        ]);

        $this->assertSame(
            1,
            ServerEvent::query()
                ->where('server_id', $server->id)
                ->where('type', ServerEventType::SERVER_PROVISIONED->value)
                ->count(),
        );
    }
}
