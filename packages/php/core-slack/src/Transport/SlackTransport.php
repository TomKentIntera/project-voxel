<?php

declare(strict_types=1);

namespace Interadigital\CoreSlack\Transport;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class SlackTransport
{
    private const CHAT_POST_MESSAGE_ENDPOINT = 'https://slack.com/api/chat.postMessage';

    public function __construct(
        private readonly string $botUserOAuthToken,
    ) {
    }

    public function send(SlackTransportMessage $message): void
    {
        $token = trim($this->botUserOAuthToken);
        if ($token === '') {
            throw new InvalidArgumentException('SLACK_BOT_USER_OAUTH_TOKEN is not configured.');
        }

        $channelId = trim($message->channel);
        if ($channelId === '') {
            throw new InvalidArgumentException('Slack destination channel ID is required.');
        }

        $content = trim($message->content);
        if ($content === '') {
            throw new InvalidArgumentException('Slack notification content is required.');
        }

        $response = Http::withToken($token)
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
}
