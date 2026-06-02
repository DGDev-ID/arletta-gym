<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Allow guest bookings (no user account required)
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_phone')->nullable()->after('guest_name');

            // Make user_id nullable so guests can book without an account
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_phone']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
