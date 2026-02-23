<?php

declare(strict_types=1);

namespace Interadigital\CoreNotifications\Transport;

final class SlackTransportMessage
{
    public function __construct(
        public readonly string $channel,
        public readonly string $content,
    ) {
    }
}
