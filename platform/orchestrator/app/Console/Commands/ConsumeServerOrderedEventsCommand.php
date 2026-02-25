<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EventBus\ServerOrderedConsumer;
use Illuminate\Console\Command;

class ConsumeServerOrderedEventsCommand extends Command
{
    protected $signature = 'events:consume-server-ordered
                            {--once : Consume one receive batch and exit}
                            {--max-messages=10 : Max SQS messages per poll}
                            {--wait=20 : SQS long-poll wait seconds}
                            {--sleep=2 : Idle sleep seconds between polls}';

    protected $description = 'Consume server lifecycle integration events from SQS';

    public function handle(ServerOrderedConsumer $consumer): int
    {
        $once = (bool) $this->option('once');
        $maxMessages = max(1, (int) $this->option('max-messages'));
        $waitSeconds = max(0, (int) $this->option('wait'));
        $sleepSeconds = max(0, (int) $this->option('sleep'));

        do {
            $processed = $consumer->consumeBatch($maxMessages, $waitSeconds);

            if ($processed > 0) {
                $this->info(sprintf('Processed %d server lifecycle event(s).', $processed));
            } elseif (! $once && $sleepSeconds > 0) {
                sleep($sleepSeconds);
            }

            if ($once) {
                break;
            }
        } while (true);

        return self::SUCCESS;
    }
}

