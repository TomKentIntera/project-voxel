<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\EventBus\ServerLifecycleEventProcessorResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EventConsumerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $eventPayload
     */
    public function __construct(
        public readonly string $processorKey,
        public readonly array $eventPayload,
    ) {
    }

    public function handle(ServerLifecycleEventProcessorResolver $resolver): void
    {
        $resolver->resolve($this->processorKey)->consume($this->eventPayload);
    }
}
