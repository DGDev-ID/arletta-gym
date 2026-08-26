<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_package_promos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bundle_package_id');
            $table->string('unique_code')->nullable(); // null = global promo (otomatis aktif)
            $table->enum('type', ['discount_percent', 'discount_amount', 'bonus_days', 'bonus_sessions']);
            $table->decimal('value', 10, 2);
            $table->timestamps();

            $table->foreign('bundle_package_id')
                  ->references('id')
                  ->on('master_bundle_packages')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_package_promos');
    }
};
