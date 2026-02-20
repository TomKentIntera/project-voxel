<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_owner_is_redirected_to_their_panel_server(): void
    {
        $this->setPterodactylEnvironment();

        Http::fake([
            'https://panel.example.test/api/application/servers/*' => Http::response([
                'attributes' => [
                    'id' => 123,
                    'identifier' => 'abc12345',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Test Server']),
            'status' => 'new',
            'stripe_tx_return' => true,
            'initialised' => true,
            'ptero_id' => '123',
        ]);

        $response = $this->actingAs($user)->get(route('client.server.panel', ['serverUUID' => $server->uuid]));

        $response->assertRedirect('https://panel.example.test/server/abc12345');
    }

    public function test_server_panel_route_404s_for_non_owner(): void
    {
        $this->setPterodactylEnvironment();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $server = Server::factory()->create([
            'user_id' => $owner->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Test Server']),
            'status' => 'new',
            'stripe_tx_return' => true,
            'initialised' => true,
            'ptero_id' => '123',
        ]);

        $response = $this->actingAs($otherUser)->get(route('client.server.panel', ['serverUUID' => $server->uuid]));

        $response->assertNotFound();
    }

    public function test_server_panel_route_redirects_back_while_server_is_provisioning(): void
    {
        $this->setPterodactylEnvironment();

        Http::fake();

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Test Server']),
            'status' => 'new',
            'stripe_tx_return' => false,
            'initialised' => false,
            'ptero_id' => null,
        ]);

        $response = $this->actingAs($user)->get(route('client.server.panel', ['serverUUID' => $server->uuid]));

        $response->assertRedirect('/client');
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_server_panel_route_falls_back_to_panel_root_when_identifier_is_missing(): void
    {
        $this->setPterodactylEnvironment();

        Http::fake([
            'https://panel.example.test/api/application/servers*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'config' => json_encode(['name' => 'Test Server']),
            'status' => 'new',
            'stripe_tx_return' => true,
            'initialised' => true,
            'ptero_id' => null,
        ]);

        $response = $this->actingAs($user)->get(route('client.server.panel', ['serverUUID' => $server->uuid]));

        $response->assertRedirect('https://panel.example.test');
    }

    private function setPterodactylEnvironment(): void
    {
        putenv('PTERO_PANEL=https://panel.example.test');
        putenv('PTERO_API=https://panel.example.test/api');
        putenv('PTERO_API_KEY=test-api-key');

        $_ENV['PTERO_PANEL'] = 'https://panel.example.test';
        $_ENV['PTERO_API'] = 'https://panel.example.test/api';
        $_ENV['PTERO_API_KEY'] = 'test-api-key';

        $_SERVER['PTERO_PANEL'] = 'https://panel.example.test';
        $_SERVER['PTERO_API'] = 'https://panel.example.test/api';
        $_SERVER['PTERO_API_KEY'] = 'test-api-key';
    }
}
