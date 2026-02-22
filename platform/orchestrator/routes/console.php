<?php

use App\Services\EventBus\ServerOrderedConsumer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

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
