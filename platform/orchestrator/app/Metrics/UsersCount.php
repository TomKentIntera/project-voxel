<?php

declare(strict_types=1);

namespace App\Metrics;

use Interadigital\CoreModels\Models\User;

class UsersCount extends Metric
{
    public function key(): string
    {
        return 'users_count';
    }

    public function label(): string
    {
        return 'Registered Users';
    }

    public function value(): int
    {
        return User::count();
    }
}

