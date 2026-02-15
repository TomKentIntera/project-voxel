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

class AvailabilityNotification extends Notification implements ShouldQueue
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
        return ['mail', 'slack'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New availability in '.$this->region['title'])
                    ->view('emails.AvailabilityNotification.html',
                [
                    'plan' => $this->plan,
                    'region' => $this->region,
                    'planLink' => $this->planLink
                ]);
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'sqs_mail',
            'slack' => 'default',
        ];
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
                ->headerBlock(':rocket: Availability Notification Delivered!')
                ->sectionBlock(function (SectionBlock $block) {
                    $block->text('An availability notification has been delivered for:');
                    $block->field("*Email:*\n".$this->user->email)->markdown();
                    $block->field("*Plan:*\n".$this->plan['name'])->markdown();
                    $block->field("*Region:*\n".$this->region['title'])->markdown();
                });
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    
}
