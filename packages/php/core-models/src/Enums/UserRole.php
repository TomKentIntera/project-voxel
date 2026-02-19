<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case ADMIN = 'admin';
}
