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
        Schema::table('master_gyms', function (Blueprint $table) {
            $table->decimal('freeze_price', 12, 2)->default(100000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_gyms', function (Blueprint $table) {
            $table->dropColumn('freeze_price');
        });
    }
};
