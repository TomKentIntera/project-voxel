<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ServerProvisionedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly int $serverId,
        public readonly string $serverUuid,
        public readonly string $serverName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your server is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.server-provisioned',
            with: [
                'serverId' => $this->serverId,
                'serverUuid' => $this->serverUuid,
                'serverName' => $this->serverName,
            ],
        );
    }
}
