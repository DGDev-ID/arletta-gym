<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->enum('status', ['waiting', 'promoted', 'expired'])->default('waiting');
            $table->timestamp('promoted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'class_schedule_id']);
            $table->index(['class_schedule_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
