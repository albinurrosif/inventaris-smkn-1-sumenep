<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('mutasi_barang', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade'); // Relasi ke barang
            $table->foreignId('id_ruangan_lama')->constrained('ruangan')->onDelete('cascade'); // Ruangan asal
            $table->foreignId('id_ruangan_baru')->nullable()->constrained('ruangan')->onDelete('set null');// Ruangan tujuan
            $table->date('tanggal_mutasi'); // Tanggal mutasi barang
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_barang');
    }
};
