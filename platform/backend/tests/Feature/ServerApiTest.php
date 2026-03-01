<?php

namespace Tests\Feature;

use App\Services\Stripe\Services\StripeCheckoutSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Mockery\MockInterface;
use Stripe\Checkout\Session;
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

    public function test_purchase_route_creates_pending_server_and_returns_checkout_url(): void
    {
        $this->mock(StripeCheckoutSessionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSubscriptionCheckoutSession')
                ->once()
                ->andReturn(Session::constructFrom([
                    'id' => 'cs_test_123',
                    'url' => 'https://checkout.stripe.test/c/pay/cs_test_123',
                ]));
        });

        $user = User::factory()->create();
        $token = $this->issueJwt($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/servers/purchase', [
                'plan' => 'parrot',
                'name' => 'Skyblock Server',
                'location' => 'de',
                'minecraft_version' => '1.21.1',
                'type' => 'paper',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.test/c/pay/cs_test_123');

        $server = Server::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('parrot', $server->plan);
        $this->assertSame('new', $server->status);
        $this->assertFalse((bool) $server->stripe_tx_return);
        $this->assertNull($server->stripe_tx_id);

        $config = json_decode((string) $server->config, true);
        $this->assertSame('Skyblock Server', $config['name'] ?? null);
        $this->assertSame('de', $config['location'] ?? null);
        $this->assertSame('1.21.1', $config['minecraft_version'] ?? null);
        $this->assertSame('paper', $config['type'] ?? null);
    }

    public function test_purchase_route_rejects_location_not_available_for_plan(): void
    {
        $this->mock(StripeCheckoutSessionService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createSubscriptionCheckoutSession');
        });

        $user = User::factory()->create();
        $token = $this->issueJwt($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/servers/purchase', [
                'plan' => 'parrot',
                'name' => 'Skyblock Server',
                'location' => 'ca',
                'minecraft_version' => '1.21.1',
                'type' => 'paper',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Selected location is invalid for this plan.',
            ]);

        $this->assertDatabaseCount('servers', 0);
    }

    public function test_owner_can_fetch_server_provisioning_status(): void
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
            'status' => 'active',
        ]);

        $token = $this->issueJwt($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/servers/'.$server->uuid.'/provisioning-status')
            ->assertOk()
            ->assertJson([
                'server_uuid' => $server->uuid,
                'payment_confirmed' => true,
                'initialised' => true,
                'provisioned' => true,
                'status' => 'active',
                'panel_url' => 'https://panel.example.test/server/abc123xy',
            ]);
    }

    public function test_owner_can_confirm_purchase_return_with_session_token(): void
    {
        $this->mock(StripeCheckoutSessionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->with('cs_test_123')
                ->andReturn(Session::constructFrom([
                    'id' => 'cs_test_123',
                    'status' => 'complete',
                    'payment_status' => 'paid',
                    'subscription' => 'sub_test_123',
                    'metadata' => [
                        'server_uuid' => 'server-uuid-123',
                    ],
                ]));
        });

        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => 'server-uuid-123',
            'stripe_tx_id' => null,
        ]);
        $token = $this->issueJwt($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/servers/server-uuid-123/purchase-confirmation', [
                'session_id' => 'cs_test_123',
            ])
            ->assertOk()
            ->assertJson([
                'server_uuid' => 'server-uuid-123',
                'stripe_subscription_id' => 'sub_test_123',
                'checkout_status' => 'complete',
                'payment_status' => 'paid',
            ]);

        $server->refresh();
        $this->assertSame('sub_test_123', $server->stripe_tx_id);
    }

    public function test_purchase_confirmation_rejects_mismatched_server_uuid_metadata(): void
    {
        $this->mock(StripeCheckoutSessionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->with('cs_test_123')
                ->andReturn(Session::constructFrom([
                    'id' => 'cs_test_123',
                    'status' => 'complete',
                    'payment_status' => 'paid',
                    'subscription' => 'sub_test_123',
                    'metadata' => [
                        'server_uuid' => 'different-server-uuid',
                    ],
                ]));
        });

        $user = User::factory()->create();
        Server::factory()->create([
            'user_id' => $user->id,
            'uuid' => 'server-uuid-123',
            'stripe_tx_id' => null,
        ]);
        $token = $this->issueJwt($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/servers/server-uuid-123/purchase-confirmation', [
                'session_id' => 'cs_test_123',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Checkout session does not match this server.',
            ]);
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
