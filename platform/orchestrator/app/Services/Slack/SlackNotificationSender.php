<?php

declare(strict_types=1);

namespace App\Services\Slack;

use App\Notifications\Slack\AbstractSlackNotification;
use Interadigital\CoreNotifications\Transport\SlackTransport;
use Interadigital\CoreNotifications\Transport\SlackTransportMessage;

class SlackNotificationSender
{
    public function __construct(
        private readonly SlackTransport $slackTransport,
    ) {
    }

    public function send(AbstractSlackNotification $notification): void
    {
        $this->slackTransport->send(new SlackTransportMessage(
            channel: $notification->channel(),
            content: $notification->content(),
        ));
    }
}
