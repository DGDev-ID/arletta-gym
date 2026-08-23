<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * pt_package_id dibuat nullable agar sesi PT yang berasal dari paket bundle
     * (bukan dari master_pt_packages) tetap bisa disimpan di user_pt_packages.
     */
    public function up(): void
    {
        // PostgreSQL: drop FK constraint dulu, ubah kolom, recreate FK
        Schema::table('user_pt_packages', function (Blueprint $table) {
            $table->dropForeign(['pt_package_id']);
        });

        Schema::table('user_pt_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('pt_package_id')->nullable()->change();
            $table->foreign('pt_package_id')
                  ->references('id')
                  ->on('master_pt_packages')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_pt_packages', function (Blueprint $table) {
            $table->dropForeign(['pt_package_id']);
        });

        Schema::table('user_pt_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('pt_package_id')->nullable(false)->change();
            $table->foreign('pt_package_id')
                  ->references('id')
                  ->on('master_pt_packages')
                  ->onDelete('cascade');
        });
    }
};

