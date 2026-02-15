<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\Helpers\PterodactylHelper;

class updateLocationsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:ptero:cache';

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

        PterodactylHelper::cacheData();
        return Command::SUCCESS;
    }
}
