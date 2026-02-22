<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreAuth\Services\JwtService;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Interadigital\CoreModels\Models\TelemetryServer;
use Interadigital\CoreModels\Models\TelemetryServerSample;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class ServerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_data_endpoints_require_a_valid_jwt(): void
    {
        $this->getJson('/api/servers')->assertUnauthorized();
        $this->getJson('/api/servers/1')->assertUnauthorized();
        $this->getJson('/api/metrics')->assertUnauthorized();
        $this->getJson('/api/users')->assertUnauthorized();
        $this->getJson('/api/users/1')->assertUnauthorized();
    }

    public function test_non_admin_jwt_cannot_access_admin_data_endpoints(): void
    {
        $customer = User::factory()->customer()->create();
        $token = app(JwtService::class)->issueToken($customer);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/1')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/metrics')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/1')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);
    }

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

        TelemetryServer::factory()->create([
            'server_id' => $server->uuid,
            'node_id' => 'node-eu-1',
            'players_online' => 15,
            'cpu_pct' => 47.25,
            'io_write_bytes_per_s' => 1500.0,
            'updated_at' => now()->subMinutes(2),
        ]);

        TelemetryServerSample::query()->create([
            'server_id' => $server->uuid,
            'node_id' => 'node-eu-1',
            'players_online' => 13,
            'cpu_pct' => 41.1,
            'io_write_bytes_per_s' => 1200.0,
            'recorded_at' => now()->subHours(4),
        ]);
        TelemetryServerSample::query()->create([
            'server_id' => $server->uuid,
            'node_id' => 'node-eu-1',
            'players_online' => 17,
            'cpu_pct' => 53.4,
            'io_write_bytes_per_s' => 1800.0,
            'recorded_at' => now()->subMinutes(45),
        ]);
        TelemetryServerSample::query()->create([
            'server_id' => $server->uuid,
            'node_id' => 'node-eu-1',
            'players_online' => 3,
            'cpu_pct' => 8.8,
            'io_write_bytes_per_s' => 250.0,
            'recorded_at' => now()->subHours(30),
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
            ->assertJsonPath('data.events.1.actor.id', $actor->id)
            ->assertJsonPath('data.performance_last_24h.latest.players_online', 15)
            ->assertJsonPath('data.performance_last_24h.latest.cpu_pct', 47.25)
            ->assertJsonPath('data.performance_last_24h.latest.io_write_bytes_per_s', 1500)
            ->assertJsonPath('data.performance_last_24h.latest.node_id', 'node-eu-1');

        /** @var list<array<string, mixed>> $samples */
        $samples = $response->json('data.performance_last_24h.samples');
        $this->assertCount(2, $samples);
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
