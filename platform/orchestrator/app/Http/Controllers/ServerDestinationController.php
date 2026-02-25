<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Destination\ServerDestinationOrchestratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ServerDestinationController extends Controller
{
    public function __construct(
        private readonly ServerDestinationOrchestratorService $serverDestinationOrchestratorService
    ) {}

    /**
     * Resolve the best destination node for a plan.
     */
    public function resolve(Request $request): JsonResponse
    {
        /** @var list<string> $planNames */
        $planNames = collect(config('plans.planList', []))
            ->pluck('name')
            ->filter(fn (mixed $name): bool => is_string($name) && trim($name) !== '')
            ->map(fn (string $name): string => trim($name))
            ->unique()
            ->values()
            ->all();

        $validated = $request->validate([
            'plan' => ['required', 'string', Rule::in($planNames)],
            'region' => ['nullable', 'string', 'max:255'],
            'server_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->serverDestinationOrchestratorService->resolve(
                planName: (string) $validated['plan'],
                region: isset($validated['region']) ? (string) $validated['region'] : null,
                serverIdentifier: isset($validated['server_id']) ? (string) $validated['server_id'] : null,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}
