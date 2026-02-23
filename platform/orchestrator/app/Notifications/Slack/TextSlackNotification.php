<?php

declare(strict_types=1);

namespace App\Notifications\Slack;

final class TextSlackNotification extends AbstractSlackNotification
{
    public function __construct(
        private readonly string $destinationChannelId,
        private readonly string $content,
    ) {
    }

    public function destinationChannelId(): string
    {
        return $this->destinationChannelId;
    }

    public function content(): string
    {
        return $this->content;
    }
}
