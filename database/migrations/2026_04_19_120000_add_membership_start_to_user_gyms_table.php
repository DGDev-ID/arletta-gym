<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_gyms', function (Blueprint $table) {
            // store the logical start date for a membership period
            $table->date('membership_start_at')->nullable()->after('membership_end_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_gyms', function (Blueprint $table) {
            $table->dropColumn('membership_start_at');
        });
    }
};
