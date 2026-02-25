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
        Schema::create('user_pt_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pt_package_id')->constrained('master_pt_packages')->onDelete('cascade');
            $table->foreignId('pt_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('sessions_remaining');
            $table->enum('status', ['done_payment', 'instalment']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pt_packages');
    }
};
