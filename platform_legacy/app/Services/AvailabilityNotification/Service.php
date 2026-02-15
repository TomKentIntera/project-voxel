<?php

namespace App\Services\AvailabilityNotification;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

use App\Models\AvailabilityNotification;

use App\Notifications\AvailabilityNotification as AvailabilityNotificationRequest;

use App\Services\Log\Service as LogService;

class Service
{
    private LogService $logger;

    public function __construct(LogService $logger) {
        $this->logger = $logger;
    }

    public function dispatchAvailabilityNotifications() {
        $this->log('Starting AvailabilityNotification dispatch');

        // Get the latest locations.json
        $locationsData = json_decode(Storage::disk('local')->get('locations.json'), true);

        // Iterate over each location
        foreach($locationsData['locations'] as $location) {
            $this->log('> Running on location:', [$location['short']]);
            
            // Get the availabile maxFreeRAM for each location
            $locationAvailableRAMMax = $location['maxFreeMemory'];
            $this->log('> Location available Max RAM:', [$locationAvailableRAMMax]);

            // Get the region data
            $regionData = null;
            foreach(Config::get('plans.locations') as $loc) {
                if($loc['ptero_location'] === $location['short']) {
                    $regionData = $loc;
                }
            }

            // figure out which plans are valid for this RAM amount
            $validPlansInRegion = [];

            foreach(Config::get('plans.planList') as $planList) {
                if($planList['ram'] * 1024 <= $locationAvailableRAMMax) {
                    $validPlansInRegion[] = $planList;
                }
            }
            $this->log('> Available plans in region: ', [$validPlansInRegion]);

            // Find all outstanding availability notifications
            foreach($validPlansInRegion as $validPlan) {
                $outstandingNotifications = AvailabilityNotification::where('region', $location['short'])->where('plan', $validPlan['name'])->where('sent', 0)->get();

                if(count($outstandingNotifications) > 0) {
                    $this->log('> Found pending notifications for plan "'.$validPlan['name'].'":', $outstandingNotifications->toArray());

                } else {
                    $this->log('> Found no pending notifications for plan "'.$validPlan['name'].'":', $outstandingNotifications->toArray());

                }

                // Dispatch 
                foreach($outstandingNotifications as $outstandingNotification) {
                    
                    $outstandingNotification->notify(new AvailabilityNotificationRequest($outstandingNotification, $validPlan, $regionData));
                    //$outstandingNotification->sent = true;
                    $outstandingNotification->save();
                    $this->log('> Sent notification to:', [$outstandingNotification, $validPlan, $regionData]);
                }
            }

            
        }
    }

    private function log($message, $context = []) {
        if(env('APP_DEBUG')) {
            Log::info($message, $context);
        }
    }
}