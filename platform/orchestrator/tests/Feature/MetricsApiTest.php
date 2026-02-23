<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class MetricsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('metrics.resource_consumption.cache_store', 'array');
        Cache::store('array')->forget((string) config('metrics.resource_consumption.cache_key'));
    }

    public function test_metrics_endpoint_includes_last_hour_resource_consumption_metric(): void
    {
        $token = $this->authenticateAdmin();

        TelemetryNode::factory()->create([
            'node_id' => 'node-a',
            'cpu_pct' => 40.0,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-b',
            'cpu_pct' => 60.0,
            'created_at' => now()->subMinutes(25),
            'updated_at' => now()->subMinutes(25),
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-c',
            'cpu_pct' => 99.0,
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/metrics');

        $response->assertOk();

        /** @var list<array<string, mixed>> $metrics */
        $metrics = $response->json('data');
        $resourceMetric = collect($metrics)->firstWhere('key', 'resource_consumption_last_hour');

        $this->assertIsArray($resourceMetric);
        $this->assertSame('Resource Consumption (1h)', $resourceMetric['label']);
        $this->assertSame('%', $resourceMetric['suffix']);
        $this->assertEqualsWithDelta(50.0, (float) $resourceMetric['value'], 0.001);

        $cachedValue = Cache::store('array')->get((string) config('metrics.resource_consumption.cache_key'));
        $this->assertNotNull($cachedValue);
        $this->assertEqualsWithDelta(50.0, (float) $cachedValue, 0.001);
    }

    public function test_resource_consumption_cache_command_refreshes_metric_value(): void
    {
        TelemetryNode::factory()->create([
            'node_id' => 'node-a',
            'cpu_pct' => 30.0,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-b',
            'cpu_pct' => 90.0,
            'created_at' => now()->subMinutes(35),
            'updated_at' => now()->subMinutes(35),
        ]);

        TelemetryNode::factory()->create([
            'node_id' => 'node-c',
            'cpu_pct' => 5.0,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $this->artisan('metrics:cache-resource-consumption')
            ->expectsOutputToContain('Cached last-hour resource consumption at 60.00%.')
            ->assertSuccessful();

        $cachedValue = Cache::store('array')->get((string) config('metrics.resource_consumption.cache_key'));
        $this->assertNotNull($cachedValue);
        $this->assertEqualsWithDelta(60.0, (float) $cachedValue, 0.001);
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
