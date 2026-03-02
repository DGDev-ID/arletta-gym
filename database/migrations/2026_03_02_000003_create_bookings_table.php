<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->onDelete('cascade');
            $table->enum('booking_type', ['in-person', 'online'])->default('in-person');
            $table->enum('status', ['confirmed', 'cancelled', 'completed', 'no-show'])->default('confirmed');
            $table->text('cancel_reason')->nullable();
            $table->text('cancel_verification')->nullable(); // typed "CANCEL"
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'class_schedule_id']);
            $table->index(['class_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
