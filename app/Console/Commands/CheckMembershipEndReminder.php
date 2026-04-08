<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsappBlast;
use App\Models\UserGym;
use App\Models\WABlastTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMembershipEndReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-membership-end-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and send membership end reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Start checking membership end reminder');
        Log::info('now: ' . now()->toDateTimeString());
        $membershipEndReminders = UserGym::whereNull('freezed_at')->whereNull('freezed_end_at')
            ->where('membership_end_at', now()->addDays(7)->toDateString())->get();

        if ($membershipEndReminders->isEmpty()) {
            Log::info('No membership end reminder to send');
            return;
        }

        $waTemplate = WABlastTemplate::where('template_name', 'MEMBERSHIP_END_REMINDER')->first();
        foreach ($membershipEndReminders as $userGym) {
            $userPhone = $userGym->user->userDetail->phone_number;
            SendWhatsappBlast::dispatch(
                $userPhone,
                $waTemplate->template_id,
                [
                    '{CUST_NAME}' => $userGym->user->name,
                    '{GYM_NAME}' => $userGym->gym->name,
                    '{MEMBERSHIP_END_AT}' => $userGym->membership_end_at->format('d F Y'),
                ]
            );
            Log::info("Success dispatch reminder membership msg for" . $userGym->user->name . " with phone number " . $userPhone);
        }
    }
}
