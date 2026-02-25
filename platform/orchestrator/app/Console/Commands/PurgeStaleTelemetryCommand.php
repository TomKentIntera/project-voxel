<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;

class PurgeStaleTelemetryCommand extends Command
{
    protected $signature = 'telemetry:purge-stale';

    protected $description = 'Delete telemetry rows older than 24 hours';

    public function handle(): int
    {
        $cutoff = now()->subDay();

        $deletedNodes = TelemetryNode::query()
            ->where('updated_at', '<', $cutoff)
            ->delete();

        $deletedServers = TelemetryServer::query()
            ->where('updated_at', '<', $cutoff)
            ->delete();

        $this->info("Purged {$deletedNodes} stale node telemetry row(s) and {$deletedServers} stale server telemetry row(s).");

        return self::SUCCESS;
    }
}

