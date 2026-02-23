<?php

declare(strict_types=1);

namespace App\Notifications\Slack;

final class ServerOrderedSlackNotification extends AbstractSlackNotification
{
    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function channel(): string
    {
        $ordersChannel = trim((string) config('slack.channels.orders', ''));
        if ($ordersChannel !== '') {
            return $ordersChannel;
        }

        return trim((string) config('slack.channels.servers', ''));
    }

    public function content(): string
    {
        return sprintf(
            ':package: Server ordered successfully (server_id=%d).',
            $this->serverId,
        );
    }
}
