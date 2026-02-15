<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;    
use Illuminate\Support\Facades\Notification;
use App\Notifications\RegionAvailabilityWarning;

use App\Services\Region\Service as RegionService;

use Storage;
use Cache;

use Log;
use Illuminate\Support\Facades\App;

class NotifyLowResourceLocations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $minimumRAMPerLocation;
    private $cacheTime;
    private $regionService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->minimumRAMPerLocation = 8 * 1024; // 8GB
        $this->cacheTime = 60 * 60 * 24; // 24 hours between messages
        $this->regionService =  App::make(RegionService::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->log('Checking Free Node Space...');

        $locationData = json_decode(Storage::disk('local')->get('locations.json'), true);
        
        if(!isset($locationData['locations'])) {
            $this->log('> Location info missing');
            return 1;
        }

        foreach ($locationData['locations'] as $location) {
            if($location['maxFreeMemory'] < $this->minimumRAMPerLocation) {
                // do we need to notify Slack?
                $cacheKey = 'SERVERMEM.'.$location['short'];
                // is this in the cache? If not, send it
                if(Cache::has($cacheKey) == false) {
                    // send the notification
                    $this->log('> Sending notification for '. $location['short'].' (Max RAM: '.$location['maxFreeMemory'].')');
                    $region = $this->regionService->getRegionFromPteroCode($location['short']);

                    Notification::route('slack', '#server-capacity-warnings')
                    ->notify(new RegionAvailabilityWarning($location['long'], $location['maxFreeMemory'], $region['code']));

                    Cache::put($cacheKey, true, $this->cacheTime);
                } else {
                    $this->log('> Skipping notification, found cache key: '.$cacheKey);
                }

            }
        }

        return 0;
    }

    private function log($message, $context = []) {
        if(env('APP_DEBUG')) {
            Log::info($message, $context);
        }
    }
}
