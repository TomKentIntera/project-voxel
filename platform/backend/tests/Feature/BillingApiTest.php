<?php

namespace Tests\Feature;

use App\Services\Stripe\Services\StripeBillingPortalSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class BillingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_billing_portal_session(): void
    {
        $user = User::factory()->create([
            'password' => 'secret1234',
        ]);

        $service = $this->mock(StripeBillingPortalSessionService::class);
        $service->shouldReceive('createCustomerPortalUrl')
            ->once()
            ->withArgs(fn (User $resolvedUser): bool => $resolvedUser->id === $user->id)
            ->andReturn('https://billing.example.test/session/abc123');

        $token = $this->issueJwt($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/billing/portal-session')
            ->assertOk()
            ->assertJsonPath('portal_url', 'https://billing.example.test/session/abc123');
    }

    public function test_portal_session_route_requires_authentication(): void
    {
        $this->postJson('/api/billing/portal-session')
            ->assertUnauthorized();
    }

    private function issueJwt(User $user): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}

