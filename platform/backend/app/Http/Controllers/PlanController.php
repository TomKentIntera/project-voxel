<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LocationsCacheReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PlanController extends Controller
{
    public function __construct(
        private readonly LocationsCacheReader $locationsCacheReader
    ) {}

    /**
     * Return all available plans with public-facing data.
     *
     * @OA\Get(
     *     path="/api/plans",
     *     operationId="getPlans",
     *     tags={"Plans"},
     *     summary="List public plan catalogue and recommender options",
     *     @OA\Response(
     *         response=200,
     *         description="Public plans payload",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"plans", "locations", "planRecommender", "modpacks", "subdomain_domains"},
     *             @OA\Property(
     *                 property="plans",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/PublicPlan")
     *             ),
     *             @OA\Property(
     *                 property="locations",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/LocationOption")
     *             ),
     *             @OA\Property(property="planRecommender", ref="#/components/schemas/PlanRecommender"),
     *             @OA\Property(
     *                 property="modpacks",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ModpackSummary")
     *             ),
     *             @OA\Property(
     *                 property="subdomain_domains",
     *                 type="array",
     *                 @OA\Items(type="string", example="intera.gg")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $locationsCache = $this->locationsCacheReader->maxFreeMemoryByLocationShortCode();
        $cachedLocations = $this->locationsCacheReader->locations();

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

        $cachedLocationsByShort = collect($cachedLocations)->keyBy('short');

        $locations = collect(config('plans.locations'))->map(
            function (array $location, string $key) use ($cachedLocationsByShort): array {
                $shortCode = trim((string) ($location['ptero_location'] ?? ''));
                $cached = $shortCode !== '' ? $cachedLocationsByShort->get($shortCode) : null;

                return [
                    'key' => $key,
                    'title' => $location['title'] ?? $key,
                    'flag' => $location['flag'] ?? '',
                    'short' => $shortCode !== '' ? $shortCode : null,
                    'long' => is_array($cached) ? ($cached['long'] ?? '') : '',
                    'maxFreeMemory' => is_array($cached) ? (int) ($cached['maxFreeMemory'] ?? 0) : 0,
                ];
            }
        )->values()->all();

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

        $subdomainDomains = collect(config('subdomains.allowed_domains', []))
            ->filter(fn (mixed $domain): bool => is_string($domain) && trim($domain) !== '')
            ->map(fn (string $domain): string => strtolower(trim($domain)))
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'plans' => $plans,
            'locations' => $locations,
            'planRecommender' => config('plans.planRecommender'),
            'modpacks' => $modpacks,
            'subdomain_domains' => $subdomainDomains,
        ]);
    }

    /**
     * Accept players, version, and type selections, sum their weights to
     * determine the recommended GB, then return the best-fit plan.
     *
     * @OA\Get(
     *     path="/api/plans/recommend",
     *     operationId="recommendPlan",
     *     tags={"Plans"},
     *     summary="Get recommended plan from weighted selections",
     *     @OA\Parameter(
     *         name="players",
     *         in="query",
     *         required=true,
     *         description="Selected player count range label from planRecommender.players.",
     *         @OA\Schema(type="string", example="10-20")
     *     ),
     *     @OA\Parameter(
     *         name="version",
     *         in="query",
     *         required=true,
     *         description="Selected version range label from planRecommender.versions.",
     *         @OA\Schema(type="string", example="1.17+")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=true,
     *         description="Selected server type label from planRecommender.types.",
     *         @OA\Schema(type="string", example="Forge Modpack")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommended plan and computed score",
     *         @OA\JsonContent(ref="#/components/schemas/PlanRecommendation")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid query data or unsupported selection",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiValidationErrors"),
     *                 @OA\Schema(
     *                     type="object",
     *                     required={"error"},
     *                     @OA\Property(property="error", type="string", example="Invalid selection.")
     *                 )
     *             }
     *         )
     *     )
     * )
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
