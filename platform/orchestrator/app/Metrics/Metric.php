<?php

declare(strict_types=1);

namespace App\Metrics;

abstract class Metric
{
    /**
     * Unique key used to identify this metric in API responses.
     */
    abstract public function key(): string;

    /**
     * Human-readable label shown in the UI.
     */
    abstract public function label(): string;

    /**
     * Compute the current value of this metric.
     */
    abstract public function value(): int|float;

    /**
     * Display format hint for the frontend.
     *
     * Supported: "number", "currency"
     */
    public function format(): string
    {
        return 'number';
    }

    /**
     * Optional prefix for formatted display (e.g. "$" for currency).
     */
    public function prefix(): ?string
    {
        return null;
    }

    /**
     * Optional suffix for formatted display (e.g. "%" for percentages).
     */
    public function suffix(): ?string
    {
        return null;
    }

    /**
     * Serialize the metric for JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'value' => $this->value(),
            'format' => $this->format(),
            'prefix' => $this->prefix(),
            'suffix' => $this->suffix(),
        ];
    }
}

