<?php

declare(strict_types=1);

namespace App\Metrics;

use Illuminate\Support\Facades\DB;

class RevenueMonthly extends Metric
{
    public function key(): string
    {
        return 'revenue_mtd';
    }

    public function label(): string
    {
        return 'Revenue (MTD)';
    }

    public function value(): float
    {
        // Sum completed payments for the current calendar month.
        // The `payments` table may not exist yet — fall back to 0 gracefully.
        try {
            if (! DB::getSchemaBuilder()->hasTable('payments')) {
                return 0;
            }

            return (float) DB::table('payments')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
        } catch (\Throwable) {
            return 0;
        }
    }

    public function format(): string
    {
        return 'currency';
    }

    public function prefix(): ?string
    {
        return '$';
    }
}

