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
        Schema::create('user_pt_package_instalments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_pt_package_id')->constrained('user_pt_packages')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('ppn_fee', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->enum('status', ['paid', 'unpaid'])->default('unpaid');
            $table->dateTime('must_paid_before')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pt_package_instalments');
    }
};
