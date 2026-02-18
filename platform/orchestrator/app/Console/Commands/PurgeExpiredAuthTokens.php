<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Interadigital\CoreModels\Models\AuthToken;

class PurgeExpiredAuthTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:purge-expired-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete auth tokens that expired more than 90 days ago';

    public function handle(): int
    {
        $deleted = AuthToken::where('expires_at', '<', now()->subDays(90))->delete();

        $this->info("Purged {$deleted} expired auth token(s).");

        return self::SUCCESS;
    }
}

