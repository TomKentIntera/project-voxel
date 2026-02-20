<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreAuth\Services\JwtService;
use Interadigital\CoreModels\Models\AuthToken;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Registration (blocked on the orchestrator)
    // ---------------------------------------------------------------

    public function test_registration_is_forbidden(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'alex-example',
            'first_name' => 'Alex',
            'last_name' => 'Example',
            'name' => 'Alex Example',
            'email' => 'alex@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Registration is not available on this service.',
            ]);
    }

    // ---------------------------------------------------------------
    // Login — admin users
    // ---------------------------------------------------------------

    public function test_admin_user_can_log_in_and_receive_a_jwt(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'refresh_token',
                'token_type',
                'expires_in',
                'expires_at',
                'user' => ['id', 'email', 'role'],
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseCount('auth_tokens', 1);
    }

    // ---------------------------------------------------------------
    // Login — non-admin users are rejected
    // ---------------------------------------------------------------

    public function test_customer_user_cannot_log_in(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'customer@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);
    }

    public function test_user_cannot_log_in_with_invalid_credentials(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    // ---------------------------------------------------------------
    // /me endpoint
    // ---------------------------------------------------------------

    public function test_me_endpoint_requires_a_valid_jwt(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_admin_can_fetch_profile_with_jwt(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_me_endpoint_rejects_non_admin_jwt_users(): void
    {
        $customer = User::factory()->customer()->create();
        $token = app(JwtService::class)->issueToken($customer);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);
    }

    // ---------------------------------------------------------------
    // Refresh tokens
    // ---------------------------------------------------------------

    public function test_refresh_endpoint_requires_a_valid_jwt(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertUnauthorized();
    }

    public function test_refresh_endpoint_rejects_non_admin_jwt_users(): void
    {
        $customer = User::factory()->customer()->create();
        $jwtService = app(JwtService::class);

        $accessToken = $jwtService->issueToken($customer);
        $refreshToken = $jwtService->issueRefreshToken($customer);

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/auth/refresh', [
                'refresh_token' => $refreshToken,
            ])->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);
    }

    public function test_refresh_token_returns_new_token_pair_for_admin(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $accessToken = $loginResponse->json('token');
        $refreshToken = $loginResponse->json('refresh_token');

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/auth/refresh', [
                'refresh_token' => $refreshToken,
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'refresh_token',
                'token_type',
                'expires_in',
                'expires_at',
                'user' => ['id', 'email'],
            ])
            ->assertJsonPath('user.id', $user->id);

        // The old refresh token should now be revoked, and a new one created.
        $this->assertDatabaseCount('auth_tokens', 2);

        $oldToken = AuthToken::where('token_hash', AuthToken::hashToken($refreshToken))->first();
        $this->assertNotNull($oldToken->revoked_at);
    }

    public function test_refresh_rejects_invalid_token(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $accessToken = $loginResponse->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/auth/refresh', [
                'refresh_token' => 'not-a-valid-token',
            ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid or expired refresh token.',
            ]);
    }

    public function test_refresh_rejects_access_token(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $accessToken = $loginResponse->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/auth/refresh', [
                'refresh_token' => $accessToken,
            ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid or expired refresh token.',
            ]);
    }

    public function test_refresh_token_cannot_be_used_as_bearer(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $this->withHeader('Authorization', 'Bearer '.$refreshToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_revoked_refresh_token_cannot_be_reused(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        // First refresh succeeds and rotates the token.
        $firstRefreshResponse = $this->withHeader('Authorization', 'Bearer '.$loginResponse->json('token'))
            ->postJson('/api/auth/refresh', [
                'refresh_token' => $refreshToken,
            ]);

        $firstRefreshResponse->assertOk();
        $newAccessToken = (string) $firstRefreshResponse->json('token');

        // Second refresh with the same (now-revoked) token should fail.
        $this->withHeader('Authorization', 'Bearer '.$newAccessToken)
            ->postJson('/api/auth/refresh', [
                'refresh_token' => $refreshToken,
            ])->assertUnauthorized();
    }

    public function test_logout_requires_a_valid_jwt(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $this->postJson('/api/auth/logout', [
            'refresh_token' => $refreshToken,
        ])->assertUnauthorized();
    }

    public function test_logout_rejects_non_admin_jwt_users(): void
    {
        $customer = User::factory()->customer()->create();
        $jwtService = app(JwtService::class);

        $accessToken = $jwtService->issueToken($customer);
        $refreshToken = $jwtService->issueRefreshToken($customer);

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/auth/logout', [
                'refresh_token' => $refreshToken,
            ])->assertForbidden()
            ->assertJson([
                'message' => 'Access denied. Administrator privileges required.',
            ]);
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');
        $accessToken = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/auth/logout', [
                'refresh_token' => $refreshToken,
            ])->assertOk()
          ->assertJson(['message' => 'Logged out.']);

        // The refresh token should no longer work.
        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/auth/refresh', [
                'refresh_token' => $refreshToken,
            ])->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid or expired refresh token.',
            ]);
    }
}
