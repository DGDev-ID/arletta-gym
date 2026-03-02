<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_policy_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('answers'); // array of {question, answer: yes/no}
            $table->boolean('agreed_health_accuracy')->default(false);
            $table->boolean('agreed_terms')->default(false);
            $table->boolean('agreed_risk')->default(false);
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_policy_responses');
    }
};
