<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Pterodactyl\Exceptions\PterodactylApiException;
use App\Services\Pterodactyl\Services\PterodactylApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class PterodactylApiClientTest extends TestCase
{
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

    public function test_create_server_posts_to_application_api_and_returns_attributes(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/servers' => Http::response([
                'object' => 'server',
                'attributes' => [
                    'id' => 123,
                    'external_id' => 'srv-123',
                ],
            ], 201),
        ]);

        $client = app(PterodactylApiClient::class);

        $server = $client->createServer([
            'name' => 'Alpha Realm',
            'external_id' => 'srv-123',
        ]);

        $this->assertSame(123, $server['id']);
        $this->assertSame('srv-123', $server['external_id']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://panel.example.com/api/application/servers'
                && $request->hasHeader('Authorization', 'Bearer app-api-token')
                && ($request->data()['name'] ?? null) === 'Alpha Realm'
                && ($request->data()['external_id'] ?? null) === 'srv-123';
        });
    }

    public function test_list_locations_can_request_nodes_relationship_and_unwrap_attributes(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/locations*' => Http::response([
                'data' => [
                    [
                        'object' => 'location',
                        'attributes' => [
                            'id' => 1,
                            'short' => 'eu.de',
                        ],
                    ],
                    [
                        'object' => 'location',
                        'attributes' => [
                            'id' => 2,
                            'short' => 'eu.fi',
                        ],
                    ],
                ],
            ]),
        ]);

        $client = app(PterodactylApiClient::class);

        $locations = $client->listLocations(includeNodes: true);

        $this->assertCount(2, $locations);
        $this->assertSame('eu.de', $locations[0]['short']);
        $this->assertSame('eu.fi', $locations[1]['short']);

        Http::assertSent(function (Request $request): bool {
            $queryString = parse_url($request->url(), PHP_URL_QUERY);
            parse_str(is_string($queryString) ? $queryString : '', $query);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://panel.example.com/api/application/locations')
                && ($query['include'] ?? null) === 'nodes';
        });
    }

    public function test_find_user_by_external_id_uses_filter_query_parameter(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/users*' => Http::response([
                'data' => [
                    [
                        'object' => 'user',
                        'attributes' => [
                            'id' => 99,
                            'external_id' => 'user-99',
                        ],
                    ],
                ],
            ]),
        ]);

        $client = app(PterodactylApiClient::class);

        $user = $client->findUserByExternalId('user-99');

        $this->assertNotNull($user);
        $this->assertSame(99, $user['id']);

        Http::assertSent(function (Request $request): bool {
            $queryString = parse_url($request->url(), PHP_URL_QUERY);
            parse_str(is_string($queryString) ? $queryString : '', $query);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://panel.example.com/api/application/users')
                && ($query['filter']['external_id'] ?? null) === 'user-99';
        });
    }

    public function test_send_power_signal_uses_client_api_key(): void
    {
        Http::fake([
            'https://panel.example.com/api/client/servers/srv-ident/power' => Http::response([], 204),
        ]);

        $client = app(PterodactylApiClient::class);

        $client->sendPowerSignal('srv-ident', 'restart');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://panel.example.com/api/client/servers/srv-ident/power'
                && $request->hasHeader('Authorization', 'Bearer client-api-token')
                && ($request->data()['signal'] ?? null) === 'restart';
        });
    }

    public function test_send_power_signal_rejects_invalid_signal(): void
    {
        $client = app(PterodactylApiClient::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid power signal');

        $client->sendPowerSignal('srv-ident', 'launch');
    }

    public function test_it_throws_a_domain_exception_for_failed_requests(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/servers/404' => Http::response([
                'errors' => [
                    ['detail' => 'Server not found.'],
                ],
            ], 404),
        ]);

        $client = app(PterodactylApiClient::class);

        try {
            $client->getServer(404);
            $this->fail('Expected PterodactylApiException to be thrown.');
        } catch (PterodactylApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
            $this->assertStringContainsString('Server not found.', $exception->getMessage());
            $this->assertSame('Server not found.', $exception->errorPayload()['errors'][0]['detail']);
        }
    }
}
