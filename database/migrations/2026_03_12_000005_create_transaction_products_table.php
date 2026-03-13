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
        Schema::create('transaction_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('master_products')->onDelete('cascade');
            $table->integer('quantity');
            $table->enum('type', ['in', 'out']);
            $table->decimal('buy_price', 12, 2)->nullable();
            $table->decimal('sell_price', 12, 2)->nullable();
            $table->foreignId('transaction_product_out_id')->nullable()->constrained('transaction_product_outs')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_products');
    }
};
