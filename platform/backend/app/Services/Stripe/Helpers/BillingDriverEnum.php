<?php

declare(strict_types=1);

namespace App\Services\Stripe\Helpers;

enum BillingDriverEnum: string
{
    case Cashier = 'cashier';
    case Custom = 'custom';

    public static function fromConfig(mixed $value): self
    {
        $normalized = is_string($value) ? strtolower(trim($value)) : '';

        return match ($normalized) {
            self::Cashier->value => self::Cashier,
            default => self::Custom,
        };
    }
}
