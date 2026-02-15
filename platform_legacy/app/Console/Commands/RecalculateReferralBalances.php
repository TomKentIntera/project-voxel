<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\Referral\Service as ReferralService;
use App\Models\User;

use Illuminate\Database\Eloquent\Builder;

class RecalculateReferralBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:referral:balanceupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all referral balances';

    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService) {
        parent::__construct();
        $this->referralService = $referralService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('> Beginning balance update job...');

        $usersWithReferralTransactions = User::whereHas('referralTransactions')->chunkById(200, function ($users) {
            foreach($users as $user) {
                $this->line('> Updating user: '.$user->id);
                $this->referralService->recalculateReferralBalanceForUser($user);
                $this->line('> Updated user');
            }
        }, $column = 'id');

        $this->line('> FINISHED!');


        return Command::SUCCESS;
    }
}
