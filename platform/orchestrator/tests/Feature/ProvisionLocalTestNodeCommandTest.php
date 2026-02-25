<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Interadigital\CoreModels\Models\Node;
use Tests\TestCase;

class ProvisionLocalTestNodeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_the_local_test_node_and_syncs_allocations_when_configured(): void
    {
        config()->set('services.pterodactyl', [
            'base_url' => 'https://panel.example.com',
            'application_api_key' => 'app-api-token',
            'client_api_key' => 'client-api-token',
            'timeout' => 30,
        ]);

        Http::fake([
            'https://panel.example.com/api/application/locations*' => static function (HttpClientRequest $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'data' => [],
                    ], 200);
                }

                if ($request->method() === 'POST') {
                    return Http::response([
                        'object' => 'location',
                        'attributes' => [
                            'id' => 9,
                            'short' => 'eu.ger',
                        ],
                    ], 201);
                }

                return Http::response([], 405);
            },
            'https://panel.example.com/api/application/nodes' => Http::response([
                'object' => 'node',
                'attributes' => [
                    'id' => 987,
                    'name' => 'node-1',
                ],
            ], 201),
            'https://panel.example.com/api/application/nodes/987/allocations*' => static function (HttpClientRequest $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'data' => [
                            [
                                'object' => 'allocation',
                                'attributes' => [
                                    'id' => 101,
                                    'ip' => '127.0.0.1',
                                    'port' => 26625,
                                ],
                            ],
                        ],
                    ], 200);
                }

                if ($request->method() === 'POST') {
                    return Http::response([
                        'object' => 'allocation_batch',
                    ], 201);
                }

                return Http::response([], 405);
            },
        ]);

        $this->artisan('test:provision-local')
            ->expectsOutputToContain('Provisioned local test node [node-1] in orchestrator.')
            ->expectsOutputToContain('NODE_ID=node-1')
            ->expectsOutputToContain('ALLOCATION_PORTS=26625-26695')
            ->assertSuccessful();

        $node = Node::query()->find('node-1');
        $this->assertNotNull($node);
        $this->assertSame('node-1', $node->name);
        $this->assertSame('eu.ger', $node->region);
        $this->assertSame('127.0.0.1', $node->ip_address);
        $this->assertSame(9, $node->ptero_location_id);
        $this->assertSame(987, $node->ptero_node_id);
        $this->assertSame(['26625-26695'], $node->allocation_ports);
        $this->assertSame(Node::SYNC_STATUS_SYNCED, $node->sync_status);
        $this->assertNotNull($node->synced_at);
        $this->assertIsString($node->token_hash);
        $this->assertSame(64, strlen($node->token_hash));

        Http::assertSent(static function (HttpClientRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://panel.example.com/api/application/locations'
                && ($request->data()['short'] ?? null) === 'eu.ger'
                && ($request->data()['long'] ?? null) === 'Local development';
        });

        Http::assertSent(static function (HttpClientRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://panel.example.com/api/application/nodes'
                && ($request->data()['name'] ?? null) === 'node-1'
                && ($request->data()['location_id'] ?? null) === 9
                && ($request->data()['fqdn'] ?? null) === 'pterodactyl-wings'
                && ($request->data()['daemon_listen'] ?? null) === 8080
                && ($request->data()['daemon_sftp'] ?? null) === 2022;
        });

        $expectedAllocationPorts = array_map(
            static fn (int $port): string => (string) $port,
            range(26626, 26695)
        );

        Http::assertSent(static function (HttpClientRequest $request) use ($expectedAllocationPorts): bool {
            if (
                $request->method() !== 'POST'
                || $request->url() !== 'https://panel.example.com/api/application/nodes/987/allocations'
            ) {
                return false;
            }

            return ($request->data()['ip'] ?? null) === '127.0.0.1'
                && ($request->data()['ports'] ?? null) === $expectedAllocationPorts;
        });
    }

    public function test_it_seeds_local_node_but_fails_when_panel_api_is_not_configured(): void
    {
        config()->set('services.pterodactyl', [
            'base_url' => '',
            'application_api_key' => '',
            'client_api_key' => '',
            'timeout' => 30,
        ]);

        Http::fake();

        $this->artisan('test:provision-local')
            ->expectsOutputToContain('Seeded local test node [node-1] in orchestrator database.')
            ->expectsOutputToContain('Failed to synchronize local test node to Pterodactyl')
            ->expectsOutputToContain('NODE_ID=node-1')
            ->assertFailed();

        $node = Node::query()->find('node-1');
        $this->assertNotNull($node);
        $this->assertSame('node-1', $node->name);
        $this->assertSame(1, $node->ptero_location_id);
        $this->assertNull($node->ptero_node_id);
        $this->assertSame(Node::SYNC_STATUS_PENDING, $node->sync_status);
        $this->assertNull($node->synced_at);
        $this->assertIsString($node->token_hash);
        $this->assertSame(64, strlen($node->token_hash));

        Http::assertNothingSent();
    }
}
