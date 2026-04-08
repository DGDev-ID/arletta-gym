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
            $table->timestamp('freezed_at')->nullable()->default(null);
            $table->timestamp('freezed_end_at')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_gyms', function (Blueprint $table) {
            $table->dropColumn(['freezed_at', 'freezed_end_at']);
        });
    }
};
