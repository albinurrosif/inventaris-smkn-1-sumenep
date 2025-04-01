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
        Schema::create('barang_status', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade'); // Foreign Key ke barang
            $table->foreignId('id_operator')->constrained('users')->onDelete('cascade'); // Foreign Key ke user sebagai operator
            $table->date('tanggal'); // Tanggal status dicatat
            $table->text('deskripsi'); // Deskripsi status barang
            $table->enum('status', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang', 'Ditemukan', 'Diperbaiki']); // Status barang
            $table->foreignId('id_ruangan')->nullable()->constrained('ruangan')->onDelete('set null'); // Ruangan terkait, bisa null
            $table->foreignId('id_peminjaman')->nullable()->constrained('peminjaman')->onDelete('set null'); // Peminjaman terkait, bisa null
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_status');
    }
};
