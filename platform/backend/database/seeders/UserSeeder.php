<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Interadigital\CoreModels\Enums\UserRole;
use Interadigital\CoreModels\Models\User;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with starting users.
     */
    public function run(): void
    {
        $seedPassword = env('SEED_USER_PASSWORD', 'password');

        // Fixed admin / test user – always available for local development & staging.
        User::query()->updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'username' => 'admin',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'password' => Hash::make($seedPassword),
                'role' => UserRole::ADMIN->value,
            ]
        );

        // A secondary known test user for multi-user scenarios.
        User::query()->updateOrCreate(
            ['email' => 'test@test.com'],
            [
                'username' => 'testuser',
                'first_name' => 'Test',
                'last_name' => 'User',
                'name' => 'Test User',
                'password' => Hash::make($seedPassword),
                'role' => UserRole::CUSTOMER->value,
            ]
        );

        // Generate a handful of random users for realistic data.
        User::factory(8)->create();
    }
}

