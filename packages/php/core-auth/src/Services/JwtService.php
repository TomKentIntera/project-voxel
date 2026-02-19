<?php

declare(strict_types=1);

namespace Interadigital\CoreAuth\Services;

use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\AuthToken;
use Interadigital\CoreModels\Models\User;
use RuntimeException;

class JwtService
{
    private const ALGORITHM = 'HS256';

    private const TYPE_ACCESS = 'access';

    private const TYPE_REFRESH = 'refresh';

    /**
     * Issue a short-lived access token for the given user.
     */
    public function issueToken(User $user): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + ($this->ttlMinutes() * 60);

        $payload = [
            'sub' => $user->getKey(),
            'type' => self::TYPE_ACCESS,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        return $this->encode($payload);
    }

    /**
     * Issue a long-lived refresh token for the given user and persist its
     * hash in the database so it can be revoked later.
     */
    public function issueRefreshToken(User $user): string
    {
        $issuedAt = time();
        $refreshTtlSeconds = $this->refreshTtlMinutes() * 60;
        $expiresAt = $issuedAt + $refreshTtlSeconds;

        $payload = [
            'sub' => $user->getKey(),
            'type' => self::TYPE_REFRESH,
            'jti' => Str::random(32),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        $rawToken = $this->encode($payload);

        AuthToken::create([
            'user_id' => $user->getKey(),
            'token_hash' => AuthToken::hashToken($rawToken),
            'expires_at' => now()->addSeconds($refreshTtlSeconds),
        ]);

        return $rawToken;
    }

    /**
     * Resolve a user ID from a valid access token.
     */
    public function userIdFromToken(string $token): ?int
    {
        $payload = $this->decode($token);

        if ($payload === null) {
            return null;
        }

        // Only accept access tokens (or legacy tokens without a type claim).
        $type = $payload['type'] ?? self::TYPE_ACCESS;
        if ($type !== self::TYPE_ACCESS) {
            return null;
        }

        if (! isset($payload['sub']) || ! is_numeric($payload['sub'])) {
            return null;
        }

        return (int) $payload['sub'];
    }

    /**
     * Validate a refresh token against both the JWT signature/expiry and the
     * database. Returns the user ID if valid, null otherwise.
     */
    public function userIdFromRefreshToken(string $token): ?int
    {
        $payload = $this->decode($token);

        if ($payload === null) {
            return null;
        }

        if (($payload['type'] ?? null) !== self::TYPE_REFRESH) {
            return null;
        }

        if (! isset($payload['sub']) || ! is_numeric($payload['sub'])) {
            return null;
        }

        // Verify the token exists in the database and has not been revoked.
        $authToken = AuthToken::where('token_hash', AuthToken::hashToken($token))->first();

        if ($authToken === null || ! $authToken->isActive()) {
            return null;
        }

        return (int) $payload['sub'];
    }

    /**
     * Revoke a refresh token so it can no longer be used.
     */
    public function revokeRefreshToken(string $token): void
    {
        $authToken = AuthToken::where('token_hash', AuthToken::hashToken($token))->first();

        $authToken?->revoke();
    }

    /**
     * Revoke all active refresh tokens for a user (e.g. on logout-everywhere).
     */
    public function revokeAllRefreshTokens(User $user): void
    {
        AuthToken::where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Return the access-token TTL in minutes.
     */
    public function ttlMinutes(): int
    {
        $ttl = config('jwt.ttl', 60 * 24 * 7);

        return max(1, (int) $ttl);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];

        $headerSegment = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $payloadSegment = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signatureSegment = $this->signatureForSegments($headerSegment, $payloadSegment);

        return implode('.', [$headerSegment, $payloadSegment, $signatureSegment]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(string $token): ?array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return null;
        }

        [$headerSegment, $payloadSegment, $signatureSegment] = $segments;
        $expectedSignature = $this->signatureForSegments($headerSegment, $payloadSegment);

        if (! hash_equals($expectedSignature, $signatureSegment)) {
            return null;
        }

        $headerJson = $this->base64UrlDecode($headerSegment);
        $payloadJson = $this->base64UrlDecode($payloadSegment);

        if ($headerJson === null || $payloadJson === null) {
            return null;
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (! is_array($header) || ! is_array($payload)) {
            return null;
        }

        if (($header['alg'] ?? null) !== self::ALGORITHM) {
            return null;
        }

        if (! isset($payload['exp']) || ! is_numeric($payload['exp'])) {
            return null;
        }

        if ((int) $payload['exp'] <= time()) {
            return null;
        }

        return $payload;
    }

    private function signatureForSegments(string $headerSegment, string $payloadSegment): string
    {
        $signature = hash_hmac(
            'sha256',
            $headerSegment.'.'.$payloadSegment,
            $this->signingSecret(),
            true
        );

        return $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $normalizedValue = strtr($value, '-_', '+/');
        $paddingLength = strlen($normalizedValue) % 4;

        if ($paddingLength > 0) {
            $normalizedValue .= str_repeat('=', 4 - $paddingLength);
        }

        $decoded = base64_decode($normalizedValue, true);

        return $decoded === false ? null : $decoded;
    }

    private function signingSecret(): string
    {
        $configuredSecret = config('jwt.secret');

        if (is_string($configuredSecret) && $configuredSecret !== '') {
            return $configuredSecret;
        }

        $appKey = config('app.key');

        if (is_string($appKey) && $appKey !== '') {
            if (Str::startsWith($appKey, 'base64:')) {
                $decoded = base64_decode(Str::after($appKey, 'base64:'), true);

                if ($decoded !== false) {
                    return $decoded;
                }
            }

            return $appKey;
        }

        if (app()->environment('testing')) {
            return 'testing-jwt-secret';
        }

        throw new RuntimeException('JWT signing secret is not configured.');
    }

    private function refreshTtlMinutes(): int
    {
        $ttl = config('jwt.refresh_ttl', 60 * 24 * 30);

        return max(1, (int) $ttl);
    }
}

