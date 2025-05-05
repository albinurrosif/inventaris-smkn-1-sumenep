<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('detail_peminjaman', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_peminjaman')->constrained('peminjaman')->onDelete('cascade'); // Foreign Key ke peminjaman
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade'); // Foreign Key ke barang
            $table->foreignId('ruangan_asal')->constrained('ruangan')->onDelete('cascade'); // Ruangan asal barang
            $table->foreignId('ruangan_tujuan')->constrained('ruangan')->onDelete('cascade'); // Ruangan tujuan peminjaman
            $table->integer('jumlah_dipinjam'); // Jumlah barang yang dipinjam
            $table->dateTime('tanggal_pinjam'); // Tanggal mulai peminjaman
            $table->dateTime('tanggal_kembali'); // Tanggal pengembalian yang direncanakan
            $table->unsignedInteger('durasi_pinjam'); // Durasi peminjaman dalam hari
            $table->boolean('dapat_diperpanjang')->default(true); // Apakah dapat diperpanjang
            $table->boolean('diperpanjang')->default(false); // Status diperpanjang
            $table->unsignedInteger('jumlah_terverifikasi')->nullable(); // Jumlah yang dikembalikan setelah diverifikasi operator
            $table->dateTime('tanggal_pengembalian_aktual')->nullable(); // Tanggal pengembalian yang aktual
            $table->enum('kondisi_sebelum', ['baik', 'rusak ringan', 'rusak berat', 'hilang'])->nullable(); // Kondisi barang sebelum dipinjam
            $table->enum('kondisi_setelah', ['baik', 'rusak ringan', 'rusak berat', 'hilang'])->nullable(); // Kondisi barang setelah dikembalikan
            $table->enum('status_pengembalian', [
                'menunggu_verifikasi',
                'disetujui',
                'dipinjam',
                'dikembalikan',
                'hilang',
                'rusak',
                'ditolak'
            ])->default('menunggu_verifikasi'); // Status pengembalian
            $table->foreignId('disetujui_oleh_pengembalian')->nullable()->constrained('users')->onDelete('set null'); // Operator yang menyetujui pengembalian
            $table->foreignId('diverifikasi_oleh_pengembalian')->nullable()->constrained('users')->onDelete('set null'); // Operator yang memverifikasi pengembalian
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
