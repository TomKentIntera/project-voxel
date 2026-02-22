<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryNodeSample;
use Interadigital\CoreModels\Models\TelemetryServer;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class NodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_node_and_receive_one_time_token(): void
    {
        $token = $this->authenticateAdmin();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/nodes', [
                'name' => 'Frankfurt Wings Node',
                'region' => 'eu.de',
                'ip_address' => '203.0.113.10',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Frankfurt Wings Node')
            ->assertJsonPath('data.region', 'eu.de')
            ->assertJsonPath('data.ip_address', '203.0.113.10')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'region',
                    'ip_address',
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
            'updated_at' => now()->subMinutes(2),
        ]);

        TelemetryNodeSample::query()->create([
            'node_id' => $node->id,
            'cpu_pct' => 41.2,
            'iowait_pct' => 2.4,
            'recorded_at' => now()->subHours(23),
        ]);
        TelemetryNodeSample::query()->create([
            'node_id' => $node->id,
            'cpu_pct' => 59.7,
            'iowait_pct' => 1.8,
            'recorded_at' => now()->subHours(1),
        ]);
        TelemetryNodeSample::query()->create([
            'node_id' => $node->id,
            'cpu_pct' => 77.7,
            'iowait_pct' => 6.6,
            'recorded_at' => now()->subHours(30),
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
        $this->assertCount(2, $samples);

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

        TelemetryNodeSample::query()->create([
            'node_id' => $node->id,
            'cpu_pct' => 52.0,
            'iowait_pct' => 1.1,
            'recorded_at' => now()->subMinutes(10),
        ]);

        TelemetryServer::factory()->create([
            'server_id' => 'delete-me-server',
            'node_id' => $node->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/nodes/'.$node->id)
            ->assertOk()
            ->assertJson([
                'message' => 'Node deleted.',
            ]);

        $this->assertDatabaseMissing('nodes', ['id' => $node->id]);
        $this->assertDatabaseMissing('telemetry_node', ['node_id' => $node->id]);
        $this->assertDatabaseMissing('telemetry_node_sample', ['node_id' => $node->id]);
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
