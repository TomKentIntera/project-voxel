<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class ServerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_fetch_server_panel_url(): void
    {
        $this->configurePterodactyl();

        Http::fake([
            'https://panel.example.test/api/application/servers/1234' => Http::response([
                'attributes' => [
                    'id' => 1234,
                    'identifier' => 'abc123xy',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Skyblock Server']),
            'ptero_id' => '1234',
            'initialised' => true,
            'stripe_tx_return' => true,
        ]);

        $token = $this->issueJwt($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/'.$server->uuid.'/panel-url');

        $response
            ->assertOk()
            ->assertJsonPath('panel_url', 'https://panel.example.test/server/abc123xy');

        Http::assertSent(function (HttpClientRequest $request): bool {
            return $request->url() === 'https://panel.example.test/api/application/servers/1234'
                && $request->hasHeader('Authorization', 'Bearer test-ptero-key');
        });
    }

    public function test_panel_url_route_returns_404_for_non_owner(): void
    {
        $this->configurePterodactyl();
        Http::fake();

        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $server = Server::factory()->create([
            'user_id' => $owner->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Skyblock Server']),
            'ptero_id' => '1234',
            'initialised' => true,
            'stripe_tx_return' => true,
        ]);

        $token = $this->issueJwt($intruder);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/'.$server->uuid.'/panel-url')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_panel_url_route_returns_conflict_while_server_is_provisioning(): void
    {
        $this->configurePterodactyl();
        Http::fake();

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Skyblock Server']),
            'ptero_id' => '1234',
            'initialised' => false,
            'stripe_tx_return' => false,
        ]);

        $token = $this->issueJwt($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/'.$server->uuid.'/panel-url')
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Server is still provisioning.',
            ]);

        Http::assertNothingSent();
    }

    public function test_panel_url_route_falls_back_to_panel_root_if_identifier_is_missing(): void
    {
        $this->configurePterodactyl();

        Http::fake([
            'https://panel.example.test/api/application/servers*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Skyblock Server']),
            'ptero_id' => null,
            'initialised' => true,
            'stripe_tx_return' => true,
        ]);

        $token = $this->issueJwt($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/'.$server->uuid.'/panel-url')
            ->assertOk()
            ->assertJsonPath('panel_url', 'https://panel.example.test');
    }

    private function configurePterodactyl(): void
    {
        config()->set('services.pterodactyl.panel_url', 'https://panel.example.test');
        config()->set('services.pterodactyl.api_url', 'https://panel.example.test/api');
        config()->set('services.pterodactyl.api_key', 'test-ptero-key');
    }

    private function issueJwt(User $user): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}
