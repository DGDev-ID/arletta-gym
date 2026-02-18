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
        Schema::create('pt_package_promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pt_package_id')->constrained('master_pt_packages')->onDelete('cascade');
            $table->string('unique_code')->unique()->nullable();
            $table->enum('type', ['discount_percent', 'discount_amount', 'bonus_sessions']);
            $table->decimal('value', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pt_package_promos');
    }
};
