<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SyncNodeToPterodactylJob;
use App\Services\Pterodactyl\Exceptions\PterodactylApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Interadigital\CoreModels\Models\Node;
use Tests\TestCase;

class SyncNodeToPterodactylJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.pterodactyl', [
            'base_url' => 'https://panel.example.com',
            'application_api_key' => 'app-api-token',
            'client_api_key' => 'client-api-token',
            'timeout' => 30,
        ]);
    }

    public function test_it_creates_panel_node_and_missing_allocations(): void
    {
        $node = Node::factory()->create([
            'name' => 'Frankfurt Wings Node',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.10',
            'ptero_location_id' => 1,
            'fqdn' => 'wings-eu-de.example.com',
            'scheme' => 'https',
            'behind_proxy' => true,
            'maintenance_mode' => false,
            'memory' => 32768,
            'memory_overallocate' => 0,
            'disk' => 204800,
            'disk_overallocate' => 0,
            'upload_size' => 500,
            'daemon_sftp' => 2022,
            'daemon_listen' => 8080,
            'allocation_ip' => '203.0.113.10',
            'allocation_alias' => 'wings-eu-de',
            'allocation_ports' => ['25565-25566', 25570],
            'sync_status' => Node::SYNC_STATUS_PENDING,
            'sync_error' => null,
            'synced_at' => null,
        ]);

        Http::fake([
            'https://panel.example.com/api/application/nodes' => Http::response([
                'object' => 'node',
                'attributes' => [
                    'id' => 321,
                    'name' => 'Frankfurt Wings Node',
                ],
            ], 201),
            'https://panel.example.com/api/application/nodes/321/allocations*' => Http::response([
                'data' => [
                    [
                        'object' => 'allocation',
                        'attributes' => [
                            'id' => 5001,
                            'ip' => '203.0.113.10',
                            'port' => 25565,
                        ],
                    ],
                ],
            ], 200),
            'https://panel.example.com/api/application/nodes/321/allocations' => Http::response([
                'object' => 'allocation_batch',
            ], 201),
        ]);

        SyncNodeToPterodactylJob::dispatchSync($node->id);

        $node->refresh();
        $this->assertSame(321, $node->ptero_node_id);
        $this->assertSame(Node::SYNC_STATUS_SYNCED, $node->sync_status);
        $this->assertNull($node->sync_error);
        $this->assertNotNull($node->synced_at);

        Http::assertSent(function (Request $request): bool {
            if (
                $request->method() !== 'POST'
                || $request->url() !== 'https://panel.example.com/api/application/nodes/321/allocations'
            ) {
                return false;
            }

            $ports = $request->data()['ports'] ?? null;

            if (! is_array($ports)) {
                return false;
            }

            sort($ports);

            return ($request->data()['ip'] ?? null) === '203.0.113.10'
                && ($request->data()['alias'] ?? null) === 'wings-eu-de'
                && $ports === ['25566', '25570'];
        });
    }

    public function test_it_marks_node_as_failed_when_panel_request_fails(): void
    {
        $node = Node::factory()->create([
            'sync_status' => Node::SYNC_STATUS_PENDING,
            'sync_error' => null,
            'synced_at' => null,
        ]);

        Http::fake([
            'https://panel.example.com/api/application/nodes' => Http::response([
                'errors' => [
                    [
                        'detail' => 'Validation failed.',
                    ],
                ],
            ], 422),
        ]);

        try {
            SyncNodeToPterodactylJob::dispatchSync($node->id);
            $this->fail('Expected PterodactylApiException to be thrown.');
        } catch (PterodactylApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }

        $node->refresh();
        $this->assertSame(Node::SYNC_STATUS_FAILED, $node->sync_status);
        $this->assertNotNull($node->sync_error);
        $this->assertNull($node->synced_at);
    }
}
