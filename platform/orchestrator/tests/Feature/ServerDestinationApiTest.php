<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class ServerDestinationApiTest extends TestCase
{
    use RefreshDatabase;

    private string $localCachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localCachePath = storage_path('app/locations.json');
        File::delete($this->localCachePath);
    }

    protected function tearDown(): void
    {
        File::delete($this->localCachePath);

        parent::tearDown();
    }

    public function test_it_resolves_best_destination_within_requested_region(): void
    {
        $token = $this->authenticateAdmin();

        Node::factory()->create([
            'id' => 'node-eu-1',
            'name' => 'DE-Node-1',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.31',
        ]);

        Node::factory()->create([
            'id' => 'node-eu-2',
            'name' => 'DE-Node-2',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.32',
        ]);

        Node::factory()->create([
            'id' => 'node-fi-1',
            'name' => 'FI-Node-1',
            'region' => 'eu.fi',
            'ip_address' => '203.0.113.33',
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-eu-1',
            'cpu_pct' => 50.0,
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(4),
        ]);
        TelemetryNode::factory()->create([
            'node_id' => 'node-eu-1',
            'cpu_pct' => 60.0,
            'created_at' => now()->subMinutes(40),
            'updated_at' => now()->subMinutes(40),
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-eu-2',
            'cpu_pct' => 20.0,
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);
        TelemetryNode::factory()->create([
            'node_id' => 'node-eu-2',
            'cpu_pct' => 24.0,
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-fi-1',
            'cpu_pct' => 2.0,
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
        ]);

        $this->writeLocationsCache([
            [
                'id' => 11,
                'name' => 'DE-Node-1',
                'fqdn' => 'de-node-1.example.com',
                'location' => 'eu.de',
                'memoryFree' => 12288,
            ],
            [
                'id' => 12,
                'name' => 'DE-Node-2',
                'fqdn' => 'de-node-2.example.com',
                'location' => 'eu.de',
                'memoryFree' => 8192,
            ],
            [
                'id' => 13,
                'name' => 'FI-Node-1',
                'fqdn' => 'fi-node-1.example.com',
                'location' => 'eu.fi',
                'memoryFree' => 16384,
            ],
        ], [
            ['short' => 'eu.de', 'maxFreeMemory' => 12288],
            ['short' => 'eu.fi', 'maxFreeMemory' => 16384],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/destinations/resolve', [
                'plan' => 'panda',
                'region' => 'eu.de',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.destination.id', 'node-eu-2')
            ->assertJsonPath('data.destination.region', 'eu.de')
            ->assertJsonPath('data.filters.requested_region', 'eu.de')
            ->assertJsonPath('data.filters.effective_regions.0', 'eu.de');

        $this->assertSame(22.0, (float) $response->json('data.destination.average_cpu_pct_24h'));

        /** @var list<array<string, mixed>> $candidates */
        $candidates = $response->json('data.candidates');

        $this->assertCount(2, $candidates);
        $this->assertSame(['node-eu-2', 'node-eu-1'], array_column($candidates, 'id'));
        $this->assertSame(['eu.de', 'eu.de'], array_column($candidates, 'region'));
    }

    public function test_it_excludes_the_current_server_node_when_server_id_is_provided(): void
    {
        $token = $this->authenticateAdmin();

        Node::factory()->create([
            'id' => 'node-eu-1',
            'name' => 'DE-Node-1',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.41',
        ]);

        Node::factory()->create([
            'id' => 'node-eu-2',
            'name' => 'DE-Node-2',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.42',
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-eu-1',
            'cpu_pct' => 40.0,
            'created_at' => now()->subMinutes(45),
            'updated_at' => now()->subMinutes(45),
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-eu-2',
            'cpu_pct' => 10.0,
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ]);

        $server = Server::factory()->create([
            'uuid' => '33333333-3333-3333-3333-333333333333',
            'config' => json_encode(['name' => 'Adventure Realm']),
        ]);

        TelemetryServer::factory()->create([
            'server_id' => $server->uuid,
            'node_id' => 'node-eu-2',
            'players_online' => 3,
            'cpu_pct' => 12.0,
            'io_write_bytes_per_s' => 512.0,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $this->writeLocationsCache([
            [
                'id' => 21,
                'name' => 'DE-Node-1',
                'fqdn' => 'de-node-1.example.com',
                'location' => 'eu.de',
                'memoryFree' => 8192,
            ],
            [
                'id' => 22,
                'name' => 'DE-Node-2',
                'fqdn' => 'de-node-2.example.com',
                'location' => 'eu.de',
                'memoryFree' => 8192,
            ],
        ], [
            ['short' => 'eu.de', 'maxFreeMemory' => 8192],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/destinations/resolve', [
                'plan' => 'panda',
                'region' => 'eu.de',
                'server_id' => (string) $server->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.filters.excluded_node_id', 'node-eu-2')
            ->assertJsonPath('data.destination.id', 'node-eu-1');

        /** @var list<array<string, mixed>> $candidates */
        $candidates = $response->json('data.candidates');
        $this->assertCount(1, $candidates);
        $this->assertSame('node-eu-1', $candidates[0]['id']);
    }

    public function test_it_returns_validation_error_for_unknown_server_id(): void
    {
        $token = $this->authenticateAdmin();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/destinations/resolve', [
                'plan' => 'panda',
                'server_id' => 'missing-server-id',
            ]);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'The provided server_id does not match an existing server.',
            ]);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<array<string, mixed>>  $locations
     */
    private function writeLocationsCache(array $nodes, array $locations): void
    {
        File::ensureDirectoryExists(dirname($this->localCachePath));
        File::put($this->localCachePath, json_encode([
            'locations' => $locations,
            'nodes' => $nodes,
        ], JSON_THROW_ON_ERROR));
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
