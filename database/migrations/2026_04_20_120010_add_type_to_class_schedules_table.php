<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('class_schedules', 'type')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->enum('type', ['schedule', 'session'])->default('schedule');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class_schedules', 'type')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
