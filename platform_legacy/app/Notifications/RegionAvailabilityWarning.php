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

class RegionAvailabilityWarning extends Notification implements ShouldQueue
{
    use Queueable;

    private $freeMem;
    private $region;
    private $regionCode;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($region, $freeMem, $regionCode)
    {
        $this->freeMem = $freeMem;
        $this->region = $region;
        $this->regionCode = $regionCode;
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
        return (new SlackMessage)
                ->headerBlock(':warning: Location with low resources! :flag-'.$this->regionCode.':')
                ->sectionBlock(function (SectionBlock $block) {
                    $block->text('A location has insufficient resources to continue selling services:');
                    $block->field("*Region:*\n".$this->region)->markdown();
                    $block->field("*Free RAM (MB):*\n".$this->freeMem)->markdown();
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
