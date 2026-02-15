<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class JwtService
{
    private const ALGORITHM = 'HS256';

    public function issueToken(User $user): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + ($this->ttlMinutes() * 60);

        $payload = [
            'sub' => $user->getKey(),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        return $this->encode($payload);
    }

    public function userIdFromToken(string $token): ?int
    {
        $payload = $this->decode($token);

        if (! is_array($payload) || ! isset($payload['sub']) || ! is_numeric($payload['sub'])) {
            return null;
        }

        return (int) $payload['sub'];
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

    private function ttlMinutes(): int
    {
        $ttl = config('jwt.ttl', 60 * 24 * 7);

        return max(1, (int) $ttl);
    }
}
