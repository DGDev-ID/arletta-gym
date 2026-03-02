<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_class_id')->constrained('gym_classes')->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable(); // room name or "Online"
            $table->integer('capacity')->default(20);
            $table->integer('booked_count')->default(0);
            $table->string('zoom_link')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['date', 'gym_class_id']);
            $table->index(['trainer_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
