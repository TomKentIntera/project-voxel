<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Enums;

enum ServerEventType: string
{
    case SERVER_PROVISIONED = 'server.provisioned';
    case SERVER_CANCELLED = 'server.cancelled';
    case SERVER_SUSPENDED = 'server.suspended';
}
