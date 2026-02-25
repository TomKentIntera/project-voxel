<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Pterodactyl\Services\PterodactylApiClient;
use App\Services\Pterodactyl\Support\AllocationPortNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Interadigital\CoreModels\Models\Node;
use RuntimeException;
use Throwable;

class SyncNodeToPterodactylJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120, 300, 600];

    public function __construct(
        private readonly string $nodeId
    ) {}

    public function handle(PterodactylApiClient $pterodactylApiClient): void
    {
        $node = Node::query()->find($this->nodeId);

        if (! ($node instanceof Node)) {
            return;
        }

        $node->forceFill([
            'sync_status' => Node::SYNC_STATUS_SYNCING,
            'sync_error' => null,
        ])->save();

        try {
            $pteroNodeId = $this->ensurePanelNodeExists($node, $pterodactylApiClient);
            $this->ensureAllocationsExist($node, $pteroNodeId, $pterodactylApiClient);

            $node->forceFill([
                'ptero_node_id' => $pteroNodeId,
                'sync_status' => Node::SYNC_STATUS_SYNCED,
                'sync_error' => null,
                'synced_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $node->forceFill([
                'sync_status' => Node::SYNC_STATUS_FAILED,
                'sync_error' => $this->truncateError($exception->getMessage()),
            ])->save();

            throw $exception;
        }
    }

    private function ensurePanelNodeExists(Node $node, PterodactylApiClient $pterodactylApiClient): int
    {
        $existingNodeId = $this->normalizePositiveInteger($node->ptero_node_id);

        if ($existingNodeId !== null) {
            return $existingNodeId;
        }

        $createdNode = $pterodactylApiClient->createNode([
            'name' => (string) $node->name,
            'location_id' => $this->requirePositiveInteger($node->ptero_location_id, 'ptero_location_id'),
            'fqdn' => $this->requireNonEmptyString($node->fqdn, 'fqdn'),
            'scheme' => $this->requireScheme($node->scheme),
            'behind_proxy' => (bool) $node->behind_proxy,
            'maintenance_mode' => (bool) $node->maintenance_mode,
            'memory' => $this->requirePositiveInteger($node->memory, 'memory'),
            'memory_overallocate' => $this->normalizeInteger($node->memory_overallocate),
            'disk' => $this->requirePositiveInteger($node->disk, 'disk'),
            'disk_overallocate' => $this->normalizeInteger($node->disk_overallocate),
            'upload_size' => $this->requirePositiveInteger($node->upload_size, 'upload_size'),
            'daemon_sftp' => $this->requirePort($node->daemon_sftp, 'daemon_sftp'),
            'daemon_listen' => $this->requirePort($node->daemon_listen, 'daemon_listen'),
        ]);

        $createdNodeId = $this->extractPanelNodeId($createdNode);

        $node->forceFill([
            'ptero_node_id' => $createdNodeId,
        ])->save();

        return $createdNodeId;
    }

    private function ensureAllocationsExist(
        Node $node,
        int $pteroNodeId,
        PterodactylApiClient $pterodactylApiClient
    ): void {
        $allocationIp = $this->resolveAllocationIp($node);
        $desiredPorts = AllocationPortNormalizer::expand($node->allocation_ports ?? []);

        if ($desiredPorts === []) {
            return;
        }

        $existingAllocationPorts = $this->resolveExistingAllocationPorts(
            allocations: $pterodactylApiClient->listNodeAllocations($pteroNodeId),
            allocationIp: $allocationIp,
        );

        $missingPorts = array_values(array_diff($desiredPorts, $existingAllocationPorts));
        sort($missingPorts);

        if ($missingPorts === []) {
            return;
        }

        $payload = [
            'ip' => $allocationIp,
            'ports' => array_map(static fn (int $port): string => (string) $port, $missingPorts),
        ];

        if (is_string($node->allocation_alias) && trim($node->allocation_alias) !== '') {
            $payload['alias'] = trim($node->allocation_alias);
        }

        $pterodactylApiClient->createNodeAllocations($pteroNodeId, $payload);
    }

    /**
     * @param  list<array<string, mixed>>  $allocations
     * @return list<int>
     */
    private function resolveExistingAllocationPorts(array $allocations, string $allocationIp): array
    {
        $existingPorts = [];

        foreach ($allocations as $allocation) {
            $entryIp = trim((string) ($allocation['ip'] ?? ''));

            if ($entryIp !== '' && $entryIp !== $allocationIp) {
                continue;
            }

            $port = $this->normalizePositiveInteger($allocation['port'] ?? null);

            if ($port !== null) {
                $existingPorts[] = $port;
            }
        }

        $existingPorts = array_values(array_unique($existingPorts));
        sort($existingPorts);

        return $existingPorts;
    }

    private function resolveAllocationIp(Node $node): string
    {
        $allocationIp = trim((string) ($node->allocation_ip ?? ''));

        if ($allocationIp === '') {
            $allocationIp = trim((string) ($node->ip_address ?? ''));
        }

        if ($allocationIp === '') {
            throw new RuntimeException('Node allocation IP address is required for allocation sync.');
        }

        return $allocationIp;
    }

    /**
     * @param  array<string, mixed>  $panelNode
     */
    private function extractPanelNodeId(array $panelNode): int
    {
        $createdNodeId = $this->normalizePositiveInteger($panelNode['id'] ?? null);

        if ($createdNodeId === null) {
            throw new RuntimeException('Pterodactyl node create response did not include a valid node ID.');
        }

        return $createdNodeId;
    }

    private function requirePositiveInteger(mixed $value, string $field): int
    {
        $normalized = $this->normalizePositiveInteger($value);

        if ($normalized === null) {
            throw new RuntimeException(sprintf('Node field "%s" is required and must be a positive integer.', $field));
        }

        return $normalized;
    }

    private function requirePort(mixed $value, string $field): int
    {
        $port = $this->requirePositiveInteger($value, $field);

        if ($port < 1 || $port > 65535) {
            throw new RuntimeException(sprintf('Node field "%s" must be between 1 and 65535.', $field));
        }

        return $port;
    }

    private function requireNonEmptyString(mixed $value, string $field): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf('Node field "%s" is required and must be a non-empty string.', $field));
        }

        return trim($value);
    }

    private function requireScheme(mixed $value): string
    {
        $scheme = strtolower($this->requireNonEmptyString($value, 'scheme'));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Node field "scheme" must be either "http" or "https".');
        }

        return $scheme;
    }

    private function normalizeInteger(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        return 0;
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

    private function truncateError(string $message): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return 'Unknown node synchronization error.';
        }

        return mb_substr($trimmed, 0, 65535);
    }
}
