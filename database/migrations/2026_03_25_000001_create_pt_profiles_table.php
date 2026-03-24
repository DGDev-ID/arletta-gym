<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pt_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pt_id')->constrained('users')->onDelete('cascade');
            $table->string('experience')->nullable();
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->json('certifications')->nullable();
            $table->json('specializations')->nullable();
            $table->string('instagram')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->timestamps();

            $table->unique('pt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pt_profiles');
    }
};
