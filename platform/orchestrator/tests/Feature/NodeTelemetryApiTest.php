<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use Tests\TestCase;

class NodeTelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_endpoint_requires_valid_node_token(): void
    {
        $this->configureNodeTelemetryAuth([
            'node-a' => 'node-a-token',
        ], null);

        $payload = $this->basePayload('node-a');

        $this->postJson('/api/internal/nodes/node-a/telemetry', $payload)
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->withHeader('Authorization', 'Bearer wrong-token')
            ->postJson('/api/internal/nodes/node-a/telemetry', $payload)
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_telemetry_endpoint_upserts_rows_by_primary_key(): void
    {
        $this->configureNodeTelemetryAuth([
            'node-a' => 'node-a-token',
        ], null);

        $firstPayload = $this->basePayload('node-a');

        $this->withHeader('Authorization', 'Bearer node-a-token')
            ->postJson('/api/internal/nodes/node-a/telemetry', $firstPayload)
            ->assertAccepted()
            ->assertJson([
                'message' => 'Telemetry accepted.',
            ]);

        $this->assertDatabaseCount('telemetry_node', 1);
        $this->assertDatabaseCount('telemetry_server', 2);

        $secondPayload = $this->basePayload('node-a');
        $secondPayload['node']['cpu_pct'] = 88.75;
        $secondPayload['node']['iowait_pct'] = 4.25;
        $secondPayload['servers'][0]['players_online'] = 42;
        $secondPayload['servers'][0]['cpu_pct'] = 91.5;
        $secondPayload['servers'][0]['io_write_bytes_per_s'] = 22222.0;
        $secondPayload['servers'][1]['players_online'] = 3;
        $secondPayload['servers'][1]['cpu_pct'] = 13.25;
        $secondPayload['servers'][1]['io_write_bytes_per_s'] = 1234.5;

        $this->withHeader('Authorization', 'Bearer node-a-token')
            ->postJson('/api/internal/nodes/node-a/telemetry', $secondPayload)
            ->assertAccepted();

        $this->assertDatabaseCount('telemetry_node', 1);
        $this->assertDatabaseCount('telemetry_server', 2);

        $node = TelemetryNode::find('node-a');
        $this->assertNotNull($node);
        $this->assertEqualsWithDelta(88.75, (float) $node->cpu_pct, 0.001);
        $this->assertEqualsWithDelta(4.25, (float) $node->iowait_pct, 0.001);

        $firstServer = TelemetryServer::find('11111111-1111-1111-1111-111111111111');
        $this->assertNotNull($firstServer);
        $this->assertSame('node-a', $firstServer->node_id);
        $this->assertSame(42, $firstServer->players_online);
        $this->assertEqualsWithDelta(91.5, (float) $firstServer->cpu_pct, 0.001);
        $this->assertEqualsWithDelta(22222.0, (float) $firstServer->io_write_bytes_per_s, 0.001);
    }

    public function test_payload_node_id_must_match_route_node_id(): void
    {
        $this->configureNodeTelemetryAuth([], 'shared-node-token');

        $this->withHeader('Authorization', 'Bearer shared-node-token')
            ->postJson('/api/internal/nodes/node-a/telemetry', $this->basePayload('node-b'))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Payload node_id does not match route node_id.',
            ]);

        $this->assertDatabaseCount('telemetry_node', 0);
        $this->assertDatabaseCount('telemetry_server', 0);
    }

    /**
     * @param  array<string, string>  $nodeTokens
     */
    private function configureNodeTelemetryAuth(array $nodeTokens = [], ?string $fallbackToken = 'shared-node-token'): void
    {
        config([
            'services.node_telemetry.tokens' => $nodeTokens,
            'services.node_telemetry.token' => $fallbackToken,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function basePayload(string $nodeId): array
    {
        return [
            'node_id' => $nodeId,
            'timestamp' => now()->toIso8601String(),
            'node' => [
                'cpu_pct' => 55.25,
                'iowait_pct' => 1.75,
            ],
            'servers' => [
                [
                    'server_id' => '11111111-1111-1111-1111-111111111111',
                    'players_online' => 10,
                    'cpu_pct' => 40.5,
                    'io_write_bytes_per_s' => 10240.0,
                ],
                [
                    'server_id' => '22222222-2222-2222-2222-222222222222',
                    'players_online' => 0,
                    'cpu_pct' => 8.1,
                    'io_write_bytes_per_s' => 512.0,
                ],
            ],
        ];
    }
}
