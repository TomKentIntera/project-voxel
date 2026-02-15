<?php

namespace App\Listeners;

use App\Events\AvailabilityNotificationCreated;
use App\Notifications\AvailabilityNotificationCreated as AvailabilityNotificationCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Services\Region\Service as RegionService;
use App\Services\Plan\Service as PlanService;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class SendAvailabilityNotificationCreatedNotification
{

    private ?RegionService $regionService;
    private ?PlanService $planService;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(RegionService $regionService, PlanService $planService)
    {
        $this->regionService = $regionService;
        $this->planService = $planService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\AvailabilityNotificationCreated  $event
     * @return void
     */
    public function handle(AvailabilityNotificationCreated $event)
    {
        
        Log::info('Handling availability notification created event...');
        $region = $this->regionService->getRegionFromPteroCode($event->notification->region);
        $plan = $this->planService->getPlanFromTitle($event->notification->plan);

        $event->notification->notify(new AvailabilityNotificationCreatedNotification($event->notification, $plan, $region));
    }
}
