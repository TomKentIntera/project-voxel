<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Notifications\RegionAvailabilityRequest;
use Illuminate\Support\Facades\Notification;

class TestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:notification';

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
        Notification::notify(new RegionAvailabilityRequest());
        return Command::SUCCESS;
    }
}
