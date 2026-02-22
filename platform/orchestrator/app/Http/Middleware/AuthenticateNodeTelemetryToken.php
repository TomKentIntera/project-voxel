<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Models\Node;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateNodeTelemetryToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedToken = $this->resolveTokenFromRequest($request);
        $nodeId = trim((string) $request->route('node_id', ''));

        if ($providedToken === null || $nodeId === '') {
            return $this->unauthenticatedResponse();
        }

        $node = Node::find($nodeId);

        if (! ($node instanceof Node) || ! $node->matchesToken($providedToken)) {
            return $this->unauthenticatedResponse();
        }

        $request->attributes->set('node', $node);

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

    private function unauthenticatedResponse(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
