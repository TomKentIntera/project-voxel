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
    /**
     * @param array<string, mixed> $eventPayload
     */
    public function dispatch(array $eventPayload): void
    {
        $serverId = $this->extractServerId($eventPayload);
        if ($serverId === null) {
            Log::warning('Skipping server provisioned notifications: missing server_id.', [
                'event_payload' => $eventPayload,
            ]);

            return;
        }

        $server = Server::query()->with('user')->find($serverId);
        if (! ($server instanceof Server)) {
            Log::warning('Skipping server provisioned notifications: server not found.', [
                'server_id' => $serverId,
            ]);

            return;
        }

        $serverOwner = $server->user;
        $userId = (int) $server->user_id;
        $userEmail = '';

        if ($serverOwner !== null) {
            $userId = (int) $serverOwner->id;
            $userEmail = trim((string) $serverOwner->email);
        } else {
            Log::warning('Skipping server provisioned notifications: server owner missing.', [
                'server_id' => (int) $server->id,
            ]);
        }

        if ($userEmail === '') {
            Log::warning('Skipping server provisioned notifications: owner email missing.', [
                'server_id' => (int) $server->id,
                'user_id' => $userId,
            ]);
        }

        $serverUuid = $this->resolveServerUuid($server);
        $serverName = $this->resolveServerName($server);

        if ($userEmail !== '') {
            $this->sendUserEmail(
                email: $userEmail,
                serverId: (int) $server->id,
                serverUuid: $serverUuid,
                serverName: $serverName,
            );
        }

        $this->dispatchSlackNotification(
            serverId: (int) $server->id,
            serverUuid: $serverUuid,
            userId: $userId,
            userEmail: $userEmail !== '' ? $userEmail : 'unknown',
            serverName: $serverName,
        );
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function extractServerId(array $eventPayload): ?int
    {
        $serverId = $eventPayload['server_id'] ?? null;

        if (is_int($serverId) && $serverId > 0) {
            return $serverId;
        }

        if (is_string($serverId) && ctype_digit($serverId)) {
            $normalized = (int) $serverId;

            return $normalized > 0 ? $normalized : null;
        }

        return null;
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
