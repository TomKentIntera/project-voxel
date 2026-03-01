<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncNodeToPterodactylJob;
use App\Services\Pterodactyl\Services\PterodactylApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\Node;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NodeProvisioningController extends Controller
{
    private const CACHE_KEY_PREFIX = 'node-provisioning-bootstrap:';

    private const DEFAULT_TTL_MINUTES = 20;

    private const MAX_TTL_MINUTES = 120;

    public function issueCommand(Request $request, PterodactylApiClient $pterodactylApiClient, string $id): JsonResponse
    {
        $validated = $request->validate([
            'ttl_minutes' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_TTL_MINUTES],
        ]);

        $node = Node::query()->find($id);

        if (! ($node instanceof Node)) {
            return response()->json([
                'message' => 'Node not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->assertPterodactylConfigured();
            $pteroNodeId = $this->ensureNodeHasPanelId($node);
            $wingsConfiguration = $pterodactylApiClient->getNodeConfiguration($pteroNodeId);
            $monitorInstaller = $this->resolveMonitorInstaller();
            $orchestratorBaseUrl = $this->resolveOrchestratorApiBaseUrl();
            $wingsBinaryUrlAmd64 = $this->resolveWingsBinaryUrlForArch('amd64');
            $wingsBinaryUrlArm64 = $this->resolveWingsBinaryUrlForArch('arm64');
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $this->provisioningFailureMessage($exception),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawNodeToken = Node::generateToken();

        $node->forceFill([
            'token_hash' => Node::hashToken($rawNodeToken),
        ])->save();

        $ttlMinutes = $this->resolveTtlMinutes($validated);
        $expiresAt = now()->addMinutes($ttlMinutes);
        $bootstrapToken = Str::random(64);

        Cache::put($this->cacheKey($bootstrapToken), [
            'node_id' => $node->id,
            'node_ip' => $node->ip_address,
            'node_token' => $rawNodeToken,
            'orchestrator_base_url' => $orchestratorBaseUrl,
            'wings_configuration' => $wingsConfiguration,
            'monitor_installer_type' => $monitorInstaller['type'],
            'monitor_installer_value' => $monitorInstaller['value'],
            'wings_binary_url_amd64' => $wingsBinaryUrlAmd64,
            'wings_binary_url_arm64' => $wingsBinaryUrlArm64,
        ], $expiresAt);

        $bootstrapUrl = $this->buildBootstrapUrl($bootstrapToken);

        return response()->json([
            'data' => [
                'node_id' => $node->id,
                'expires_at' => $expiresAt->toIso8601String(),
                'bootstrap_url' => $bootstrapUrl,
                'command' => sprintf('curl -fsSL %s | sudo bash', escapeshellarg($bootstrapUrl)),
            ],
        ], Response::HTTP_CREATED);
    }

    public function bootstrapScript(string $token): Response
    {
        $normalizedToken = trim($token);

        if ($normalizedToken === '' || preg_match('/^[A-Za-z0-9]+$/', $normalizedToken) !== 1) {
            return response('Bootstrap token not found.', Response::HTTP_NOT_FOUND, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $payload = Cache::pull($this->cacheKey($normalizedToken));

        if (! is_array($payload)) {
            return response('Bootstrap token not found or expired.', Response::HTTP_NOT_FOUND, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        return response($this->buildBootstrapScript($payload), Response::HTTP_OK, [
            'Content-Type' => 'text/x-shellscript; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveTtlMinutes(array $validated): int
    {
        $configuredDefault = (int) config('services.provisioning.bootstrap_ttl_minutes', self::DEFAULT_TTL_MINUTES);
        $configuredMax = (int) config('services.provisioning.bootstrap_max_ttl_minutes', self::MAX_TTL_MINUTES);

        $maxTtl = $configuredMax > 0 ? min($configuredMax, self::MAX_TTL_MINUTES) : self::MAX_TTL_MINUTES;
        $ttl = (int) ($validated['ttl_minutes'] ?? $configuredDefault);

        if ($ttl < 1) {
            $ttl = self::DEFAULT_TTL_MINUTES;
        }

        return min($ttl, $maxTtl);
    }

    private function assertPterodactylConfigured(): void
    {
        $baseUrl = trim((string) config('services.pterodactyl.base_url', ''));
        $applicationApiKey = trim((string) config('services.pterodactyl.application_api_key', ''));

        if ($baseUrl === '' || $applicationApiKey === '') {
            throw new RuntimeException(
                'Pterodactyl base URL and application API key must be configured before generating provisioning commands.'
            );
        }
    }

    private function ensureNodeHasPanelId(Node $node): int
    {
        $pteroNodeId = $this->normalizePositiveInteger($node->ptero_node_id);

        if ($pteroNodeId !== null) {
            return $pteroNodeId;
        }

        SyncNodeToPterodactylJob::dispatchSync($node->id);
        $node->refresh();

        $resolvedNodeId = $this->normalizePositiveInteger($node->ptero_node_id);

        if ($resolvedNodeId === null) {
            throw new RuntimeException('Node did not receive a valid Pterodactyl node ID during synchronization.');
        }

        return $resolvedNodeId;
    }

    /**
     * @return array{type: string, value: string}
     */
    private function resolveMonitorInstaller(): array
    {
        $monitorScriptUrl = trim((string) config('services.provisioning.monitor_script_url', ''));

        if ($monitorScriptUrl !== '') {
            return [
                'type' => 'url',
                'value' => $monitorScriptUrl,
            ];
        }

        foreach ($this->monitorScriptPathCandidates() as $candidatePath) {
            if (! is_file($candidatePath) || ! is_readable($candidatePath)) {
                continue;
            }

            $contents = file_get_contents($candidatePath);

            if (! is_string($contents) || trim($contents) === '') {
                continue;
            }

            return [
                'type' => 'inline',
                'value' => $contents,
            ];
        }

        throw new RuntimeException(
            'No monitor installer source is configured. Set PROVISIONING_MONITOR_SCRIPT_URL '
            .'or PROVISIONING_MONITOR_SCRIPT_PATH.'
        );
    }

    /**
     * @return list<string>
     */
    private function monitorScriptPathCandidates(): array
    {
        $candidates = [];
        $configuredPath = trim((string) config('services.provisioning.monitor_script_path', ''));

        if ($configuredPath !== '') {
            $candidates[] = $configuredPath;
        }

        $candidates[] = base_path('resources/provisioning/node-agent.py');
        $candidates[] = base_path('../../node_agent/main.py');

        /** @var list<string> $normalized */
        $normalized = collect($candidates)
            ->filter(fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->map(fn (string $path): string => trim($path))
            ->unique()
            ->values()
            ->all();

        return $normalized;
    }

    private function resolveOrchestratorApiBaseUrl(): string
    {
        $configured = trim((string) config('services.provisioning.orchestrator_base_url', ''));

        if ($configured !== '') {
            $normalized = rtrim($configured, '/');

            return str_ends_with(strtolower($normalized), '/api')
                ? $normalized
                : $normalized.'/api';
        }

        $appUrl = trim((string) config('app.url', ''));

        if ($appUrl === '') {
            throw new RuntimeException(
                'Unable to resolve orchestrator base URL for provisioning command generation (APP_URL is empty).'
            );
        }

        return rtrim($appUrl, '/').'/api';
    }

    private function buildBootstrapUrl(string $bootstrapToken): string
    {
        $appUrl = trim((string) config('app.url', ''));

        if ($appUrl === '') {
            throw new RuntimeException('Unable to resolve bootstrap URL (APP_URL is empty).');
        }

        return rtrim($appUrl, '/').'/api/provisioning/bootstrap/'.$bootstrapToken;
    }

    private function cacheKey(string $bootstrapToken): string
    {
        return self::CACHE_KEY_PREFIX.$bootstrapToken;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildBootstrapScript(array $payload): string
    {
        $nodeId = trim((string) ($payload['node_id'] ?? ''));
        $nodeIp = trim((string) ($payload['node_ip'] ?? ''));
        $nodeToken = trim((string) ($payload['node_token'] ?? ''));
        $orchestratorBaseUrl = trim((string) ($payload['orchestrator_base_url'] ?? ''));
        $wingsConfiguration = $payload['wings_configuration'] ?? null;
        $monitorInstallerType = trim((string) ($payload['monitor_installer_type'] ?? ''));
        $monitorInstallerValue = (string) ($payload['monitor_installer_value'] ?? '');
        $wingsBinaryUrlAmd64 = trim((string) ($payload['wings_binary_url_amd64'] ?? ''));
        $wingsBinaryUrlArm64 = trim((string) ($payload['wings_binary_url_arm64'] ?? ''));

        if (
            $nodeId === ''
            || $nodeIp === ''
            || $nodeToken === ''
            || $orchestratorBaseUrl === ''
            || ! is_array($wingsConfiguration)
            || $wingsBinaryUrlAmd64 === ''
            || $wingsBinaryUrlArm64 === ''
        ) {
            throw new RuntimeException('Provisioning bootstrap payload is incomplete.');
        }

        $wingsConfigJson = json_encode($wingsConfiguration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($wingsConfigJson) || trim($wingsConfigJson) === '') {
            throw new RuntimeException('Unable to encode Wings configuration for bootstrap.');
        }

        $monitorInstallBlock = $this->buildMonitorInstallBlock(
            $monitorInstallerType,
            $monitorInstallerValue
        );

        $renderedScript = view('provisioning.bootstrap-script', [
            'nodeIdQuoted' => $this->shellSingleQuote($nodeId),
            'nodeTokenQuoted' => $this->shellSingleQuote($nodeToken),
            'nodeIpQuoted' => $this->shellSingleQuote($nodeIp),
            'orchestratorBaseUrlQuoted' => $this->shellSingleQuote($orchestratorBaseUrl),
            'wingsConfigB64Quoted' => $this->shellSingleQuote(base64_encode($wingsConfigJson)),
            'wingsUrlAmd64Quoted' => $this->shellSingleQuote($wingsBinaryUrlAmd64),
            'wingsUrlArm64Quoted' => $this->shellSingleQuote($wingsBinaryUrlArm64),
            'monitorInstallBlock' => $monitorInstallBlock,
        ])->render();

        return ltrim($renderedScript, "\n");
    }

    private function buildMonitorInstallBlock(string $installerType, string $installerValue): string
    {
        if ($installerType === 'url') {
            $url = trim($installerValue);

            if ($url === '') {
                throw new RuntimeException('Monitor installer URL is empty.');
            }

            return sprintf(
                "log \"Downloading orchestrator monitor script...\"\n"
                ."curl -fsSL '%s' -o /opt/intera/orchestrator-monitor/main.py",
                $this->shellSingleQuote($url)
            );
        }

        if ($installerType === 'inline') {
            $encodedScript = base64_encode($installerValue);

            if ($encodedScript === '') {
                throw new RuntimeException('Embedded monitor installer script is empty.');
            }

            return sprintf(
                "log \"Installing embedded orchestrator monitor script...\"\n"
                ."printf '%%s' '%s' | base64 --decode > /opt/intera/orchestrator-monitor/main.py",
                $this->shellSingleQuote($encodedScript)
            );
        }

        throw new RuntimeException('Unsupported monitor installer source type.');
    }

    private function resolveWingsBinaryUrlForArch(string $arch): string
    {
        $template = trim((string) config(
            'services.provisioning.wings_binary_url_template',
            'https://github.com/pterodactyl/wings/releases/latest/download/wings_linux_%s'
        ));

        if ($template === '') {
            throw new RuntimeException('Wings binary URL template is empty.');
        }

        return str_contains($template, '%s')
            ? sprintf($template, $arch)
            : $template;
    }

    private function shellSingleQuote(string $value): string
    {
        return str_replace("'", "'\"'\"'", $value);
    }

    private function normalizePositiveInteger(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', trim($value)) === 1) {
            $normalized = (int) trim($value);

            return $normalized > 0 ? $normalized : null;
        }

        return null;
    }

    private function provisioningFailureMessage(Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        if ($message === '') {
            return 'Unable to generate provisioning command for this node.';
        }

        return mb_substr($message, 0, 1000);
    }
}

