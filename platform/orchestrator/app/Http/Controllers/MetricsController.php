<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Metrics\JobsCount;
use App\Metrics\Metric;
use App\Metrics\RevenueMonthly;
use App\Metrics\ServersCount;
use App\Metrics\UsersCount;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    /**
     * Registered metrics.
     *
     * Add new Metric subclasses here to expose them via the API.
     *
     * @var list<class-string<Metric>>
     */
    private array $metrics = [
        ServersCount::class,
        UsersCount::class,
        JobsCount::class,
        RevenueMonthly::class,
    ];

    /**
     * Return all registered metrics.
     */
    public function index(): JsonResponse
    {
        $data = array_map(
            static fn (string $class): array => (new $class())->toArray(),
            $this->metrics,
        );

        return response()->json(['data' => $data]);
    }
}

