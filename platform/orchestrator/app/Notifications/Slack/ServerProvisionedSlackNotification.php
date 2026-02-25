<?php

declare(strict_types=1);

namespace App\Notifications\Slack;

final class ServerProvisionedSlackNotification extends AbstractSlackNotification
{
    public function __construct(
        private readonly int $serverId,
        private readonly string $serverUuid,
        private readonly int $userId,
        private readonly string $userEmail,
        private readonly string $serverName,
    ) {
    }

    public function channel(): string
    {
        $serversChannel = trim((string) config('slack.channels.servers', ''));
        if ($serversChannel !== '') {
            return $serversChannel;
        }

        return trim((string) config('slack.channels.orders', ''));
    }

    public function content(): string
    {
        return sprintf(
            ':white_check_mark: Server provisioned (server_id=%d, server_uuid=%s, user_id=%d, user_email=%s, name=%s).',
            $this->serverId,
            $this->serverUuid,
            $this->userId,
            $this->userEmail,
            $this->serverName,
        );
    }
}
