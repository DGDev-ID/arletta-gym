<?php

namespace App\Console\Commands;

use App\Models\UserGym;
use Illuminate\Console\Command;

class CheckFreezingUserGyms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-freezing-user-gyms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and unfreeze user gyms that have reached their freeze end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredFreezes = UserGym::whereNotNull('freezed_at')
            ->whereNotNull('freezed_end_at')
            ->where('freezed_end_at', '<=', now())
            ->get();

        foreach ($expiredFreezes as $userGym) {
            $selisihHari = now()->startOfDay()->diffInDays(Carbon::parse($userGym->freezed_at)->startOfDay());
            $userGym->membership_end_at = $userGym->membership_end_at->addDays($selisihHari);
            $userGym->freezed_at = null;
            $userGym->freezed_end_at = null;
            $userGym->save();
        }

        $this->info("Processed {$expiredFreezes->count()} expired freeze(s).");
    }
}
