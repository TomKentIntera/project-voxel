<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateNodeTelemetryToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedToken = $this->resolveTokenFromRequest($request);
        $expectedToken = $this->resolveExpectedTokenForNode((string) $request->route('node_id', ''));

        if ($providedToken === null || $expectedToken === null || ! hash_equals($expectedToken, $providedToken)) {
            return $this->unauthenticatedResponse();
        }

        return $next($request);
    }

    private function resolveTokenFromRequest(Request $request): ?string
    {
        $bearerToken = trim((string) $request->bearerToken());

        if ($bearerToken !== '') {
            return $bearerToken;
        }

        $headerToken = trim((string) $request->header('X-Node-Token', ''));

        return $headerToken !== '' ? $headerToken : null;
    }

    private function resolveExpectedTokenForNode(string $nodeId): ?string
    {
        $tokenMap = config('services.node_telemetry.tokens', []);

        if (is_array($tokenMap) && $nodeId !== '' && array_key_exists($nodeId, $tokenMap)) {
            $nodeToken = trim((string) $tokenMap[$nodeId]);

            if ($nodeToken !== '') {
                return $nodeToken;
            }
        }

        $fallbackToken = trim((string) config('services.node_telemetry.token', ''));

        return $fallbackToken !== '' ? $fallbackToken : null;
    }

    private function unauthenticatedResponse(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
