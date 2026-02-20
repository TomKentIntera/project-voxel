<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Models\RegionalProxy;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRegionalProxyToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->resolveTokenFromRequest($request);

        if ($token === null) {
            return $this->unauthenticatedResponse();
        }

        $regionalProxy = RegionalProxy::where('token_hash', RegionalProxy::hashToken($token))->first();

        if (! $regionalProxy instanceof RegionalProxy || ! $regionalProxy->matchesToken($token)) {
            return $this->unauthenticatedResponse();
        }

        $request->attributes->set('regionalProxy', $regionalProxy);

        return $next($request);
    }

    private function resolveTokenFromRequest(Request $request): ?string
    {
        $bearerToken = $request->bearerToken();

        if (is_string($bearerToken) && $bearerToken !== '') {
            return $bearerToken;
        }

        $headerToken = trim((string) $request->header('X-Regional-Proxy-Token', ''));

        return $headerToken !== '' ? $headerToken : null;
    }

    private function unauthenticatedResponse(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
