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

class AvailabilityNotificationCreated extends Notification implements ShouldQueue
{
    use Queueable;

    private $plan;
    private $region;
    private $planLink;
    private $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Model $user, $plan, $region, $planLink = 'plans')
    {
        $this->plan = $plan;
        $this->region = $region;
        $this->planLink = $planLink;
        $this->user = $user;
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
                ->headerBlock(':boom: Availability Notification Created!')
                ->sectionBlock(function (SectionBlock $block) {
                    $block->text('An availability notification has created for:');
                    $block->field("*Email:*\n".$this->user->email)->markdown();
                    $block->field("*Plan:*\n".$this->plan['title'])->markdown();
                    $block->field("*Region:*\n".$this->region['title'])->markdown();
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
