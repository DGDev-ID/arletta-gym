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
        Schema::create('master_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('master_gyms')->onDelete('cascade');
            $table->string('name');
            $table->integer('duration_in_days');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_memberships');
    }
};
