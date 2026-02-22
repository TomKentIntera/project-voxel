<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use Tests\TestCase;

class PurgeStaleTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_telemetry_rows_older_than_24_hours(): void
    {
        $now = now()->startOfSecond();
        $this->travelTo($now);

        TelemetryNode::factory()->create(['node_id' => 'node-stale']);
        TelemetryNode::factory()->create(['node_id' => 'node-boundary']);
        TelemetryNode::factory()->create(['node_id' => 'node-fresh']);

        TelemetryServer::factory()->create(['server_id' => 'server-stale', 'node_id' => 'node-stale']);
        TelemetryServer::factory()->create(['server_id' => 'server-boundary', 'node_id' => 'node-boundary']);
        TelemetryServer::factory()->create(['server_id' => 'server-fresh', 'node_id' => 'node-fresh']);

        TelemetryNode::query()->whereKey('node-stale')->update(['updated_at' => $now->copy()->subHours(25)]);
        TelemetryNode::query()->whereKey('node-boundary')->update(['updated_at' => $now->copy()->subHours(24)]);
        TelemetryNode::query()->whereKey('node-fresh')->update(['updated_at' => $now->copy()->subHours(1)]);

        TelemetryServer::query()->whereKey('server-stale')->update(['updated_at' => $now->copy()->subHours(25)]);
        TelemetryServer::query()->whereKey('server-boundary')->update(['updated_at' => $now->copy()->subHours(24)]);
        TelemetryServer::query()->whereKey('server-fresh')->update(['updated_at' => $now->copy()->subHours(1)]);

        $this->assertDatabaseCount('telemetry_node', 3);
        $this->assertDatabaseCount('telemetry_server', 3);

        $this->artisan('telemetry:purge-stale')
            ->expectsOutputToContain('Purged 1 stale node telemetry row(s) and 1 stale server telemetry row(s).')
            ->assertSuccessful();

        $this->assertDatabaseCount('telemetry_node', 2);
        $this->assertDatabaseCount('telemetry_server', 2);

        $this->assertDatabaseMissing('telemetry_node', ['node_id' => 'node-stale']);
        $this->assertDatabaseHas('telemetry_node', ['node_id' => 'node-boundary']);
        $this->assertDatabaseHas('telemetry_node', ['node_id' => 'node-fresh']);

        $this->assertDatabaseMissing('telemetry_server', ['server_id' => 'server-stale']);
        $this->assertDatabaseHas('telemetry_server', ['server_id' => 'server-boundary']);
        $this->assertDatabaseHas('telemetry_server', ['server_id' => 'server-fresh']);

        $this->travelBack();
    }
}

