<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Reject requests from non-admin users with a 403 Forbidden response.
     *
     * This middleware should always be applied *after* `auth.jwt` so that
     * `$request->user()` is already resolved.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Access denied. Administrator privileges required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

