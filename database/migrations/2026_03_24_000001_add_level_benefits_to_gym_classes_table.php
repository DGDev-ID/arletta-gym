<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->string('level')->nullable()->after('category'); // Beginner | Intermediate | Advanced | All Levels
            $table->json('benefits')->nullable()->after('level');   // ["Burn 500+ cal", "Boost metabolism", ...]
        });
    }

    public function down(): void
    {
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->dropColumn(['level', 'benefits']);
        });
    }
};
