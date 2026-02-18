<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\AuthToken;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_a_jwt(): void
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
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'refresh_token',
                'token_type',
                'expires_in',
                'expires_at',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alex@example.com',
        ]);

        // Refresh token should be persisted in the database.
        $this->assertDatabaseCount('auth_tokens', 1);
    }

    public function test_user_can_log_in_and_receive_a_jwt(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
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
                'user' => ['id', 'email'],
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);

        $this->assertDatabaseCount('auth_tokens', 1);
    }

    public function test_user_cannot_log_in_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_me_endpoint_requires_a_valid_jwt(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_profile_with_jwt(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
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

    public function test_refresh_token_returns_new_token_pair(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $response = $this->postJson('/api/auth/refresh', [
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
        $response = $this->postJson('/api/auth/refresh', [
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
        $user = User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $accessToken = $loginResponse->json('token');

        $response = $this->postJson('/api/auth/refresh', [
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
        $user = User::factory()->create([
            'email' => 'alex@example.com',
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
        $user = User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        // First refresh succeeds and rotates the token.
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertOk();

        // Second refresh with the same (now-revoked) token should fail.
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertUnauthorized();
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'secret1234',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $this->postJson('/api/auth/logout', [
            'refresh_token' => $refreshToken,
        ])->assertOk()
          ->assertJson(['message' => 'Logged out.']);

        // The refresh token should no longer work.
        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertUnauthorized();
    }
}
