<?php

declare(strict_types=1);

namespace App\Services\Slack;

use App\Notifications\Slack\AbstractSlackNotification;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class SlackNotificationSender
{
    private const CHAT_POST_MESSAGE_ENDPOINT = 'https://slack.com/api/chat.postMessage';

    public function send(AbstractSlackNotification $notification): void
    {
        $channelId = trim($notification->channel());
        if ($channelId === '') {
            throw new InvalidArgumentException('Slack destination channel ID is required.');
        }

        $content = $notification->content();
        if (trim($content) === '') {
            throw new InvalidArgumentException('Slack notification content is required.');
        }

        $response = Http::withToken($this->botUserOAuthToken())
            ->acceptJson()
            ->asJson()
            ->post(self::CHAT_POST_MESSAGE_ENDPOINT, [
                'channel' => $channelId,
                'text' => $content,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Slack notification request failed with status %d.',
                $response->status(),
            ));
        }

        $payload = $response->json();
        if (! is_array($payload) || ($payload['ok'] ?? false) !== true) {
            $error = is_array($payload) && is_string($payload['error'] ?? null)
                ? $payload['error']
                : 'unknown_error';

            throw new RuntimeException('Slack notification request was rejected: '.$error);
        }
    }

    private function botUserOAuthToken(): string
    {
        $token = trim((string) config('services.slack.notifications.bot_user_oauth_token', ''));

        if ($token === '') {
            throw new InvalidArgumentException('SLACK_BOT_USER_OAUTH_TOKEN is not configured.');
        }

        return $token;
    }
}
