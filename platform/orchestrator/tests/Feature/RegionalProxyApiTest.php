<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\RegionalProxy;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class RegionalProxyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_regional_proxy_and_receive_proxy_token(): void
    {
        $token = $this->authenticateAdmin();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/regional-proxies', [
                'name' => 'Frankfurt Edge',
                'region' => 'eu.de',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Frankfurt Edge')
            ->assertJsonPath('data.region', 'eu.de')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'region',
                    'last_active_at',
                    'last_used_at',
                    'created_at',
                    'updated_at',
                    'proxy_token',
                ],
            ]);

        $proxyId = (int) $response->json('data.id');
        $rawToken = (string) $response->json('data.proxy_token');

        $regionalProxy = RegionalProxy::find($proxyId);
        $this->assertNotNull($regionalProxy);
        $this->assertNotSame($rawToken, $regionalProxy->token_hash);
        $this->assertSame(RegionalProxy::hashToken($rawToken), $regionalProxy->token_hash);
        $this->assertTrue($regionalProxy->matchesToken($rawToken));
    }

    public function test_admin_can_list_regional_proxies_with_search_and_pagination(): void
    {
        $token = $this->authenticateAdmin();

        RegionalProxy::factory()->create([
            'name' => 'Frankfurt Edge',
            'region' => 'eu.de',
        ]);

        RegionalProxy::factory()->create([
            'name' => 'Virginia Edge',
            'region' => 'us.east',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/regional-proxies?search=eu&per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Frankfurt Edge')
            ->assertJsonPath('data.0.region', 'eu.de')
            ->assertJsonMissingPath('data.0.token_hash');
    }

    public function test_admin_can_fetch_single_regional_proxy(): void
    {
        $token = $this->authenticateAdmin();

        $regionalProxy = RegionalProxy::factory()->create([
            'name' => 'Singapore Edge',
            'region' => 'ap.southeast',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/regional-proxies/'.$regionalProxy->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $regionalProxy->id)
            ->assertJsonPath('data.name', 'Singapore Edge')
            ->assertJsonPath('data.region', 'ap.southeast')
            ->assertJsonMissingPath('data.token_hash')
            ->assertJsonMissingPath('data.proxy_token');
    }

    public function test_show_returns_not_found_for_missing_regional_proxy(): void
    {
        $token = $this->authenticateAdmin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/regional-proxies/999999')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Regional proxy not found.',
            ]);
    }

    public function test_mappings_endpoint_requires_valid_regional_proxy_token(): void
    {
        $this->getJson('/api/regional-proxies/mappings')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/regional-proxies/mappings')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_regional_proxy_can_fetch_region_mappings_and_updates_last_active(): void
    {
        ['regionalProxy' => $regionalProxy, 'rawToken' => $rawToken] = $this->createRegionalProxyWithRawToken([
            'name' => 'Germany Edge',
            'region' => 'eu.de',
            'last_active_at' => null,
        ]);

        $matchingServer = Server::factory()->create([
            'uuid' => 'srv-eu-match',
            'ptero_id' => '111',
            'config' => json_encode([
                'location' => 'de',
                'name' => 'EU Match',
            ]),
        ]);

        Server::factory()->create([
            'uuid' => 'srv-eu-miss',
            'ptero_id' => '222',
            'config' => json_encode([
                'location' => 'fi',
                'name' => 'EU Miss',
            ]),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->getJson('/api/regional-proxies/mappings');

        $response->assertOk()
            ->assertJsonPath('data.regional_proxy.id', $regionalProxy->id)
            ->assertJsonPath('data.regional_proxy.region', 'eu.de')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.mappings.0.server_id', $matchingServer->id)
            ->assertJsonPath('data.mappings.0.server_uuid', 'srv-eu-match')
            ->assertJsonPath('data.mappings.0.region', 'eu.de');

        $regionalProxy->refresh();
        $this->assertNotNull($regionalProxy->last_active_at);
        $this->assertNotNull($regionalProxy->last_used_at);
    }

    public function test_mappings_by_id_rejects_token_for_different_proxy(): void
    {
        ['regionalProxy' => $regionalProxy, 'rawToken' => $rawToken] = $this->createRegionalProxyWithRawToken([
            'name' => 'Token Owner',
            'region' => 'eu.de',
        ]);

        ['regionalProxy' => $otherProxy] = $this->createRegionalProxyWithRawToken([
            'name' => 'Other Proxy',
            'region' => 'eu.de',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->getJson('/api/regional-proxies/'.$otherProxy->id.'/mappings')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied for the requested regional proxy.',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->getJson('/api/regional-proxies/'.$regionalProxy->id.'/mappings')
            ->assertOk();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{regionalProxy: RegionalProxy, rawToken: string}
     */
    private function createRegionalProxyWithRawToken(array $attributes = []): array
    {
        $rawToken = RegionalProxy::generateToken();

        $regionalProxy = RegionalProxy::factory()->create([
            ...$attributes,
            'token_hash' => RegionalProxy::hashToken($rawToken),
        ]);

        return [
            'regionalProxy' => $regionalProxy,
            'rawToken' => $rawToken,
        ];
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

