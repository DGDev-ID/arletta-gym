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
        Schema::table('transaction_product_outs', function (Blueprint $table) {
            $table->decimal('cash_paid', 12, 2)->nullable()->after('payment_method');
            $table->decimal('cash_change', 12, 2)->nullable()->after('cash_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_product_outs', function (Blueprint $table) {
            $table->dropColumn(['cash_paid', 'cash_change']);
        });
    }
};
