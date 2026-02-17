<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Enums;

enum ServerStatus: string
{
    case NEW = 'new';
    case PROVISIONING = 'provisioning';
    case PROVISIONED = 'provisioned';
    case ACTIVE = 'active';
    case PAST_DUE = 'past-due';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case DELETED = 'deleted';
}
