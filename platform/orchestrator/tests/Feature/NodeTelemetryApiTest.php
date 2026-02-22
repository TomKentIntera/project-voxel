<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryNodeSample;
use Interadigital\CoreModels\Models\TelemetryServer;
use Interadigital\CoreModels\Models\TelemetryServerSample;
use Tests\TestCase;

class NodeTelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_endpoint_requires_valid_node_token(): void
    {
        ['rawToken' => $rawToken] = $this->createNodeWithRawToken([
            'id' => 'node-a',
        ]);

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

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/internal/nodes/unknown-node/telemetry', $this->basePayload('unknown-node'))
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_telemetry_endpoint_upserts_rows_by_primary_key(): void
    {
        ['rawToken' => $rawToken] = $this->createNodeWithRawToken([
            'id' => 'node-a',
        ]);

        $firstPayload = $this->basePayload('node-a');

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/internal/nodes/node-a/telemetry', $firstPayload)
            ->assertAccepted()
            ->assertJson([
                'message' => 'Telemetry accepted.',
            ]);

        $this->assertDatabaseCount('telemetry_node', 1);
        $this->assertDatabaseCount('telemetry_server', 2);
        $this->assertDatabaseCount('telemetry_node_sample', 1);
        $this->assertDatabaseCount('telemetry_server_sample', 2);

        $secondPayload = $this->basePayload('node-a');
        $secondPayload['node']['cpu_pct'] = 88.75;
        $secondPayload['node']['iowait_pct'] = 4.25;
        $secondPayload['servers'][0]['players_online'] = 42;
        $secondPayload['servers'][0]['cpu_pct'] = 91.5;
        $secondPayload['servers'][0]['io_write_bytes_per_s'] = 22222.0;
        $secondPayload['servers'][1]['players_online'] = 3;
        $secondPayload['servers'][1]['cpu_pct'] = 13.25;
        $secondPayload['servers'][1]['io_write_bytes_per_s'] = 1234.5;

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/internal/nodes/node-a/telemetry', $secondPayload)
            ->assertAccepted();

        $this->assertDatabaseCount('telemetry_node', 1);
        $this->assertDatabaseCount('telemetry_server', 2);
        $this->assertDatabaseCount('telemetry_node_sample', 2);
        $this->assertDatabaseCount('telemetry_server_sample', 4);

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

        $nodeIdentity = Node::find('node-a');
        $this->assertNotNull($nodeIdentity);
        $this->assertNotNull($nodeIdentity->last_active_at);
        $this->assertNotNull($nodeIdentity->last_used_at);

        $latestSample = TelemetryNodeSample::query()
            ->where('node_id', 'node-a')
            ->orderByDesc('recorded_at')
            ->first();
        $this->assertNotNull($latestSample);
        $this->assertEqualsWithDelta(88.75, (float) $latestSample->cpu_pct, 0.001);
        $this->assertEqualsWithDelta(4.25, (float) $latestSample->iowait_pct, 0.001);

        $latestServerSample = TelemetryServerSample::query()
            ->where('server_id', '11111111-1111-1111-1111-111111111111')
            ->orderByDesc('recorded_at')
            ->first();
        $this->assertNotNull($latestServerSample);
        $this->assertSame(42, $latestServerSample->players_online);
        $this->assertEqualsWithDelta(91.5, (float) $latestServerSample->cpu_pct, 0.001);
        $this->assertEqualsWithDelta(22222.0, (float) $latestServerSample->io_write_bytes_per_s, 0.001);
    }

    public function test_payload_node_id_must_match_route_node_id(): void
    {
        ['rawToken' => $rawToken] = $this->createNodeWithRawToken([
            'id' => 'node-a',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/internal/nodes/node-a/telemetry', $this->basePayload('node-b'))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Payload node_id does not match route node_id.',
            ]);

        $this->assertDatabaseCount('telemetry_node', 0);
        $this->assertDatabaseCount('telemetry_server', 0);
        $this->assertDatabaseCount('telemetry_node_sample', 0);
        $this->assertDatabaseCount('telemetry_server_sample', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{node: Node, rawToken: string}
     */
    private function createNodeWithRawToken(array $attributes = []): array
    {
        $rawToken = Node::generateToken();

        $node = Node::factory()->create([
            ...$attributes,
            'token_hash' => Node::hashToken($rawToken),
        ]);

        return [
            'node' => $node,
            'rawToken' => $rawToken,
        ];
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
