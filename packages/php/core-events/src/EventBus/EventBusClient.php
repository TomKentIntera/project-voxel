<?php

declare(strict_types=1);

namespace Interadigital\CoreEvents\EventBus;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Interadigital\CoreEvents\Aws\SignatureV4;
use Interadigital\CoreEvents\Events\AbstractEvent;
use RuntimeException;

class EventBusClient
{
    public function publish(AbstractEvent $event): void
    {
        $topicArn = $this->resolveTopicArn($event);

        if ($topicArn === '') {
            Log::warning('Skipping event publish because topic ARN is not configured.', [
                'event_type' => $event::eventType(),
            ]);

            return;
        }

        $formParameters = [
            'Action' => 'Publish',
            'Version' => '2010-03-31',
            'TopicArn' => $topicArn,
            'Message' => json_encode($event->toArray(), JSON_THROW_ON_ERROR),
        ];

        $payload = http_build_query($formParameters, '', '&', PHP_QUERY_RFC3986);
        $endpoint = $this->snsEndpoint();
        $headers = SignatureV4::signRequest(
            service: 'sns',
            region: $this->region(),
            accessKeyId: $this->accessKey(),
            secretAccessKey: $this->secretKey(),
            sessionToken: $this->sessionToken(),
            method: 'POST',
            uri: $endpoint,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
            ],
            queryParameters: [],
            payload: $payload,
        );

        $response = Http::withHeaders($headers)
            ->withBody($payload, 'application/x-www-form-urlencoded; charset=utf-8')
            ->send('POST', $endpoint);

        $this->assertSuccessfulPublish($response);
    }

    private function assertSuccessfulPublish(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Failed to publish event. HTTP %d: %s',
            $response->status(),
            trim($response->body()),
        ));
    }

    private function resolveTopicArn(AbstractEvent $event): string
    {
        $topics = config('services.event_bus.topics', []);

        if (is_array($topics)) {
            $topicArn = $topics[$event::eventType()] ?? null;

            if (is_string($topicArn) && trim($topicArn) !== '') {
                return trim($topicArn);
            }
        }

        $legacyTopicArn = config('services.event_bus.'.$event::topicArnConfigKey(), '');

        return is_string($legacyTopicArn) ? trim($legacyTopicArn) : '';
    }

    private function snsEndpoint(): string
    {
        $configuredEndpoint = trim((string) config('services.event_bus.endpoint', ''));

        if ($configuredEndpoint !== '') {
            return rtrim($configuredEndpoint, '/').'/';
        }

        return sprintf('https://sns.%s.amazonaws.com/', $this->region());
    }

    private function region(): string
    {
        $region = trim((string) config('services.event_bus.region', 'us-east-1'));

        return $region === '' ? 'us-east-1' : $region;
    }

    private function accessKey(): string
    {
        $key = trim((string) config('services.event_bus.key', ''));

        if ($key === '') {
            throw new RuntimeException('AWS access key is not configured for event publishing.');
        }

        return $key;
    }

    private function secretKey(): string
    {
        $secret = trim((string) config('services.event_bus.secret', ''));

        if ($secret === '') {
            throw new RuntimeException('AWS secret key is not configured for event publishing.');
        }

        return $secret;
    }

    private function sessionToken(): ?string
    {
        $token = trim((string) config('services.event_bus.session_token', ''));

        return $token === '' ? null : $token;
    }
}
