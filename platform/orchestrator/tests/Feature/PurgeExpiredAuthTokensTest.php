<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\AuthToken;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class PurgeExpiredAuthTokensTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_tokens_expired_more_than_90_days_ago(): void
    {
        $user = User::factory()->create();

        // Token expired 91 days ago — should be purged.
        AuthToken::factory()->for($user)->create([
            'expires_at' => now()->subDays(91),
        ]);

        // Token expired 89 days ago — should be kept.
        AuthToken::factory()->for($user)->create([
            'expires_at' => now()->subDays(89),
        ]);

        // Active token — should be kept.
        AuthToken::factory()->for($user)->create([
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertDatabaseCount('auth_tokens', 3);

        $this->artisan('auth:purge-expired-tokens')
            ->expectsOutputToContain('Purged 1 expired auth token(s).')
            ->assertSuccessful();

        $this->assertDatabaseCount('auth_tokens', 2);
    }

    public function test_it_handles_no_expired_tokens_gracefully(): void
    {
        $this->artisan('auth:purge-expired-tokens')
            ->expectsOutputToContain('Purged 0 expired auth token(s).')
            ->assertSuccessful();
    }
}

