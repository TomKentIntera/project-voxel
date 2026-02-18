<?php

namespace App\Http\Controllers;

use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Interadigital\CoreModels\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        return response()->json(
            $this->authPayload($user),
            Response::HTTP_CREATED
        );
    }

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

        return response()->json($this->authPayload($user));
    }

    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $rawRefreshToken = $validated['refresh_token'];

        $userId = $this->jwtService->userIdFromRefreshToken($rawRefreshToken);

        if ($userId === null) {
            return response()->json([
                'message' => 'Invalid or expired refresh token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::find($userId);

        if ($user === null) {
            return response()->json([
                'message' => 'Invalid or expired refresh token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Rotate: revoke the old refresh token, issue a fresh pair.
        $this->jwtService->revokeRefreshToken($rawRefreshToken);

        return response()->json($this->authPayload($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $this->jwtService->revokeRefreshToken($validated['refresh_token']);

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function authPayload(User $user): array
    {
        $expiresInSeconds = $this->jwtService->ttlMinutes() * 60;

        return [
            'token' => $this->jwtService->issueToken($user),
            'refresh_token' => $this->jwtService->issueRefreshToken($user),
            'token_type' => 'Bearer',
            'expires_in' => $expiresInSeconds,
            'expires_at' => time() + $expiresInSeconds,
            'user' => $user,
        ];
    }
}
