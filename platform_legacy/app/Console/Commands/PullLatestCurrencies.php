<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use App\Services\Price\Facade as Price;

class PullLatestCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:data:exchangerates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull exchange rates from exchangeratesapi.io';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('> Pulling from: exchangerates.io');
        
        // Retrieve data
        Price::retrieveExchangeRateData(false);

        $this->info('> Done!');
        return Command::SUCCESS;
    }
}
