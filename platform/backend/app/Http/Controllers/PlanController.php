<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Return all available plans with public-facing data.
     */
    public function index(): JsonResponse
    {
        $locationsCache = $this->getLocationsCache();

        $plans = collect(config('plans.planList'))->map(function (array $plan) use ($locationsCache): array {
            $availability = [];
            foreach ($plan['locations'] as $locKey) {
                $pteroLocation = config("plans.locations.{$locKey}.ptero_location");
                $maxFreeMemory = $locationsCache[$pteroLocation] ?? 0;
                $availability[$locKey] = $maxFreeMemory >= 1024 * $plan['ram'];
            }

            return [
                'name' => $plan['name'],
                'title' => $plan['title'],
                'icon' => $plan['icon'],
                'ram' => $plan['ram'],
                'displayPrice' => $plan['displayPrice'],
                'bullets' => $plan['bullets_xx'],
                'showDefaultPlans' => $plan['showDefaultPlans'],
                'modpacks' => $plan['modpacks'] ?? [],
                'locations' => $plan['locations'],
                'ribbon' => $plan['ribbon'],
                'availability' => $availability,
            ];
        })->values()->all();

        $locations = collect(config('plans.locations'))->map(function (array $location, string $key): array {
            return [
                'key' => $key,
                'title' => $location['title'],
                'flag' => $location['flag'],
            ];
        })->values()->all();

        // Build modpacks list with resolved starting prices
        $modpacks = collect(config('plans.modpacks', []))->map(function (array $modpack): array {
            return [
                'slug' => $modpack['slug'],
                'name' => $modpack['name'],
                'heading' => $modpack['heading'],
                'description' => $modpack['description'],
                'headerClass' => $modpack['headerClass'],
                'startingPlan' => $modpack['startingPlan'],
                'modId' => $modpack['modId'],
            ];
        })->values()->all();

        return response()->json([
            'plans' => $plans,
            'locations' => $locations,
            'planRecommender' => config('plans.planRecommender'),
            'modpacks' => $modpacks,
        ]);
    }

    /**
     * Read the cached locations JSON and build a map of
     * ptero location short code → maxFreeMemory (MB).
     *
     * @return array<string, int>
     */
    private function getLocationsCache(): array
    {
        $map = [];
        $path = storage_path('app/locations.json');

        if (! file_exists($path)) {
            return $map;
        }

        $raw = json_decode(file_get_contents($path), true);

        foreach ($raw['locations'] ?? [] as $loc) {
            $map[$loc['short']] = (int) ($loc['maxFreeMemory'] ?? 0);
        }

        return $map;
    }

    /**
     * Accept players, version, and type selections, sum their weights to
     * determine the recommended GB, then return the best-fit plan.
     */
    public function recommend(Request $request): JsonResponse
    {
        $request->validate([
            'players' => ['required', 'string'],
            'version' => ['required', 'string'],
            'type' => ['required', 'string'],
        ]);

        $recommender = config('plans.planRecommender');

        $playersWeight = $this->findWeight($recommender['players'], $request->input('players'));
        $versionWeight = $this->findWeight($recommender['versions'], $request->input('version'));
        $typeWeight = $this->findWeight($recommender['types'], $request->input('type'));

        if ($playersWeight === null || $versionWeight === null || $typeWeight === null) {
            return response()->json(['error' => 'Invalid selection.'], 422);
        }

        $score = max(1, $playersWeight + $versionWeight + $typeWeight);

        // Walk the score thresholds (ascending) and pick the last one
        // whose min_score is still <= the computed score.
        $thresholds = config('plans.scoreThresholds', []);
        $planName = null;

        foreach ($thresholds as $entry) {
            if ($score >= $entry['min_score']) {
                $planName = $entry['plan'];
            }
        }

        // Resolve the full plan definition from the plan list
        $plans = collect(config('plans.planList'))->keyBy('name');
        $recommended = $planName !== null ? $plans->get($planName) : null;

        // Fallback to the largest plan if nothing matched
        if ($recommended === null) {
            $recommended = $plans->sortBy('ram')->last();
        }

        return response()->json([
            'score' => $score,
            'plan' => [
                'name' => $recommended['name'],
                'title' => $recommended['title'],
                'icon' => $recommended['icon'],
                'ram' => $recommended['ram'],
                'displayPrice' => $recommended['displayPrice'],
                'ribbon' => $recommended['ribbon'],
            ],
        ]);
    }

    /**
     * Look up the weight for a given label in an options array.
     */
    private function findWeight(array $options, string $label): ?int
    {
        foreach ($options as $option) {
            if ($option['label'] === $label) {
                return $option['weight'];
            }
        }

        return null;
    }
}
