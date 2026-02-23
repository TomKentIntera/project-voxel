<?php

declare(strict_types=1);

namespace Interadigital\CoreNotifications\Contracts;

interface NotificationMessageContract
{
    public function channel(): string;

    public function content(): string;
}
