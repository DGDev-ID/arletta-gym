<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Laravel pada PostgreSQL membuat CHECK constraint (bukan hanya enum type) saat
     * menggunakan ->enum(). Constraint lama hanya mengizinkan 'manual' dan 'midtrans'.
     * Migration ini drop constraint lama dan recreate dengan tambahan nilai 'debit'.
     */
    public function up(): void
    {
        // Cari nama check constraint yang aktual untuk kolom 'method' di tabel 'transactions'
        $constraint = DB::selectOne("
            SELECT conname
            FROM pg_constraint
            WHERE conrelid = 'transactions'::regclass
              AND contype = 'c'
              AND pg_get_constraintdef(oid) LIKE '%method%'
        ");

        if ($constraint) {
            // Drop constraint lama yang hanya mengizinkan 'manual' dan 'midtrans'
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT \"{$constraint->conname}\"");
        }

        // Recreate constraint dengan tambahan nilai 'debit' untuk VA/QRIS tanpa gateway
        DB::statement("
            ALTER TABLE transactions
            ADD CONSTRAINT transactions_method_check
            CHECK (method IN ('manual', 'midtrans', 'debit'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraint yang sudah diupdate
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_method_check");

        // Kembalikan constraint asli (hanya manual dan midtrans)
        DB::statement("
            ALTER TABLE transactions
            ADD CONSTRAINT transactions_method_check
            CHECK (method IN ('manual', 'midtrans'))
        ");
    }
};
