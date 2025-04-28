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
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_peminjam')->constrained('users')->onDelete('cascade'); // Relasi ke users
            $table->foreignId('id_ruangan')->constrained('ruangan')->onDelete('cascade'); // Relasi ke ruangan
            $table->dateTime('tanggal_pinjam'); // Tanggal peminjaman
            $table->dateTime('tanggal_kembali')->nullable(); // Tanggal pengembalian dihitung dari durasi_pinjam
            $table->integer('durasi_pinjam'); // Durasi peminjaman (diambil dari pengaturan atau diinput admin)
            $table->boolean('dapat_diperpanjang'); // Apakah peminjaman bisa diperpanjang (diambil dari pengaturan)
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->onDelete('set null'); // Admin/operator yang memproses
            $table->enum('status', ['menunggu', 'menunggu_verifikasi_pengembalian', 'dipinjam', 'dikembalikan'])->default('menunggu'); // Status peminjaman
            $table->text('keterangan')->nullable(); // Keterangan tambahan
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
