<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Interadigital\CoreAuth\Http\Controllers\AuthController;
use Interadigital\CoreModels\Enums\UserRole;
use Interadigital\CoreModels\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthController extends AuthController
{
    /**
     * Only allow users with the admin role to log in to the orchestrator.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->isAdmin()) {
            return response()->json([
                'message' => 'Access denied. Administrator privileges required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json($this->authPayload($user));
    }

    /**
     * Reject refresh-token rotation for non-admin users (in case a user was
     * demoted after the original login).
     */
    public function refresh(Request $request): JsonResponse
    {
        $response = parent::refresh($request);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return $response;
        }

        /** @var array{user?: array{role?: string}} $data */
        $data = $response->getData(true);
        $role = $data['user']['role'] ?? null;

        if ($role !== UserRole::ADMIN->value && $role !== UserRole::ADMIN) {
            return response()->json([
                'message' => 'Access denied. Administrator privileges required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $response;
    }

    /**
     * Prevent registration on the orchestrator entirely.
     */
    public function register(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Registration is not available on this service.',
        ], Response::HTTP_FORBIDDEN);
    }
}

