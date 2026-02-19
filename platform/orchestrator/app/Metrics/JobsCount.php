<?php

declare(strict_types=1);

namespace App\Metrics;

use Illuminate\Support\Facades\DB;

class JobsCount extends Metric
{
    public function key(): string
    {
        return 'jobs_count';
    }

    public function label(): string
    {
        return 'Pending Jobs';
    }

    public function value(): int
    {
        return (int) DB::table('jobs')->count();
    }
}

