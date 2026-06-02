<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            // Recurring schedule support
            $table->boolean('is_recurring')->default(false)->after('is_cancelled');
            $table->tinyInteger('recurring_day_of_week')->nullable()->after('is_recurring')
                ->comment('0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday');

            // Free-text trainer name (override when trainer is not a registered user)
            $table->string('trainer_name')->nullable()->after('trainer_id');
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'recurring_day_of_week', 'trainer_name']);
        });
    }
};
