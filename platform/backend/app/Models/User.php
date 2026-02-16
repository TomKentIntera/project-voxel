<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Interadigital\CoreModels\Models\User as CoreUser;

class User extends CoreUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
}
