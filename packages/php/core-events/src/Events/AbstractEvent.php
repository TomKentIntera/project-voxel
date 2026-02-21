<?php

declare(strict_types=1);

namespace Interadigital\CoreEvents\Events;

use InvalidArgumentException;

abstract class AbstractEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $occurredAt,
        public readonly ?string $correlationId = null,
    ) {
        $this->assertNonEmpty($this->eventId, 'eventId');
        $this->assertNonEmpty($this->occurredAt, 'occurredAt');
    }

    abstract public static function eventType(): string;

    /**
     * Config key inside services.event_bus for this event topic.
     */
    abstract public static function topicArnConfigKey(): string;

    /**
     * Event-specific payload fields only.
     *
     * @return array<string, mixed>
     */
    abstract protected function payload(): array;

    /**
     * @return array<string, mixed>
     */
    final public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => static::eventType(),
            'occurred_at' => $this->occurredAt,
            'correlation_id' => $this->correlationId,
            ...$this->payload(),
        ];
    }

    protected static function nonEmptyString(mixed $value, string $field): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf(
                'Event field [%s] must be a non-empty string.',
                $field,
            ));
        }

        return trim($value);
    }

    protected static function optionalNonEmptyString(mixed $value, string $field): ?string
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

    protected static function integer(mixed $value, string $field): int
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
    protected static function arrayValue(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'Event field [%s] must be an array.',
                $field,
            ));
        }

        return $value;
    }

    private function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Event field [%s] cannot be empty.', $field));
        }
    }
}
