<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_per_sessions', function (Blueprint $table) {
            // Tambah kolom metode pembayaran: cash atau debit
            $table->enum('payment_method', ['cash', 'debit'])->default('cash')->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_per_sessions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};

