<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Config;
use Auth;
use Session;
use Storage;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use App\Notifications\WeeklyNodeReport;
use App\Services\Region\Service as RegionService;
use Illuminate\Support\Facades\Notification;

class TriggerWeeklyNodeReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:reports:weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger weekly reports for utilisation of nodes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->log('Generating weekly node report...');

        $regionService = \App::make(RegionService::class);
        

        $locationData = json_decode(Storage::disk('local')->get('locations.json'), true);
        if(Storage::disk('local')->has('location_previous.json')) {
            $locationDataPrevious = json_decode(Storage::disk('local')->get('locations.json'), true);
        } else {
            $locationDataPrevious = $locationData;
        }
        
        
        if(!isset($locationData['locations'])) {
            $this->log('> Location info missing');
            return Command::SUCCESS;
        }

        foreach ($locationData['locations'] as $location) {
            $locationDataObj = $location;
            $nodeData = [];
            $regionData = $regionService->getRegionFromPteroCode($location['short']);


            foreach($locationData['nodes'] as $node) {
                if($node['location'] == $location['short']) {
                    $nodeData[] = $node;
                }
            }

            
            Notification::route('slack', '#server-capacity-reports')
                    ->notify(new WeeklyNodeReport($location, $nodeData, $regionData));
        }

        // save the previous data
        Storage::disk('local')->put('locations_previous.json', json_encode($locationData));

        return Command::SUCCESS;
    }

    private function log($message, $context = []) {
        if(env('APP_DEBUG')) {
            Log::info($message, $context);
        }
    }
}
