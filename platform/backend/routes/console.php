<?php

use App\Services\EventBus\ServerLifecycleCacheInvalidationConsumer;
use App\Services\Stripe\Helpers\StripeClientFactory;
use App\Services\Stripe\Services\StripeWebhookService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Interadigital\CoreEvents\EventBus\EventBusClient;
use Interadigital\CoreEvents\Events\ServerOrdered;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Enums\ServerStatus;
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
    'servers:reconcile-pending-payments {--limit=100 : Max servers to process}',
    function (StripeClientFactory $stripeClientFactory, StripeWebhookService $stripeWebhookService): int {
        $limit = max(1, (int) $this->option('limit'));
        $servers = Server::query()
            ->where('status', ServerStatus::NEW->value)
            ->whereNotNull('stripe_tx_id')
            ->where('stripe_tx_id', '!=', '')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($servers->isEmpty()) {
            $this->info('No pending-payment servers to reconcile.');

            return ConsoleCommand::SUCCESS;
        }

        $stripeClient = $stripeClientFactory->make();
        $processed = 0;
        $updated = 0;
        $errors = 0;

        foreach ($servers as $server) {
            $processed++;
            $stripeRef = trim((string) $server->stripe_tx_id);

            try {
                $subscriptionId = null;
                if (str_starts_with($stripeRef, 'sub_')) {
                    $subscriptionId = $stripeRef;
                } elseif (str_starts_with($stripeRef, 'cs_')) {
                    $checkoutSession = $stripeClient->checkout->sessions->retrieve($stripeRef, []);
                    $rawSubscription = $checkoutSession->subscription ?? null;

                    if (is_string($rawSubscription) && $rawSubscription !== '') {
                        $subscriptionId = $rawSubscription;
                    } elseif (is_object($rawSubscription) && isset($rawSubscription->id) && is_string($rawSubscription->id)) {
                        $subscriptionId = $rawSubscription->id;
                    }

                    if (is_string($subscriptionId) && $subscriptionId !== '' && $subscriptionId !== $stripeRef) {
                        $server->stripe_tx_id = $subscriptionId;
                        $server->save();
                    }
                }

                if (! is_string($subscriptionId) || $subscriptionId === '') {
                    continue;
                }

                $subscription = $stripeClient->subscriptions->retrieve($subscriptionId, []);
                $status = is_string($subscription->status ?? null) ? $subscription->status : '';
                $isPaidOrActive = in_array($status, ['active', 'trialing', 'past_due'], true);

                if (! $isPaidOrActive) {
                    continue;
                }

                $stripeWebhookService->handleEvent([
                    'id' => (string) Str::uuid(),
                    'type' => 'invoice.payment_succeeded',
                    'created' => time(),
                    'data' => [
                        'object' => [
                            'subscription' => $subscriptionId,
                        ],
                    ],
                ]);
                $updated++;
            } catch (\Throwable $exception) {
                $errors++;
                report($exception);
                $this->warn(sprintf(
                    'Failed to reconcile server %s: %s',
                    (string) $server->uuid,
                    $exception->getMessage()
                ));
            }
        }

        $this->info(sprintf(
            'Reconcile complete. processed=%d updated=%d errors=%d',
            $processed,
            $updated,
            $errors
        ));

        return ConsoleCommand::SUCCESS;
    }
)->purpose('Reconcile pending-payment servers by checking Stripe status');

Artisan::command(
    'events:consume-server-lifecycle {--once : Consume one receive batch and exit} {--max-messages=10 : Max SQS messages per poll} {--wait=20 : SQS long-poll wait seconds} {--sleep=2 : Idle sleep seconds between polls}',
    function (ServerLifecycleCacheInvalidationConsumer $consumer): int {
        $once = (bool) $this->option('once');
        $maxMessages = max(1, (int) $this->option('max-messages'));
        $waitSeconds = max(0, (int) $this->option('wait'));
        $sleepSeconds = max(0, (int) $this->option('sleep'));

        do {
            try {
                $processed = $consumer->consumeBatch($maxMessages, $waitSeconds);
            } catch (\Throwable $exception) {
                $this->error('Lifecycle consumer poll failed: '.$exception->getMessage());

                if ($once) {
                    return ConsoleCommand::FAILURE;
                }

                sleep(max(1, $sleepSeconds));

                continue;
            }

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
