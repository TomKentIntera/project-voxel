<?php

declare(strict_types=1);

namespace Interadigital\CoreEvents\Events;

use InvalidArgumentException;

final class ServerOrdered extends AbstractEvent
{
    public const EVENT_TYPE = 'server.ordered.v1';
    public const TOPIC_ARN_CONFIG_KEY = 'server_orders_topic_arn';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        string $eventId,
        string $occurredAt,
        public readonly int $serverId,
        public readonly string $serverUuid,
        public readonly int $userId,
        public readonly string $plan,
        public readonly array $config,
        public readonly ?string $stripeSubscriptionId = null,
        ?string $correlationId = null,
    ) {
        parent::__construct(
            eventId: $eventId,
            occurredAt: $occurredAt,
            correlationId: $correlationId,
        );

        if (trim($this->serverUuid) === '') {
            throw new InvalidArgumentException('Event field [serverUuid] cannot be empty.');
        }

        if (trim($this->plan) === '') {
            throw new InvalidArgumentException('Event field [plan] cannot be empty.');
        }
    }

    public static function eventType(): string
    {
        return self::EVENT_TYPE;
    }

    public static function topicArnConfigKey(): string
    {
        return self::TOPIC_ARN_CONFIG_KEY;
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
    protected function payload(): array
    {
        return [
            'server_id' => $this->serverId,
            'server_uuid' => $this->serverUuid,
            'user_id' => $this->userId,
            'plan' => $this->plan,
            'config' => $this->config,
            'stripe_subscription_id' => $this->stripeSubscriptionId,
        ];
    }
}
