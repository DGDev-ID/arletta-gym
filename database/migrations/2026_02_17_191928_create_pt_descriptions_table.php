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
        Schema::create('pt_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pt_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('gym_id')->constrained('master_gyms')->onDelete('cascade');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pt_descriptions');
    }
};
