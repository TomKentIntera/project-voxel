<?php

use App\Services\EventBus\ServerLifecycleCacheInvalidationConsumer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Interadigital\CoreEvents\EventBus\EventBusClient;
use Interadigital\CoreEvents\Events\ServerOrdered;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'events:publish-server-ordered {serverUuid : Server UUID to publish}',
    function (EventBusClient $eventBusClient, string $serverUuid): int {
        $server = Server::query()->where('uuid', $serverUuid)->first();

        if ($server === null) {
            $this->error('Server not found.');

            return ConsoleCommand::FAILURE;
        }

        $config = is_string($server->config) ? json_decode($server->config, true) : [];
        $event = new ServerOrdered(
            eventId: (string) Str::uuid(),
            occurredAt: now()->toIso8601String(),
            serverId: (int) $server->id,
            serverUuid: (string) $server->uuid,
            userId: (int) $server->user_id,
            plan: (string) $server->plan,
            config: is_array($config) ? $config : [],
            stripeSubscriptionId: is_string($server->stripe_tx_id) ? $server->stripe_tx_id : null,
            correlationId: is_string($server->stripe_tx_id) ? $server->stripe_tx_id : null,
        );

        $eventBusClient->publish($event);

        ServerEvent::query()->create([
            'server_id' => $server->id,
            'actor_id' => null,
            'type' => ServerEventType::SERVER_ORDERED->value,
            'meta' => [
                'event_id' => $event->eventId,
                'source' => 'artisan.events:publish-server-ordered',
            ],
        ]);

        $this->info('Published server ordered event: '.$event->eventId);

        return ConsoleCommand::SUCCESS;
    }
)->purpose('Publish a server ordered event to SNS');

Artisan::command(
    'events:consume-server-lifecycle {--once : Consume one receive batch and exit} {--max-messages=10 : Max SQS messages per poll} {--wait=20 : SQS long-poll wait seconds} {--sleep=2 : Idle sleep seconds between polls}',
    function (ServerLifecycleCacheInvalidationConsumer $consumer): int {
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

        return ConsoleCommand::SUCCESS;
    }
)->purpose('Consume lifecycle events and invalidate locations cache');
