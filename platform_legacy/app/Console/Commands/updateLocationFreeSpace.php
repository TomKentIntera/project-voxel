<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\UpdateLocationFreeSpaceJob;

class updateLocationFreeSpace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:data:updatefreespace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a script to update the free space on all nodes/locations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        UpdateLocationFreeSpaceJob::dispatch();
        return Command::SUCCESS;
    }
}
