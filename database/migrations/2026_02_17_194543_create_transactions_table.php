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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('unique_id')->unique();
            $table->enum('method', ['manual', 'midtrans']);
            $table->enum('method_midtrans_detail', ['qris', 'va'])->nullable();
            $table->enum('transaction_type', ['membership', 'full_pt', 'installment_pt']);
            $table->foreignId('membership_id')->nullable()->constrained('master_memberships')->onDelete('set null');
            $table->foreignId('full_pt_id')->nullable()->constrained('master_pt_packages')->onDelete('set null');
            $table->foreignId('installment_pt_id')->nullable()->constrained('user_pt_package_instalments')->onDelete('set null');
            $table->decimal('price', 10, 2);
            $table->decimal('midtrans_fee', 10, 2)->default(0);
            $table->decimal('ppn_fee', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->text('description')->nullable();
            $table->integer('sessions_or_days')->nullable();
            $table->string('snap_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
