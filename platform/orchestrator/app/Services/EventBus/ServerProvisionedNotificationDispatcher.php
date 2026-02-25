<?php

declare(strict_types=1);

namespace App\Services\EventBus;

use App\Jobs\SendSlackNotification;
use App\Mail\ServerProvisionedMail;
use App\Notifications\Slack\ServerProvisionedSlackNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Interadigital\CoreModels\Models\Server;
use Throwable;

final class ServerProvisionedNotificationDispatcher
{
    public function dispatch(Server $server): void
    {
        $serverId = (int) $server->id;
        $serverUuid = $this->resolveServerUuid($server);
        $serverName = $this->resolveServerName($server);

        $serverOwner = $server->user;
        $userId = (int) $server->user_id;
        $userEmail = 'unknown';

        if ($serverOwner !== null) {
            $userId = (int) $serverOwner->id;
            $resolvedEmail = trim((string) $serverOwner->email);
            if ($resolvedEmail !== '') {
                $userEmail = $resolvedEmail;
            }
        }

        if ($userEmail !== 'unknown') {
            $this->sendUserEmail(
                email: $userEmail,
                serverId: $serverId,
                serverUuid: $serverUuid,
                serverName: $serverName,
            );
        } else {
            Log::warning('Skipping server provisioned user email: owner email missing.', [
                'server_id' => $serverId,
                'user_id' => $userId,
            ]);
        }

        $this->dispatchSlackNotification(
            serverId: $serverId,
            serverUuid: $serverUuid,
            userId: $userId,
            userEmail: $userEmail,
            serverName: $serverName,
        );
    }

    private function resolveServerUuid(Server $server): string
    {
        $uuid = trim((string) $server->uuid);

        if ($uuid !== '') {
            return $uuid;
        }

        return sprintf('server-%d', (int) $server->id);
    }

    private function resolveServerName(Server $server): string
    {
        $rawConfig = $server->config;

        if (! is_string($rawConfig) || trim($rawConfig) === '') {
            return sprintf('Server #%d', (int) $server->id);
        }

        $decoded = json_decode($rawConfig, true);
        $name = is_array($decoded) ? ($decoded['name'] ?? null) : null;

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        return sprintf('Server #%d', (int) $server->id);
    }

    private function sendUserEmail(string $email, int $serverId, string $serverUuid, string $serverName): void
    {
        try {
            Mail::to($email)->send(new ServerProvisionedMail(
                serverId: $serverId,
                serverUuid: $serverUuid,
                serverName: $serverName,
            ));
        } catch (Throwable $exception) {
            Log::error('Failed to send server provisioned email.', [
                'server_id' => $serverId,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function dispatchSlackNotification(
        int $serverId,
        string $serverUuid,
        int $userId,
        string $userEmail,
        string $serverName,
    ): void {
        $notification = new ServerProvisionedSlackNotification(
            serverId: $serverId,
            serverUuid: $serverUuid,
            userId: $userId,
            userEmail: $userEmail,
            serverName: $serverName,
        );

        if (trim($notification->channel()) === '') {
            Log::warning('Skipping server provisioned Slack notification: no channel configured.', [
                'server_id' => $serverId,
            ]);

            return;
        }

        try {
            SendSlackNotification::dispatch($notification);
        } catch (Throwable $exception) {
            Log::error('Failed to queue server provisioned Slack notification.', [
                'server_id' => $serverId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
