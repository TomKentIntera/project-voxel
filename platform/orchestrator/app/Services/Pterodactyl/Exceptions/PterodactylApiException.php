<?php

declare(strict_types=1);

namespace App\Services\Pterodactyl\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class PterodactylApiException extends RuntimeException
{
    /**
     * @param array<string, mixed>|null $errorPayload
     */
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?array $errorPayload = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function fromResponse(string $method, string $endpoint, Response $response): self
    {
        $statusCode = $response->status();
        $payload = $response->json();
        $errorPayload = is_array($payload) ? $payload : null;
        $detail = self::extractErrorDetail($errorPayload);

        $message = sprintf(
            'Pterodactyl API request failed (%s %s) with status %d%s',
            strtoupper($method),
            $endpoint,
            $statusCode,
            $detail !== null ? ': '.$detail : '.',
        );

        return new self($message, $statusCode, $errorPayload);
    }

    public static function invalidJson(string $method, string $endpoint): self
    {
        return new self(
            sprintf(
                'Pterodactyl API request succeeded (%s %s) but returned an invalid JSON payload.',
                strtoupper($method),
                $endpoint,
            ),
        );
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function errorPayload(): ?array
    {
        return $this->errorPayload;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private static function extractErrorDetail(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        $errors = $payload['errors'] ?? null;

        if (is_array($errors)) {
            foreach ($errors as $error) {
                $detail = is_array($error) ? ($error['detail'] ?? null) : null;

                if (is_string($detail) && trim($detail) !== '') {
                    return trim($detail);
                }
            }
        }

        $message = $payload['message'] ?? null;

        return is_string($message) && trim($message) !== '' ? trim($message) : null;
    }
}
