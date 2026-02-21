<?php

declare(strict_types=1);

namespace Interadigital\CoreEvents\Events;

use InvalidArgumentException;

final class ServerOrdered
{
    public const EVENT_TYPE = 'server.ordered.v1';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $occurredAt,
        public readonly int $serverId,
        public readonly string $serverUuid,
        public readonly int $userId,
        public readonly string $plan,
        public readonly array $config,
        public readonly ?string $stripeSubscriptionId = null,
        public readonly ?string $correlationId = null,
    ) {
        $this->assertNonEmpty($this->eventId, 'eventId');
        $this->assertNonEmpty($this->occurredAt, 'occurredAt');
        $this->assertNonEmpty($this->serverUuid, 'serverUuid');
        $this->assertNonEmpty($this->plan, 'plan');
    }

    public static function eventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $eventType = $payload['event_type'] ?? self::EVENT_TYPE;

        if (! is_string($eventType) || $eventType !== self::EVENT_TYPE) {
            throw new InvalidArgumentException(sprintf(
                'Unexpected event type [%s], expected [%s].',
                is_scalar($eventType) ? (string) $eventType : gettype($eventType),
                self::EVENT_TYPE,
            ));
        }

        $eventId = self::nonEmptyString($payload['event_id'] ?? null, 'event_id');
        $occurredAt = self::nonEmptyString($payload['occurred_at'] ?? null, 'occurred_at');
        $serverId = self::integer($payload['server_id'] ?? null, 'server_id');
        $serverUuid = self::nonEmptyString($payload['server_uuid'] ?? null, 'server_uuid');
        $userId = self::integer($payload['user_id'] ?? null, 'user_id');
        $plan = self::nonEmptyString($payload['plan'] ?? null, 'plan');
        $config = self::arrayValue($payload['config'] ?? null, 'config');

        $stripeSubscriptionId = self::optionalNonEmptyString(
            $payload['stripe_subscription_id'] ?? null,
            'stripe_subscription_id',
        );
        $correlationId = self::optionalNonEmptyString(
            $payload['correlation_id'] ?? null,
            'correlation_id',
        );

        return new self(
            eventId: $eventId,
            occurredAt: $occurredAt,
            serverId: $serverId,
            serverUuid: $serverUuid,
            userId: $userId,
            plan: $plan,
            config: $config,
            stripeSubscriptionId: $stripeSubscriptionId,
            correlationId: $correlationId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => self::EVENT_TYPE,
            'occurred_at' => $this->occurredAt,
            'server_id' => $this->serverId,
            'server_uuid' => $this->serverUuid,
            'user_id' => $this->userId,
            'plan' => $this->plan,
            'config' => $this->config,
            'stripe_subscription_id' => $this->stripeSubscriptionId,
            'correlation_id' => $this->correlationId,
        ];
    }

    private function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Event field [%s] cannot be empty.', $field));
        }
    }

    private static function nonEmptyString(mixed $value, string $field): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf(
                'Event field [%s] must be a non-empty string.',
                $field,
            ));
        }

        return trim($value);
    }

    private static function optionalNonEmptyString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'Event field [%s] must be a string when provided.',
                $field,
            ));
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function integer(mixed $value, string $field): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new InvalidArgumentException(sprintf(
            'Event field [%s] must be an integer.',
            $field,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayValue(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'Event field [%s] must be an array.',
                $field,
            ));
        }

        return $value;
    }
}
