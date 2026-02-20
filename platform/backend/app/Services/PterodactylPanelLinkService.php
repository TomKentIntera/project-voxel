<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Interadigital\CoreModels\Models\Server;
use Throwable;

class PterodactylPanelLinkService
{
    public function resolvePanelUrl(Server $server): ?string
    {
        $panelBaseUrl = $this->panelBaseUrl();
        if ($panelBaseUrl === null) {
            return null;
        }

        $apiBaseUrl = $this->apiBaseUrl();
        $apiKey = $this->apiKey();
        if ($apiBaseUrl === null || $apiKey === null) {
            return $panelBaseUrl;
        }

        try {
            $panelServer = $this->fetchServerByPteroId($server, $apiBaseUrl, $apiKey);

            if ($panelServer === null) {
                $panelServer = $this->fetchServerByExternalId((string) $server->uuid, $apiBaseUrl, $apiKey);
                $this->cachePteroId($server, $panelServer);
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to resolve panel server identifier.', [
                'server_uuid' => (string) $server->uuid,
                'error' => $exception->getMessage(),
            ]);

            return $panelBaseUrl;
        }

        $identifier = $panelServer['attributes']['identifier'] ?? null;
        if (! is_string($identifier) || $identifier === '') {
            return $panelBaseUrl;
        }

        return $panelBaseUrl.'/server/'.$identifier;
    }

    private function panelBaseUrl(): ?string
    {
        $panelUrl = config('services.pterodactyl.panel_url');

        if (! is_string($panelUrl) || $panelUrl === '') {
            return null;
        }

        return rtrim($panelUrl, '/');
    }

    private function apiBaseUrl(): ?string
    {
        $apiUrl = config('services.pterodactyl.api_url');

        if (! is_string($apiUrl) || $apiUrl === '') {
            return null;
        }

        return rtrim($apiUrl, '/');
    }

    private function apiKey(): ?string
    {
        $apiKey = config('services.pterodactyl.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        return $apiKey;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchServerByPteroId(Server $server, string $apiBaseUrl, string $apiKey): ?array
    {
        $pteroId = $server->ptero_id;
        if (! is_string($pteroId) && ! is_int($pteroId)) {
            return null;
        }

        $pteroId = (string) $pteroId;
        if ($pteroId === '') {
            return null;
        }

        $response = Http::withHeaders($this->apiHeaders($apiKey))
            ->get($apiBaseUrl.'/application/servers/'.$pteroId);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchServerByExternalId(string $externalId, string $apiBaseUrl, string $apiKey): ?array
    {
        $response = Http::withHeaders($this->apiHeaders($apiKey))
            ->get($apiBaseUrl.'/application/servers', [
                'filter' => [
                    'external_id' => $externalId,
                ],
            ]);

        if (! $response->successful()) {
            return null;
        }

        $servers = $response->json('data');
        if (! is_array($servers)) {
            return null;
        }

        foreach ($servers as $server) {
            if (
                is_array($server)
                && ($server['attributes']['external_id'] ?? null) === $externalId
            ) {
                return $server;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $panelServer
     */
    private function cachePteroId(Server $server, ?array $panelServer): void
    {
        $panelId = $panelServer['attributes']['id'] ?? null;
        if (! is_int($panelId) && ! is_string($panelId)) {
            return;
        }

        $server->ptero_id = (string) $panelId;
        $server->save();
    }

    /**
     * @return array<string, string>
     */
    private function apiHeaders(string $apiKey): array
    {
        return [
            'Authorization' => 'Bearer '.$apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
