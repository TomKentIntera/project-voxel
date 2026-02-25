<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\UpdateLocationFreeSpaceJob;
use Illuminate\Console\Command;

class UpdatePterodactylLocationFreeSpaceCommand extends Command
{
    protected $signature = 'pterodactyl:update-location-free-space';

    protected $description = 'Cache free memory values for Pterodactyl locations and nodes';

    public function handle(): int
    {
        UpdateLocationFreeSpaceJob::dispatchSync();

        $this->info('Updated Pterodactyl location and node free-space cache.');

        return self::SUCCESS;
    }
}

