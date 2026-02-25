<?php

use App\Jobs\SyncNodeToPterodactylJob;
use App\Jobs\UpdateLocationFreeSpaceJob;
use App\Services\EventBus\ServerOrderedConsumer;
use App\Services\Metrics\ResourceConsumptionCacheService;
use App\Services\Pterodactyl\Services\PterodactylApiClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use RuntimeException;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Throwable;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telemetry:purge-stale', function (): int {
    $cutoff = now()->subDay();

    $deletedNodes = TelemetryNode::query()
        ->where('updated_at', '<', $cutoff)
        ->delete();

    $deletedServers = TelemetryServer::query()
        ->where('updated_at', '<', $cutoff)
        ->delete();

    $this->info("Purged {$deletedNodes} stale node telemetry row(s) and {$deletedServers} stale server telemetry row(s).");

    return ConsoleCommand::SUCCESS;
})->purpose('Delete telemetry rows older than 24 hours');

Artisan::command('metrics:cache-resource-consumption', function (ResourceConsumptionCacheService $service): int {
    $value = $service->refreshLastHourConsumptionPercent();

    $this->info(sprintf('Cached last-hour resource consumption at %.2f%%.', $value));

    return ConsoleCommand::SUCCESS;
})->purpose('Cache overall resource consumption for dashboard');

Artisan::command('pterodactyl:update-location-free-space', function (): int {
    UpdateLocationFreeSpaceJob::dispatchSync();

    $this->info('Updated Pterodactyl location and node free-space cache.');

    return ConsoleCommand::SUCCESS;
})->purpose('Cache free memory values for Pterodactyl locations and nodes');

Artisan::command(
    'events:consume-server-ordered {--once : Consume one receive batch and exit} {--max-messages=10 : Max SQS messages per poll} {--wait=20 : SQS long-poll wait seconds} {--sleep=2 : Idle sleep seconds between polls}',
    function (ServerOrderedConsumer $consumer): int {
        $once = (bool) $this->option('once');
        $maxMessages = max(1, (int) $this->option('max-messages'));
        $waitSeconds = max(0, (int) $this->option('wait'));
        $sleepSeconds = max(0, (int) $this->option('sleep'));

        do {
            $processed = $consumer->consumeBatch($maxMessages, $waitSeconds);

            if ($processed > 0) {
                $this->info(sprintf('Processed %d server ordered event(s).', $processed));
            } elseif (! $once && $sleepSeconds > 0) {
                sleep($sleepSeconds);
            }

            if ($once) {
                break;
            }
        } while (true);

        return ConsoleCommand::SUCCESS;
    }
)->purpose('Consume server ordered integration events from SQS');

Artisan::command('test:provision-local', function (PterodactylApiClient $pterodactylApiClient): int {
    $nodeId = 'node-1';
    $nodeName = 'node-1';
    $nodeRegion = 'eu.ger';
    $allocationPorts = ['26625-26695'];
    $locationShortCode = 'eu.ger';
    $locationLongName = 'Local development';
    $defaultPteroLocationId = 1;

    $normalizePositiveInteger = static function (mixed $value): ?int {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', trim($value)) === 1) {
            $normalized = (int) trim($value);

            return $normalized > 0 ? $normalized : null;
        }

        return null;
    };

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
            throw new RuntimeException(
                'Pterodactyl base URL and application API key must be configured to sync the local test node.'
            );
        }

        $existingLocation = collect($pterodactylApiClient->listLocations())
            ->first(fn (array $location): bool => trim((string) ($location['short'] ?? '')) === $locationShortCode);

        $resolvedLocationId = $normalizePositiveInteger(
            is_array($existingLocation) ? ($existingLocation['id'] ?? null) : null
        );

        if ($resolvedLocationId === null) {
            $createdLocation = $pterodactylApiClient->createLocation([
                'short' => $locationShortCode,
                'long' => $locationLongName,
            ]);

            $resolvedLocationId = $normalizePositiveInteger($createdLocation['id'] ?? null);
        }

        if ($resolvedLocationId === null) {
            throw new RuntimeException('Unable to resolve a valid Pterodactyl location ID for local provisioning.');
        }

        if ((int) $node->ptero_location_id !== $resolvedLocationId) {
            $node->forceFill([
                'ptero_location_id' => $resolvedLocationId,
            ])->save();
        }

        SyncNodeToPterodactylJob::dispatchSync($node->id);
        $node->refresh();
    } catch (Throwable $exception) {
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

    return $syncFailed ? ConsoleCommand::FAILURE : ConsoleCommand::SUCCESS;
})->purpose('Provision a local test node with default allocations');
