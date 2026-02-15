<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\NotifyLowResourceLocations as NotifyLowResourceLocationsJob;

class NotifyLowResourceLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:notifications:locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Slack notifications if there are locations with low node resources';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        NotifyLowResourceLocationsJob::dispatch();
        
        return Command::SUCCESS;
    }
}
