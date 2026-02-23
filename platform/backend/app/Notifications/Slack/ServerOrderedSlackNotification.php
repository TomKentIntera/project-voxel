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
        $serverOrderedChannel = trim((string) config(
            'services.slack.notifications.channels.server_ordered',
            config('services.slack.notifications.channel', ''),
        ));

        if ($serverOrderedChannel !== '') {
            return $serverOrderedChannel;
        }

        return trim((string) config('services.slack.notifications.channel', ''));
    }

    public function content(): string
    {
        return sprintf(
            ':package: Server ordered successfully (server_id=%d).',
            $this->serverId,
        );
    }
}
