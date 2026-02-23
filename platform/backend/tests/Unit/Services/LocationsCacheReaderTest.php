<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\LocationsCacheReader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Models\ServerEvent;
use Tests\TestCase;

class LocationsCacheReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.locations_cache', [
            'disk' => 'locations_cache',
            'path' => 'locations.json',
            'ttl_seconds' => 300,
        ]);

        Storage::fake('locations_cache');
        Cache::flush();
    }

    public function test_it_reads_shortcode_to_max_free_memory_map_from_shared_storage(): void
    {
        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'maxFreeMemory' => 12288],
                ['short' => 'eu.fi', 'maxFreeMemory' => 4096],
            ],
        ], JSON_THROW_ON_ERROR));

        $reader = app(LocationsCacheReader::class);

        $map = $reader->maxFreeMemoryByLocationShortCode();

        $this->assertSame([
            'eu.de' => 12288,
            'eu.fi' => 4096,
        ], $map);
    }

    public function test_it_returns_cached_value_until_ttl_expires(): void
    {
        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'maxFreeMemory' => 1024],
            ],
        ], JSON_THROW_ON_ERROR));

        $reader = app(LocationsCacheReader::class);

        $first = $reader->maxFreeMemoryByLocationShortCode();
        $this->assertSame(['eu.de' => 1024], $first);

        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'maxFreeMemory' => 8192],
            ],
        ], JSON_THROW_ON_ERROR));

        $second = $reader->maxFreeMemoryByLocationShortCode();
        $this->assertSame(['eu.de' => 1024], $second);
    }

    public function test_it_invalidates_cache_when_server_is_provisioned(): void
    {
        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'maxFreeMemory' => 1024],
            ],
        ], JSON_THROW_ON_ERROR));

        $reader = app(LocationsCacheReader::class);

        $first = $reader->maxFreeMemoryByLocationShortCode();
        $this->assertSame(['eu.de' => 1024], $first);

        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'maxFreeMemory' => 8192],
            ],
        ], JSON_THROW_ON_ERROR));

        ServerEvent::factory()->system()->create([
            'type' => ServerEventType::SERVER_PROVISIONED->value,
        ]);

        $second = $reader->maxFreeMemoryByLocationShortCode();
        $this->assertSame(['eu.de' => 8192], $second);
    }

    public function test_it_invalidates_cache_when_server_is_migrated(): void
    {
        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'maxFreeMemory' => 1024],
            ],
        ], JSON_THROW_ON_ERROR));

        $reader = app(LocationsCacheReader::class);

        $first = $reader->maxFreeMemoryByLocationShortCode();
        $this->assertSame(['eu.de' => 1024], $first);

        Storage::disk('locations_cache')->put('locations.json', json_encode([
            'locations' => [
                ['short' => 'eu.de', 'maxFreeMemory' => 12288],
            ],
        ], JSON_THROW_ON_ERROR));

        ServerEvent::factory()->system()->create([
            'type' => 'server.migrated',
        ]);

        $second = $reader->maxFreeMemoryByLocationShortCode();
        $this->assertSame(['eu.de' => 12288], $second);
    }
}
