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
        Schema::create('master_pt_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('gym_id')->constrained('master_gyms')->onDelete('cascade');
            $table->integer('max_person');
            $table->integer('duration_in_sessions');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pt_packages');
    }
};
