<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ResetRecurringScheduleSlots extends Command
{
    protected $signature = 'schedule:reset-recurring-slots';

    protected $description = 'Reset booked_count for past recurring class schedules and advance their date to the next occurrence. Also reset booked_count for past non-recurring schedules.';

    public function handle(): void
    {
        $today = now()->toDateString();

        // 1. Advance recurring schedules whose date has passed
        $recurringPast = ClassSchedule::where('is_recurring', true)
            ->where('date', '<', $today)
            ->get();

        foreach ($recurringPast as $schedule) {
            if ($schedule->recurring_day_of_week === null) {
                continue;
            }

            // Find the next occurrence of the recurring day starting from today
            $dayName = Carbon::getDays()[$schedule->recurring_day_of_week]; // e.g. "Sunday"
            $nextDate = Carbon::today()->next($dayName)->toDateString();

            $schedule->update([
                'date' => $nextDate,
                'booked_count' => 0,
            ]);

            $this->line("Recurring schedule #{$schedule->id} advanced to {$nextDate}.");
        }

        // 2. Reset booked_count for past non-recurring schedules (slots appear empty for historical view)
        $nonRecurringPast = ClassSchedule::where('is_recurring', false)
            ->where('date', '<', $today)
            ->where('booked_count', '>', 0)
            ->get();

        foreach ($nonRecurringPast as $schedule) {
            $schedule->update(['booked_count' => 0]);
            $this->line("Non-recurring schedule #{$schedule->id} (date: {$schedule->date->toDateString()}) slots reset.");
        }

        $this->info("Done. {$recurringPast->count()} recurring and {$nonRecurringPast->count()} non-recurring schedules processed.");
    }
}
