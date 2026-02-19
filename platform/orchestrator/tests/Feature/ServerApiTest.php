<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class ServerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_servers_with_filters_and_pagination(): void
    {
        $token = $this->authenticateAdmin();

        $ownerA = User::factory()->customer()->create();
        $ownerB = User::factory()->customer()->create();

        Server::factory()->create([
            'user_id' => $ownerA->id,
            'uuid' => 'srv-alpha',
            'plan' => 'rabbit',
            'status' => 'active',
            'config' => json_encode(['name' => 'Alpha Server']),
            'created_at' => now()->subDay(),
        ]);

        $suspendedServer = Server::factory()->create([
            'user_id' => $ownerB->id,
            'uuid' => 'srv-bravo',
            'plan' => 'panda',
            'status' => 'suspended',
            'suspended' => true,
            'config' => json_encode(['name' => 'Bravo Server']),
            'created_at' => now()->subHour(),
        ]);

        ServerEvent::factory()->count(2)->create([
            'server_id' => $suspendedServer->id,
            'actor_id' => $ownerB->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers?status=suspended&per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $suspendedServer->id)
            ->assertJsonPath('data.0.status', 'suspended')
            ->assertJsonPath('data.0.suspended', true)
            ->assertJsonPath('data.0.events_count', 2)
            ->assertJsonPath('data.0.owner.id', $ownerB->id)
            ->assertJsonPath('data.0.owner.email', $ownerB->email);
    }

    public function test_admin_can_view_server_profile_with_timeline_events(): void
    {
        $token = $this->authenticateAdmin();

        $owner = User::factory()->customer()->create();
        $actor = User::factory()->admin()->create();

        $server = Server::factory()->create([
            'user_id' => $owner->id,
            'uuid' => 'srv-profile',
            'plan' => 'panda',
            'status' => 'provisioned',
            'config' => json_encode(['name' => 'RPG Realm']),
            'created_at' => now()->subDays(3),
        ]);

        $olderEvent = ServerEvent::factory()->create([
            'server_id' => $server->id,
            'actor_id' => $actor->id,
            'type' => 'server.provisioned',
            'meta' => ['source' => 'api'],
            'created_at' => now()->subHours(3),
        ]);

        $newerEvent = ServerEvent::factory()->create([
            'server_id' => $server->id,
            'actor_id' => null,
            'type' => 'server.suspended',
            'meta' => ['reason' => 'billing'],
            'created_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/'.$server->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $server->id)
            ->assertJsonPath('data.name', 'RPG Realm')
            ->assertJsonPath('data.owner.id', $owner->id)
            ->assertJsonPath('data.events_count', 2)
            ->assertJsonPath('data.events.0.id', $newerEvent->id)
            ->assertJsonPath('data.events.0.type', 'server.suspended')
            ->assertJsonPath('data.events.0.label', 'Server suspended')
            ->assertJsonPath('data.events.0.actor', null)
            ->assertJsonPath('data.events.1.id', $olderEvent->id)
            ->assertJsonPath('data.events.1.actor.id', $actor->id);
    }

    public function test_show_returns_not_found_for_missing_server(): void
    {
        $token = $this->authenticateAdmin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/999999')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Server not found.',
            ]);
    }

    private function authenticateAdmin(): string
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret1234',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}
