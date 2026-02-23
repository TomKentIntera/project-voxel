<?php

declare(strict_types=1);

namespace App\Notifications\Slack;

final class TextSlackNotification extends AbstractSlackNotification
{
    public function __construct(
        private readonly string $channel,
        private readonly string $content,
    ) {
    }

    public function channel(): string
    {
        return $this->channel;
    }

    public function content(): string
    {
        return $this->content;
    }
}
