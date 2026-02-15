<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AvailabilityNotification\Service as AvailabilityNotificationService;
use Illuminate\Support\Facades\App;

class DispatchAvailabilityNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:notifications:availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $availabilityService =  App::make(AvailabilityNotificationService::class);
        $availabilityService->dispatchAvailabilityNotifications();
        return Command::SUCCESS;
    }
}
