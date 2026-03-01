<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class LocationsCacheApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('locations_cache');
        config()->set('services.locations_cache', [
            'disk' => 'locations_cache',
            'path' => '/var/www/html/storage/app/locations.json',
        ]);
    }

    public function test_admin_can_view_locations_cache_payload(): void
    {
        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'long' => 'Germany', 'maxFreeMemory' => 4096],
            ],
            'nodes' => [
                ['id' => 1, 'name' => 'node-1', 'memoryFree' => 4096],
            ],
        ], JSON_THROW_ON_ERROR));

        $token = $this->authenticateAdmin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/locations/cache')
            ->assertOk()
            ->assertJsonPath('meta.path', 'locations.json')
            ->assertJsonPath('meta.location_count', 1)
            ->assertJsonPath('meta.node_count', 1)
            ->assertJsonPath('data.locations.0.short', 'eu.de')
            ->assertJsonPath('data.nodes.0.name', 'node-1');
    }

    public function test_admin_can_view_raw_locations_cache_json(): void
    {
        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de'],
            ],
            'nodes' => [],
        ], JSON_THROW_ON_ERROR));

        $token = $this->authenticateAdmin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/locations/cache/raw')
            ->assertOk()
            ->assertJsonPath('locations.0.short', 'eu.de');
    }

    public function test_locations_cache_routes_require_admin_authentication(): void
    {
        $this->getJson('/api/locations/cache')->assertUnauthorized();
        $this->getJson('/api/locations/cache/raw')->assertUnauthorized();
    }

    private function authenticateAdmin(): string
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret1234',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}

