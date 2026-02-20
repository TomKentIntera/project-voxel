<?php

declare(strict_types=1);

namespace App\Services\Pterodactyl\Services;

use App\Services\Pterodactyl\Exceptions\PterodactylApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class PterodactylApiClient
{
    private const ACCEPT_HEADER = 'Application/vnd.pterodactyl.v1+json';

    private const APPLICATION_API_PREFIX = '/api/application';

    private const CLIENT_API_PREFIX = '/api/client';

    /**
     * @var list<string>
     */
    private const ALLOWED_POWER_SIGNALS = ['start', 'stop', 'restart', 'kill'];

    /**
     * @return list<array<string, mixed>>
     */
    public function listLocations(bool $includeNodes = false): array
    {
        $query = $includeNodes ? ['include' => 'nodes'] : [];

        return $this->unwrapDataList($this->applicationSend('GET', '/locations', query: $query));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listNodes(array $includes = []): array
    {
        return $this->unwrapDataList(
            $this->applicationSend('GET', '/nodes', query: $this->buildIncludeQuery($includes))
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getNode(int|string $nodeId, array $includes = []): array
    {
        return $this->unwrapAttributes(
            $this->applicationSend('GET', '/nodes/'.$nodeId, query: $this->buildIncludeQuery($includes))
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listNests(bool $includeEggs = false): array
    {
        $query = $includeEggs ? ['include' => 'eggs'] : [];

        return $this->unwrapDataList($this->applicationSend('GET', '/nests', query: $query));
    }

    /**
     * @param  array<string, scalar>  $filters
     * @return list<array<string, mixed>>
     */
    public function listUsers(array $filters = []): array
    {
        $query = [];

        foreach ($filters as $key => $value) {
            $query['filter['.$key.']'] = (string) $value;
        }

        return $this->unwrapDataList($this->applicationSend('GET', '/users', query: $query));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findUserByExternalId(string $externalId): ?array
    {
        $users = $this->listUsers(['external_id' => $externalId]);

        return $users[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createUser(array $payload): array
    {
        return $this->unwrapAttributes($this->applicationSend('POST', '/users', payload: $payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function getUser(int|string $userId, array $includes = []): array
    {
        return $this->unwrapAttributes(
            $this->applicationSend('GET', '/users/'.$userId, query: $this->buildIncludeQuery($includes))
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createServer(array $payload): array
    {
        return $this->unwrapAttributes($this->applicationSend('POST', '/servers', payload: $payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function getServer(int|string $serverId, array $includes = []): array
    {
        return $this->unwrapAttributes(
            $this->applicationSend('GET', '/servers/'.$serverId, query: $this->buildIncludeQuery($includes))
        );
    }

    public function suspendServer(int|string $serverId): void
    {
        $this->applicationSend('POST', '/servers/'.$serverId.'/suspend');
    }

    public function unsuspendServer(int|string $serverId): void
    {
        $this->applicationSend('POST', '/servers/'.$serverId.'/unsuspend');
    }

    public function reinstallServer(int|string $serverId): void
    {
        $this->applicationSend('POST', '/servers/'.$serverId.'/reinstall');
    }

    public function deleteServer(int|string $serverId): void
    {
        $this->applicationSend('DELETE', '/servers/'.$serverId);
    }

    public function sendPowerSignal(string $serverIdentifier, string $signal, ?string $clientApiKey = null): void
    {
        $normalizedSignal = strtolower(trim($signal));

        if (! in_array($normalizedSignal, self::ALLOWED_POWER_SIGNALS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid power signal "%s". Allowed values: %s.',
                $signal,
                implode(', ', self::ALLOWED_POWER_SIGNALS),
            ));
        }

        $this->clientSend(
            method: 'POST',
            path: '/servers/'.$serverIdentifier.'/power',
            payload: ['signal' => $normalizedSignal],
            clientApiKey: $clientApiKey,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerResources(string $serverIdentifier, ?string $clientApiKey = null): array
    {
        return $this->clientSend(
            method: 'GET',
            path: '/servers/'.$serverIdentifier.'/resources',
            clientApiKey: $clientApiKey,
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applicationSend(string $method, string $path, array $query = [], array $payload = []): array
    {
        $endpoint = $this->applicationEndpoint($path);
        $response = $this->applicationRequest()->send($method, $endpoint, $this->buildRequestOptions($query, $payload));

        return $this->decodeResponse($response, $method, $endpoint);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function clientSend(
        string $method,
        string $path,
        array $query = [],
        array $payload = [],
        ?string $clientApiKey = null,
    ): array {
        $endpoint = $this->clientEndpoint($path);
        $response = $this->clientRequest($clientApiKey)->send($method, $endpoint, $this->buildRequestOptions($query, $payload));

        return $this->decodeResponse($response, $method, $endpoint);
    }

    private function applicationRequest(): PendingRequest
    {
        return $this->buildRequest($this->applicationApiKey());
    }

    private function clientRequest(?string $clientApiKey = null): PendingRequest
    {
        return $this->buildRequest($this->resolveClientApiKey($clientApiKey));
    }

    private function buildRequest(string $apiKey): PendingRequest
    {
        return Http::withToken($apiKey)
            ->withHeaders([
                'Accept' => self::ACCEPT_HEADER,
                'Content-Type' => 'application/json',
            ])
            ->baseUrl($this->baseUrl())
            ->timeout($this->timeoutSeconds());
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildRequestOptions(array $query = [], array $payload = []): array
    {
        $options = [];

        if ($query !== []) {
            $options['query'] = $query;
        }

        if ($payload !== []) {
            $options['json'] = $payload;
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response, string $method, string $endpoint): array
    {
        if ($response->failed()) {
            throw PterodactylApiException::fromResponse($method, $endpoint, $response);
        }

        if (trim($response->body()) === '') {
            return [];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw PterodactylApiException::invalidJson($method, $endpoint);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function unwrapAttributes(array $payload): array
    {
        $attributes = $payload['attributes'] ?? null;

        if (! is_array($attributes)) {
            throw new PterodactylApiException('Unexpected Pterodactyl payload shape: missing "attributes".');
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function unwrapDataList(array $payload): array
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            throw new PterodactylApiException('Unexpected Pterodactyl payload shape: missing "data" list.');
        }

        $items = [];

        foreach ($data as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $attributes = $entry['attributes'] ?? null;
            $items[] = is_array($attributes) ? $attributes : $entry;
        }

        return $items;
    }

    private function applicationEndpoint(string $path): string
    {
        return self::APPLICATION_API_PREFIX.'/'.$this->trimPath($path);
    }

    private function clientEndpoint(string $path): string
    {
        return self::CLIENT_API_PREFIX.'/'.$this->trimPath($path);
    }

    private function trimPath(string $path): string
    {
        return ltrim(trim($path), '/');
    }

    /**
     * @param  list<string>  $includes
     * @return array<string, string>
     */
    private function buildIncludeQuery(array $includes): array
    {
        $normalizedIncludes = collect($includes)
            ->filter(fn (mixed $include): bool => is_string($include) && trim($include) !== '')
            ->map(fn (string $include): string => trim($include))
            ->values()
            ->all();

        return $normalizedIncludes === [] ? [] : ['include' => implode(',', $normalizedIncludes)];
    }

    private function baseUrl(): string
    {
        $baseUrl = trim((string) config('services.pterodactyl.base_url', ''));

        if ($baseUrl === '') {
            throw new InvalidArgumentException('Pterodactyl base URL is not configured.');
        }

        return rtrim($baseUrl, '/');
    }

    private function timeoutSeconds(): int
    {
        $timeout = (int) config('services.pterodactyl.timeout', 15);

        return $timeout > 0 ? $timeout : 15;
    }

    private function applicationApiKey(): string
    {
        $apiKey = trim((string) config('services.pterodactyl.application_api_key', ''));

        if ($apiKey === '') {
            throw new InvalidArgumentException('Pterodactyl application API key is not configured.');
        }

        return $apiKey;
    }

    private function resolveClientApiKey(?string $clientApiKey): string
    {
        if (is_string($clientApiKey) && trim($clientApiKey) !== '') {
            return trim($clientApiKey);
        }

        $configuredClientApiKey = trim((string) config('services.pterodactyl.client_api_key', ''));

        if ($configuredClientApiKey === '') {
            throw new InvalidArgumentException('Pterodactyl client API key is not configured.');
        }

        return $configuredClientApiKey;
    }
}
