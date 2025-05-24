<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_peminjaman')->constrained('peminjaman')->onDelete('cascade');
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade');
            $table->foreignId('ruangan_asal')->constrained('ruangan')->onDelete('cascade');
            $table->foreignId('ruangan_tujuan')->constrained('ruangan')->onDelete('cascade');
            $table->integer('jumlah_dipinjam');
            $table->dateTime('tanggal_pinjam'); // Dijadwalkan
            $table->dateTime('tanggal_kembali'); // Dijadwalkan kembali
            $table->dateTime('tanggal_dipinjam')->nullable(); // Aktual diambil
            $table->dateTime('tanggal_pengembalian_aktual')->nullable(); // Aktual kembali
            $table->unsignedInteger('durasi_pinjam');
            $table->boolean('dapat_diperpanjang')->default(true);
            $table->boolean('diperpanjang')->default(false);
            $table->unsignedInteger('jumlah_terverifikasi')->nullable();

            // Status persetujuan per item
            $table->enum('status_persetujuan', [
                'menunggu_verifikasi',  // Saat pertama kali diajukan
                'disetujui',            // Disetujui oleh operator
                'ditolak'               // Ditolak oleh operator
            ])->default('menunggu_verifikasi');

            // Status pengambilan
            $table->enum('status_pengambilan', [
                'belum_diambil',        // Setelah disetujui, belum diambil
                'sebagian_diambil',     // Sebagian jumlah sudah diambil
                'sudah_diambil'         // Semua jumlah sudah diambil
            ])->default('belum_diambil');

            $table->enum('kondisi_sebelum', ['baik', 'rusak ringan', 'rusak berat', 'hilang'])->nullable();
            $table->enum('kondisi_setelah', ['baik', 'rusak ringan', 'rusak berat', 'hilang'])->nullable();

            $table->enum('status_pengembalian', [
                'dipinjam',             // Saat ini masih dipakai
                'menunggu_verifikasi',  // Sudah diajukan pengembalian
                'dikembalikan',
                'rusak',
                'hilang',
                'ditolak'              // Pengembalian ditolak (misal kondisi tidak sesuai)
            ])->default('dipinjam');

            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_disetujui')->nullable(); // Tanggal item disetujui

            $table->foreignId('ditolak_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_ditolak')->nullable(); // Tanggal item ditolak

            $table->foreignId('pengambilan_dikonfirmasi_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_pengambilan_dikonfirmasi')->nullable(); // Tanggal konfirmasi pengambilan

            $table->foreignId('disetujui_oleh_pengembalian')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('diverifikasi_oleh_pengembalian')->nullable()->constrained('users')->onDelete('set null');

            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_peminjaman');
    }
};
