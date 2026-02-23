<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateLocationFreeSpaceCacheTest extends TestCase
{
    private string $localCachePath;

    private string $backendCachePath;

    private string $backendCacheDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localCachePath = storage_path('app/locations.json');
        $this->backendCacheDirectory = storage_path('framework/testing/backend-cache');
        $this->backendCachePath = $this->backendCacheDirectory.'/locations.json';

        config()->set('services.pterodactyl', [
            'base_url' => 'https://panel.example.com',
            'application_api_key' => 'app-api-token',
            'client_api_key' => 'client-api-token',
            'timeout' => 30,
            'backend_locations_cache_path' => $this->backendCachePath,
        ]);

        File::delete($this->localCachePath);
        File::deleteDirectory($this->backendCacheDirectory);
    }

    protected function tearDown(): void
    {
        File::delete($this->localCachePath);
        File::deleteDirectory($this->backendCacheDirectory);

        parent::tearDown();
    }

    public function test_it_builds_and_copies_the_locations_cache(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/locations*' => Http::response([
                'data' => [
                    [
                        'object' => 'location',
                        'attributes' => [
                            'id' => 1,
                            'short' => 'eu.de',
                            'long' => 'Germany',
                            'relationships' => [
                                'nodes' => [
                                    'data' => [
                                        [
                                            'object' => 'node',
                                            'attributes' => [
                                                'id' => 11,
                                                'name' => 'DE-Node-1',
                                                'fqdn' => 'de-1.example.com',
                                                'memory' => 32768,
                                                'allocated_resources' => [
                                                    'memory' => 10240,
                                                ],
                                            ],
                                        ],
                                        [
                                            'object' => 'node',
                                            'attributes' => [
                                                'id' => 12,
                                                'name' => 'DE-Node-2',
                                                'fqdn' => 'de-2.example.com',
                                                'memory' => 16384,
                                                'allocated_resources' => [
                                                    'memory' => 4096,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('pterodactyl:update-location-free-space')
            ->expectsOutputToContain('Updated Pterodactyl location and node free-space cache.')
            ->assertSuccessful();

        $this->assertFileExists($this->localCachePath);
        $this->assertFileExists($this->backendCachePath);

        $localPayload = json_decode((string) File::get($this->localCachePath), true);
        $backendPayload = json_decode((string) File::get($this->backendCachePath), true);

        $this->assertIsArray($localPayload);
        $this->assertIsArray($backendPayload);
        $this->assertSame($localPayload, $backendPayload);

        $this->assertCount(1, $localPayload['locations']);
        $this->assertCount(2, $localPayload['nodes']);

        $location = $localPayload['locations'][0];
        $this->assertSame('eu.de', $location['short']);
        $this->assertSame(2, $location['nodeCount']);
        $this->assertSame(49152, $location['totalMemory']);
        $this->assertSame(14336, $location['totalUsedMemory']);
        $this->assertSame(34816, $location['totalFreeMemory']);
        $this->assertSame(22528, $location['maxFreeMemory']);
        $this->assertEqualsWithDelta(29.1666666667, (float) $location['totalMemoryUsedPercent'], 0.0000001);
        $this->assertEqualsWithDelta(25.0, (float) $location['memoryUsedFreestNodePercent'], 0.0000001);

        Http::assertSent(function (Request $request): bool {
            $queryString = parse_url($request->url(), PHP_URL_QUERY);
            parse_str(is_string($queryString) ? $queryString : '', $query);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://panel.example.com/api/application/locations')
                && ($query['include'] ?? null) === 'nodes';
        });
    }
}
