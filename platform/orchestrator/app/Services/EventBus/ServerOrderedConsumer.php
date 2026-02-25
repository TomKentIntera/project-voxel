<?php

declare(strict_types=1);

namespace App\Services\EventBus;

use App\Jobs\ProcessServerLifecycleEventProcessor;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Interadigital\CoreEvents\Aws\SignatureV4;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class ServerOrderedConsumer
{
    public function __construct(
        private readonly ServerLifecycleEventProcessorMap $processorMap,
    ) {}

    public function consumeBatch(int $maxMessages = 10, int $waitTimeSeconds = 20): int
    {
        $queueUrl = $this->queueUrl();

        if ($queueUrl === '') {
            Log::warning('Skipping server ordered consume because queue URL is not configured.');

            return 0;
        }

        $messages = $this->receiveMessages(
            queueUrl: $queueUrl,
            maxMessages: max(1, min($maxMessages, 10)),
            waitTimeSeconds: max(0, min($waitTimeSeconds, 20)),
        );

        $processedCount = 0;

        foreach ($messages as $message) {
            $receiptHandle = $message['receipt_handle'] ?? null;
            $body = $message['body'] ?? null;

            if (! is_string($receiptHandle) || trim($receiptHandle) === '' || ! is_string($body)) {
                continue;
            }

            try {
                $eventPayload = $this->extractEventPayload($body);
                $eventType = $this->extractEventType($eventPayload);

                if ($eventType === null) {
                    Log::warning('Dropping malformed server ordered message.', [
                        'message_id' => $message['message_id'] ?? null,
                    ]);

                    $this->deleteMessage($queueUrl, $receiptHandle);
                    continue;
                }

                if (is_array($eventPayload) && $this->fanOutToEventConsumers($eventType, $eventPayload)) {
                    $processedCount++;
                }

                $this->deleteMessage($queueUrl, $receiptHandle);
            } catch (Throwable $exception) {
                Log::error('Failed to process server ordered message.', [
                    'message_id' => $message['message_id'] ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $processedCount;
    }

    /**
     * @return list<array{message_id: string, receipt_handle: string, body: string}>
     */
    private function receiveMessages(string $queueUrl, int $maxMessages, int $waitTimeSeconds): array
    {
        $response = $this->signedSqsPost($queueUrl, [
            'Action' => 'ReceiveMessage',
            'Version' => '2012-11-05',
            'MaxNumberOfMessages' => (string) $maxMessages,
            'WaitTimeSeconds' => (string) $waitTimeSeconds,
            'MessageAttributeName.1' => 'All',
            'AttributeName.1' => 'All',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'SQS ReceiveMessage failed with HTTP %d: %s',
                $response->status(),
                trim($response->body()),
            ));
        }

        $xml = @simplexml_load_string($response->body());
        if (! ($xml instanceof SimpleXMLElement)) {
            return [];
        }

        $messages = [];
        foreach ($xml->ReceiveMessageResult->Message ?? [] as $message) {
            $messageId = trim((string) ($message->MessageId ?? ''));
            $receiptHandle = trim((string) ($message->ReceiptHandle ?? ''));
            $body = (string) ($message->Body ?? '');

            if ($receiptHandle === '') {
                continue;
            }

            $messages[] = [
                'message_id' => $messageId,
                'receipt_handle' => $receiptHandle,
                'body' => $body,
            ];
        }

        return $messages;
    }

    private function deleteMessage(string $queueUrl, string $receiptHandle): void
    {
        $response = $this->signedSqsPost($queueUrl, [
            'Action' => 'DeleteMessage',
            'Version' => '2012-11-05',
            'ReceiptHandle' => $receiptHandle,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'SQS DeleteMessage failed with HTTP %d: %s',
                $response->status(),
                trim($response->body()),
            ));
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractEventPayload(string $body): ?array
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['Type'], $decoded['Message']) && is_string($decoded['Message'])) {
            $innerDecoded = json_decode($decoded['Message'], true);

            if (! is_array($innerDecoded)) {
                return null;
            }

            $decoded = $innerDecoded;
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed>|null $eventPayload
     */
    private function extractEventType(?array $eventPayload): ?string
    {
        if ($eventPayload === null) {
            return null;
        }

        $eventType = trim((string) ($eventPayload['event_type'] ?? ''));

        return $eventType !== '' ? $eventType : null;
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function fanOutToEventConsumers(string $eventType, array $eventPayload): bool
    {
        $processorKeys = $this->processorMap->processorKeysForEventType($eventType);
        if ($processorKeys === []) {
            return false;
        }

        foreach ($processorKeys as $processorKey) {
            ProcessServerLifecycleEventProcessor::dispatchSync($processorKey, $eventPayload);
        }

        return true;
    }

    /**
     * @param array<string, string> $formParameters
     */
    private function signedSqsPost(string $queueUrl, array $formParameters): Response
    {
        $payload = http_build_query($formParameters, '', '&', PHP_QUERY_RFC3986);
        $headers = SignatureV4::signRequest(
            service: 'sqs',
            region: $this->region(),
            accessKeyId: $this->accessKey(),
            secretAccessKey: $this->secretKey(),
            sessionToken: $this->sessionToken(),
            method: 'POST',
            uri: $queueUrl,
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
            ],
            queryParameters: [],
            payload: $payload,
        );

        return Http::withHeaders($headers)
            ->withBody($payload, 'application/x-www-form-urlencoded; charset=utf-8')
            ->send('POST', $queueUrl);
    }

    private function queueUrl(): string
    {
        return trim((string) config('services.event_bus.server_orders_queue_url', ''));
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
            throw new RuntimeException('AWS access key is not configured for event consumption.');
        }

        return $key;
    }

    private function secretKey(): string
    {
        $secret = trim((string) config('services.event_bus.secret', ''));

        if ($secret === '') {
            throw new RuntimeException('AWS secret key is not configured for event consumption.');
        }

        return $secret;
    }

    private function sessionToken(): ?string
    {
        $token = trim((string) config('services.event_bus.session_token', ''));

        return $token === '' ? null : $token;
    }
}
