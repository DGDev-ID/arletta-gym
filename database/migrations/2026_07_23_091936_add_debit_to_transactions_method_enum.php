<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PostgreSQL: tambah nilai 'debit' ke tipe enum untuk kolom method di tabel transactions.
     * Mencari nama enum type secara dinamis dari pg_type + pg_attribute.
     */
    public function up(): void
    {
        // Cari nama enum type yang digunakan oleh kolom 'method' di tabel 'transactions'
        $result = DB::selectOne("
            SELECT t.typname
            FROM pg_type t
            JOIN pg_attribute a ON a.atttypid = t.oid
            JOIN pg_class c ON c.oid = a.attrelid
            WHERE c.relname = 'transactions'
              AND a.attname = 'method'
              AND t.typtype = 'e'
        ");

        if ($result) {
            DB::statement("ALTER TYPE \"{$result->typname}\" ADD VALUE IF NOT EXISTS 'debit'");
        }
    }

    /**
     * Reverse the migrations.
     * PostgreSQL tidak mendukung penghapusan nilai dari enum type (tidak ada rollback).
     */
    public function down(): void
    {
        // PostgreSQL tidak bisa menghapus nilai dari enum setelah ditambahkan.
    }
};
