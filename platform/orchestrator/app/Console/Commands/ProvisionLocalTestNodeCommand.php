<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncNodeToPterodactylJob;
use App\Services\Pterodactyl\Services\PterodactylApiClient;
use Illuminate\Console\Command;
use Interadigital\CoreModels\Models\Node;

class ProvisionLocalTestNodeCommand extends Command
{
    protected $signature = 'test:provision-local';

    protected $description = 'Provision a local test node with default allocations';

    public function handle(PterodactylApiClient $pterodactylApiClient): int
    {
        $nodeId = 'node-1';
        $nodeName = 'node-1';
        $nodeRegion = 'eu.ger';
        $allocationPorts = ['26625-26695'];
        $locationShortCode = 'eu.ger';
        $locationLongName = 'Local development';
        $defaultPteroLocationId = 1;

        $baseUrl = trim((string) config('services.pterodactyl.base_url', ''));
        $applicationApiKey = trim((string) config('services.pterodactyl.application_api_key', ''));
        $syncFailed = false;

        $rawToken = Node::generateToken();

        $node = Node::query()->updateOrCreate([
            'id' => $nodeId,
        ], [
            'name' => $nodeName,
            'region' => $nodeRegion,
            'ip_address' => '127.0.0.1',
            'ptero_location_id' => $defaultPteroLocationId,
            'fqdn' => 'pterodactyl-wings',
            'scheme' => 'http',
            'behind_proxy' => false,
            'maintenance_mode' => false,
            'memory' => 4096,
            'memory_overallocate' => 10,
            'disk' => 1000,
            'disk_overallocate' => 100,
            'upload_size' => 100,
            'daemon_sftp' => 2022,
            'daemon_listen' => 8080,
            'allocation_ip' => '127.0.0.1',
            'allocation_alias' => null,
            'allocation_ports' => $allocationPorts,
            'sync_status' => Node::SYNC_STATUS_PENDING,
            'sync_error' => null,
            'synced_at' => null,
            'token_hash' => Node::hashToken($rawToken),
            'last_active_at' => null,
            'last_used_at' => null,
        ]);

        $this->info(sprintf('Seeded local test node [%s] in orchestrator database.', $node->id));

        try {
            if ($baseUrl === '' || $applicationApiKey === '') {
                throw new \RuntimeException(
                    'Pterodactyl base URL and application API key must be configured to sync the local test node.'
                );
            }

            $existingLocation = collect($pterodactylApiClient->listLocations())
                ->first(fn (array $location): bool => trim((string) ($location['short'] ?? '')) === $locationShortCode);

            $resolvedLocationId = $this->normalizePositiveInteger(
                is_array($existingLocation) ? ($existingLocation['id'] ?? null) : null
            );

            if ($resolvedLocationId === null) {
                $createdLocation = $pterodactylApiClient->createLocation([
                    'short' => $locationShortCode,
                    'long' => $locationLongName,
                ]);

                $resolvedLocationId = $this->normalizePositiveInteger($createdLocation['id'] ?? null);
            }

            if ($resolvedLocationId === null) {
                throw new \RuntimeException('Unable to resolve a valid Pterodactyl location ID for local provisioning.');
            }

            if ((int) $node->ptero_location_id !== $resolvedLocationId) {
                $node->forceFill([
                    'ptero_location_id' => $resolvedLocationId,
                ])->save();
            }

            SyncNodeToPterodactylJob::dispatchSync($node->id);
            $node->refresh();
        } catch (\Throwable $exception) {
            $syncFailed = true;
            $this->error(sprintf(
                'Failed to synchronize local test node to Pterodactyl (%s).',
                trim($exception->getMessage()) !== '' ? trim($exception->getMessage()) : 'unknown error'
            ));
        }

        $this->info(sprintf('Provisioned local test node [%s] in orchestrator.', $node->id));
        $this->line(sprintf('NODE_ID=%s', $node->id));
        $this->line(sprintf('NODE_TOKEN=%s', $rawToken));
        $this->line(sprintf('ALLOCATION_PORTS=%s', implode(',', $allocationPorts)));

        if ($node->ptero_node_id !== null) {
            $this->line(sprintf('PTERODACTYL_NODE_ID=%d', (int) $node->ptero_node_id));
        }

        return $syncFailed ? self::FAILURE : self::SUCCESS;
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
}

