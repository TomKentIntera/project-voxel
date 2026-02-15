<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class V1EndpointsTest extends TestCase
{
    public function test_health_endpoint_returns_expected_shape(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'service', 'apiVersion', 'timestamp']);
    }

    public function test_legacy_domain_inventory_endpoint_returns_domains(): void
    {
        $response = $this->getJson('/api/v1/migration/legacy-domains');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'source',
                'domains' => [
                    '*' => ['key', 'description', 'legacyRoutes', 'targetApi', 'status'],
                ],
            ]);
    }
}
