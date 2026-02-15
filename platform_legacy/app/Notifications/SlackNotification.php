<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\AvailabilityNotification as Model;

use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\BlockKit\Composites\ConfirmObject;
use Illuminate\Notifications\Slack\SlackMessage;

use Carbon\Carbon;
use App\Services\Slack\Service as SlackService;
use App\Services\Slack\Colour as SlackColour;

class SlackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $message;
    private $context;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($message, $context)
    {
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }


    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $slackService = \App::make(SlackService::class);

        
        return (new SlackMessage)
                ->headerBlock(':warning: An error occured on the storefront!')
                ->sectionBlock(function (SectionBlock $block) {
                    $block->text('Context: ```'.$this->context.'```')->markdown();
                })->sectionBlock(function (SectionBlock $block) {
                    $block->text('Message: ```'.$this->message.'```')->markdown();
                });
    }


    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'slack' => 'default',
        ];
    }

    
}
