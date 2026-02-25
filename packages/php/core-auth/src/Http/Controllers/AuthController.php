<?php

declare(strict_types=1);

namespace Interadigital\CoreAuth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Interadigital\CoreAuth\Services\JwtService;
use Interadigital\CoreModels\Enums\UserRole;
use Interadigital\CoreModels\Models\User;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     operationId="registerUser",
     *     tags={"Auth"},
     *     summary="Register a new user and issue tokens",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"username", "first_name", "last_name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="username", type="string", maxLength=255, example="player123"),
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="Alex"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="alex@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="securePassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", minLength=8, example="securePassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered",
     *         @OA\JsonContent(ref="#/components/schemas/AuthPayload")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ApiValidationErrors")
     *     )
     * )
     */
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
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::CUSTOMER->value,
        ]);

        return response()->json(
            $this->authPayload($user),
            Response::HTTP_CREATED
        );
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     operationId="loginUser",
     *     tags={"Auth"},
     *     summary="Authenticate a user and issue tokens",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="alex@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="securePassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated",
     *         @OA\JsonContent(ref="#/components/schemas/AuthPayload")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ApiValidationErrors")
     *     )
     * )
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

        return response()->json($this->authPayload($user));
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     operationId="refreshAuthToken",
     *     tags={"Auth"},
     *     summary="Exchange a refresh token for a new token pair",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="zGfJ1yR8zQq2RUDN7h4k...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="New token pair issued",
     *         @OA\JsonContent(ref="#/components/schemas/AuthPayload")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired refresh token",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ApiValidationErrors")
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     operationId="logoutUser",
     *     tags={"Auth"},
     *     summary="Revoke a refresh token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="zGfJ1yR8zQq2RUDN7h4k...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Refresh token revoked",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ApiValidationErrors")
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     operationId="getAuthenticatedUser",
     *     tags={"Auth"},
     *     summary="Get the currently authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Current user",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"user"},
     *             @OA\Property(property="user", ref="#/components/schemas/AuthUser")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function authPayload(User $user): array
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

