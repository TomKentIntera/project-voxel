<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('intera:notifications:availability')->hourly();
        $schedule->command('intera:data:updatefreespace')->hourly();
        $schedule->command('intera:ptero:cache')->hourly();
        $schedule->command('intera:reports:weekly')->weekly();
        $schedule->command('intera:data:exchangerates')->daily();
        $schedule->command('intera:curseforge:update')->daily();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
