<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:check-freezing-user-gyms')->daily();
Schedule::command('app:check-membership-end-reminder')->daily();
Schedule::command('schedule:reset-recurring-slots')->dailyAt('00:05');