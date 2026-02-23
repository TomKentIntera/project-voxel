<?php

declare(strict_types=1);

namespace App\Notifications\Slack;

abstract class AbstractSlackNotification
{
    abstract public function channel(): string;

    abstract public function content(): string;
}
