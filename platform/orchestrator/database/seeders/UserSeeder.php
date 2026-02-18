<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Interadigital\CoreModels\Models\User;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with starting users.
     */
    public function run(): void
    {
        // Fixed admin / test user – always available for local development & staging.
        User::factory()->create([
            'username' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);

        // A secondary known test user for multi-user scenarios.
        User::factory()->create([
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
        ]);

        // Generate a handful of random users for realistic data.
        User::factory(8)->create();
    }
}

