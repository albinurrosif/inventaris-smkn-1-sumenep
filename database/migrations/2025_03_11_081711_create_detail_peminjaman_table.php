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
        Schema::create('detail_peminjaman', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_peminjaman')->constrained('peminjaman')->onDelete('cascade'); // Foreign Key ke peminjaman
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade'); // Foreign Key ke barang
            $table->integer('jumlah_dipinjam'); // Jumlah barang yang dipinjam
            $table->integer('jumlah_terverifikasi')->nullable(); // Jumlah yang dikembalikan setelah diverifikasi operator
            $table->dateTime('tanggal_pengembalian')->nullable(); // Tanggal pengembalian yang diajukan
            $table->boolean('diperpanjang')->default(false); // Apakah peminjaman diperpanjang
            $table->text('kondisi_sebelum'); // Kondisi barang sebelum dipinjam
            $table->text('kondisi_setelah')->nullable(); // Kondisi barang setelah dikembalikan
            $table->enum(
                'status_pengembalian',
                ['belum dikembalikan', 'menunggu_verifikasi', 'dikembalikan', 'hilang', 'rusak']
            )->default('belum dikembalikan'); // Status pengembalian
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->onDelete('set null'); // Operator/Admin yang menyetujui peminjaman
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->onDelete('set null'); // Operator yang memverifikasi pengembalian
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_peminjaman');
    }
};
