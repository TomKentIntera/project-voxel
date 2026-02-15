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

class WeeklyNodeReport extends Notification implements ShouldQueue
{
    use Queueable;

    private $locationData;
    private $nodeData;
    private $regionData;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($locationData, $nodeData, $regionData)
    {
        $this->locationData = $locationData;
        $this->nodeData = $nodeData;
        $this->regionData = $regionData;
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
                ->headerBlock(':chart_with_upwards_trend: Weekly Location Report: '.$this->locationData['long']. ' :flag-'.$this->regionData['code'].': ('.$this->locationData['short'].')')
                ->sectionBlock(function (SectionBlock $block) {
                    $block->text('Weekly availability report generated: '.Carbon::now()->toDateTimeString());
                    $block->field("*Total RAM:*\n".$this->locationData['totalMemoryGB'].' GB')->markdown();
                    $block->field("*Total Used RAM:*\n".$this->locationData['totalUsedMemoryGB'].' GB')->markdown();
                    $block->field("*Total Free RAM:*\n".$this->locationData['totalFreeMemoryGB'].' GB')->markdown();
                    $block->field("*Max Free RAM:*\n".$this->locationData['maxFreeMemoryGB'].' GB')->markdown();
                    $block->field("*Node Count:*\n".$this->locationData['nodeCount'])->markdown();
                })
                ->sectionBlock(function (SectionBlock $block) use ($slackService) {
                    $totalMemoryUsedProgressBar = $slackService->generateProgressBar($this->locationData['totalMemoryUsedPercent'], 100, 10, $this->locationData['totalMemoryUsedPercent'] > 80 ? SlackColour::Red : SlackColour::Blue);
                    $minimumMemoryUsedProgressBar = $slackService->generateProgressBar($this->locationData['memoryUsedFreestNodePercent'], 100, 10, $this->locationData['memoryUsedFreestNodePercent'] > 80 ? SlackColour::Red : SlackColour::Blue);
                    $progressBars = [];

                    $progressBars[] = "*Avg. RAM Allocated:* ".$totalMemoryUsedProgressBar.' '.$this->locationData['totalMemoryUsedPercent'].'%';
                    $progressBars[] = "*Min. RAM Allocated:* ".$minimumMemoryUsedProgressBar.' '.$this->locationData['memoryUsedFreestNodePercent'].'%';

                    $block->text(implode("\n ", $progressBars))->markdown();
                })
                ->dividerBlock()
                ->sectionBlock(function (SectionBlock $block) use ($slackService)  {


                    $progressBars = [];

                    foreach($this->nodeData as $node) {
                        $nodeProgressBar = $slackService->generateProgressBar($node['memoryUsedPercent'], 100, 10, $node['memoryUsedPercent'] > 80 ? SlackColour::Red : SlackColour::Blue);
                        $progressBars[] = $node['name'].": ".$nodeProgressBar.' '.$node['memoryUsedPercent'].'% ('.($node['memoryAllocated'] /1024).'GB /'.($node['memory'] /1024).'GB)';
                        
                    }
                   
                    $block->text("*Nodes in this region:* \n".implode("\n ", $progressBars))->markdown();
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
