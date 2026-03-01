<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlansApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('locations_cache');

        config()->set('services.locations_cache', [
            'disk' => 'locations_cache',
            'path' => 'locations.json',
            'ttl_seconds' => 60,
        ]);

        config()->set('plans.planList', [
            [
                'name' => 'panda',
                'title' => 'Panda',
                'icon' => 'panda',
                'ram' => 2,
                'displayPrice' => '$10',
                'bullets_xx' => [],
                'showDefaultPlans' => true,
                'modpacks' => [],
                'locations' => ['de'],
                'ribbon' => null,
            ],
        ]);

        config()->set('plans.locations', [
            'de' => [
                'title' => 'Germany',
                'flag' => 'de',
                'ptero_location' => 'eu.de',
            ],
            'fi' => [
                'title' => 'Finland',
                'flag' => 'fi',
                'ptero_location' => 'eu.fi',
            ],
        ]);

        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                [
                    'short' => 'eu.de',
                    'long' => 'Germany',
                    'maxFreeMemory' => 8192,
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    public function test_plans_index_returns_locations_from_shared_locations_cache(): void
    {
        $response = $this->getJson('/api/plans');

        $response->assertOk()
            ->assertJsonPath('locations.0.short', 'eu.de')
            ->assertJsonPath('locations.0.long', 'Germany')
            ->assertJsonPath('locations.0.maxFreeMemory', 8192)
            ->assertJsonPath('locations.0.key', 'de')
            ->assertJsonPath('locations.0.title', 'Germany')
            ->assertJsonPath('locations.0.flag', 'de')
            ->assertJsonPath('locations.1.key', 'fi')
            ->assertJsonPath('locations.1.flag', 'fi')
            ->assertJsonPath('locations.1.short', 'eu.fi')
            ->assertJsonPath('locations.1.maxFreeMemory', 0);
    }
}

