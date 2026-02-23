<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Notifications\Slack\AbstractSlackNotification;
use App\Services\Slack\SlackNotificationSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSlackNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly AbstractSlackNotification $notification,
    ) {
    }

    public function handle(SlackNotificationSender $sender): void
    {
        $sender->send($this->notification);
    }
}
