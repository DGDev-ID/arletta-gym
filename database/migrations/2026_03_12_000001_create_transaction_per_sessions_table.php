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
        Schema::create('transaction_per_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('master_gyms')->onDelete('cascade');
            $table->string('name');
            $table->string('phone_number');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['pending', 'failed', 'success'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_per_sessions');
    }
};
