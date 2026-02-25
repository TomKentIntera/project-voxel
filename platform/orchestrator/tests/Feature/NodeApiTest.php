<?php

namespace Tests\Feature;

use App\Jobs\SyncNodeToPterodactylJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class NodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_node_and_receive_one_time_token(): void
    {
        config()->set('services.pterodactyl', [
            'base_url' => 'https://panel.example.test',
            'application_api_key' => 'test-app-api-key',
            'client_api_key' => 'test-client-api-key',
            'timeout' => 15,
        ]);

        $token = $this->authenticateAdmin();
        Queue::fake();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/nodes', [
                'name' => 'Frankfurt Wings Node',
                'region' => 'eu.de',
                'ip_address' => '203.0.113.10',
                'ptero_location_id' => 1,
                'fqdn' => 'wings-eu-de.example.test',
                'scheme' => 'https',
                'behind_proxy' => true,
                'memory' => 32768,
                'memory_overallocate' => 0,
                'disk' => 204800,
                'disk_overallocate' => 0,
                'upload_size' => 500,
                'daemon_sftp' => 2022,
                'daemon_listen' => 8080,
                'allocation_ip' => '203.0.113.10',
                'allocation_alias' => 'frankfurt-main',
                'allocation_ports' => ['25565-25570', 30500],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Frankfurt Wings Node')
            ->assertJsonPath('data.region', 'eu.de')
            ->assertJsonPath('data.ip_address', '203.0.113.10')
            ->assertJsonPath('data.ptero_location_id', 1)
            ->assertJsonPath('data.fqdn', 'wings-eu-de.example.test')
            ->assertJsonPath('data.sync_status', Node::SYNC_STATUS_PENDING)
            ->assertJsonPath('data.allocation_ports.0', '25565-25570')
            ->assertJsonPath('data.allocation_ports.1', '30500')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'region',
                    'ip_address',
                    'ptero_location_id',
                    'fqdn',
                    'scheme',
                    'behind_proxy',
                    'maintenance_mode',
                    'memory',
                    'memory_overallocate',
                    'disk',
                    'disk_overallocate',
                    'upload_size',
                    'daemon_sftp',
                    'daemon_listen',
                    'allocation_ip',
                    'allocation_alias',
                    'allocation_ports',
                    'sync_status',
                    'sync_error',
                    'synced_at',
                    'last_active_at',
                    'last_used_at',
                    'created_at',
                    'updated_at',
                    'node_token',
                ],
            ]);

        $nodeId = (string) $response->json('data.id');
        $rawToken = (string) $response->json('data.node_token');
        $this->assertNotSame('', $rawToken);

        $node = Node::find($nodeId);
        $this->assertNotNull($node);
        $this->assertNotSame($rawToken, $node->token_hash);
        $this->assertTrue($node->matchesToken($rawToken));
        $this->assertSame(1, $node->ptero_location_id);
        $this->assertSame('wings-eu-de.example.test', $node->fqdn);
        $this->assertSame(['25565-25570', '30500'], $node->allocation_ports);
        $this->assertSame(Node::SYNC_STATUS_PENDING, $node->sync_status);

        Queue::assertPushed(SyncNodeToPterodactylJob::class, 1);
    }

    public function test_admin_can_list_nodes_without_exposing_token_hash(): void
    {
        $token = $this->authenticateAdmin();

        Node::factory()->create([
            'id' => 'node-1',
            'name' => 'Node One',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.11',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/nodes?search=Node&per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', 'node-1')
            ->assertJsonPath('data.0.name', 'Node One')
            ->assertJsonPath('data.0.region', 'eu.de')
            ->assertJsonPath('data.0.ip_address', '203.0.113.11')
            ->assertJsonMissingPath('data.0.token_hash');
    }

    public function test_show_returns_not_found_for_missing_node(): void
    {
        $token = $this->authenticateAdmin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/nodes/missing-node')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Node not found.',
            ]);
    }

    public function test_admin_can_view_node_profile_with_24_hour_performance_and_servers(): void
    {
        $token = $this->authenticateAdmin();

        $node = Node::factory()->create([
            'id' => 'node-profile-1',
            'name' => 'Node Profile',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.12',
        ]);

        TelemetryNode::factory()->create([
            'node_id' => $node->id,
            'cpu_pct' => 66.8,
            'iowait_pct' => 3.2,
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);
        TelemetryNode::factory()->create([
            'node_id' => $node->id,
            'cpu_pct' => 41.2,
            'iowait_pct' => 2.4,
            'created_at' => now()->subHours(23),
            'updated_at' => now()->subHours(23),
        ]);
        TelemetryNode::factory()->create([
            'node_id' => $node->id,
            'cpu_pct' => 59.7,
            'iowait_pct' => 1.8,
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);
        TelemetryNode::factory()->create([
            'node_id' => $node->id,
            'cpu_pct' => 77.7,
            'iowait_pct' => 6.6,
            'created_at' => now()->subHours(30),
            'updated_at' => now()->subHours(30),
        ]);

        $owner = User::factory()->customer()->create();

        $linkedServer = Server::factory()->create([
            'user_id' => $owner->id,
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'status' => 'active',
            'plan' => 'panda',
            'config' => json_encode(['name' => 'Chronicles Realm']),
        ]);

        TelemetryServer::factory()->create([
            'server_id' => $linkedServer->uuid,
            'node_id' => $node->id,
            'players_online' => 12,
            'cpu_pct' => 24.5,
            'io_write_bytes_per_s' => 1024.0,
            'updated_at' => now()->subMinutes(3),
        ]);

        TelemetryServer::factory()->create([
            'server_id' => 'external-server-1',
            'node_id' => $node->id,
            'players_online' => 2,
            'cpu_pct' => 7.4,
            'io_write_bytes_per_s' => 256.0,
            'updated_at' => now()->subMinutes(5),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/nodes/'.$node->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $node->id)
            ->assertJsonPath('data.name', 'Node Profile')
            ->assertJsonPath('data.performance_last_24h.latest.cpu_pct', 66.8)
            ->assertJsonPath('data.performance_last_24h.latest.iowait_pct', 3.2)
            ->assertJsonPath('data.servers_count', 2);

        /** @var list<array<string, mixed>> $samples */
        $samples = $response->json('data.performance_last_24h.samples');
        $this->assertCount(3, $samples);

        /** @var list<array<string, mixed>> $servers */
        $servers = $response->json('data.servers');
        $this->assertCount(2, $servers);

        $linkedTelemetry = collect($servers)->firstWhere('server_id', $linkedServer->uuid);
        $this->assertIsArray($linkedTelemetry);
        $this->assertSame($linkedServer->id, data_get($linkedTelemetry, 'server.id'));
        $this->assertSame('Chronicles Realm', data_get($linkedTelemetry, 'server.name'));
        $this->assertSame($owner->id, data_get($linkedTelemetry, 'server.owner.id'));

        $unlinkedTelemetry = collect($servers)->firstWhere('server_id', 'external-server-1');
        $this->assertIsArray($unlinkedTelemetry);
        $this->assertNull(data_get($unlinkedTelemetry, 'server'));
    }

    public function test_admin_can_delete_node_and_associated_telemetry_rows(): void
    {
        $token = $this->authenticateAdmin();

        $node = Node::factory()->create([
            'id' => 'node-delete-1',
        ]);

        TelemetryNode::factory()->create([
            'node_id' => $node->id,
        ]);

        TelemetryServer::factory()->create([
            'server_id' => 'delete-me-server',
            'node_id' => $node->id,
            'players_online' => 3,
            'cpu_pct' => 20.5,
            'io_write_bytes_per_s' => 1550.0,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/nodes/'.$node->id)
            ->assertOk()
            ->assertJson([
                'message' => 'Node deleted.',
            ]);

        $this->assertDatabaseMissing('nodes', ['id' => $node->id]);
        $this->assertDatabaseMissing('telemetry_node', ['node_id' => $node->id]);
        $this->assertDatabaseMissing('telemetry_server', ['node_id' => $node->id]);
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
