<?php

// 7. File: database/migrations/2024_05_24_000006_create_peminjaman_and_details_table.php
// (Tabel peminjamen diperbarui, detail_peminjaman tidak banyak berubah)

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
        // Tabel peminjamen
        Schema::create('peminjamen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_guru')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('ditolak_oleh')->nullable()->constrained('users')->onDelete('set null');

            $table->text('tujuan_peminjaman');
            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->timestamp('tanggal_disetujui')->nullable();
            $table->timestamp('tanggal_ditolak')->nullable();
            $table->timestamp('tanggal_semua_diambil')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->timestamp('tanggal_harus_kembali')->nullable();

            // Kolom tambahan sesuai model dan standar SIMA
            $table->date('tanggal_rencana_pinjam')->nullable();
            $table->date('tanggal_rencana_kembali')->nullable();
            $table->timestamp('tanggal_proses')->nullable(); // Opsional: Waktu operator mulai memproses
            $table->text('catatan_peminjam')->nullable();
            $table->unsignedBigInteger('id_ruangan_tujuan_peminjaman')->nullable(); // Lokasi tujuan penggunaan aset

            $table->boolean('dapat_diperpanjang')->default(false);
            $table->boolean('diperpanjang')->default(false);
            $table->string('catatan_operator')->nullable();
            $table->enum('status', [
                'Menunggu Persetujuan', // Alias: Diajukan
                'Disetujui',
                'Ditolak',
                'Sedang Dipinjam',
                'Selesai',
                'Terlambat',
                'Dibatalkan',
                'Menunggu Verifikasi Kembali', // Jika ada alur verifikasi pengembalian
                'Sebagian Diajukan Kembali' // Jika ada item yang ditolak/diajukan ulang
            ])->default('Menunggu Persetujuan');

            $table->timestamps();
            $table->softDeletes();


            $table->foreign('id_ruangan_tujuan_peminjaman')->references('id')->on('ruangans')->onDelete('set null');
        });

        // Tabel detail_peminjaman
        Schema::create('detail_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_peminjaman');
            $table->unsignedBigInteger('id_barang_qr_code');
            $table->enum('kondisi_sebelum', ['Baik', 'Kurang Baik', 'Rusak Berat'])->default('Baik');
            $table->enum('kondisi_setelah', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang'])->nullable();
            $table->timestamp('tanggal_diambil')->nullable();
            $table->timestamp('tanggal_dikembalikan')->nullable();
            $table->string('catatan_unit')->nullable();
            $table->enum('status_unit', [
                'Diajukan',
                'Disetujui',
                'Ditolak',
                'Diambil',
                'Dikembalikan',
                'Rusak Saat Dipinjam',
                'Hilang Saat Dipinjam'
            ])->default('Diajukan');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_peminjaman')->references('id')->on('peminjamen')->onDelete('cascade');
            $table->foreign('id_barang_qr_code')->references('id')->on('barang_qr_codes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_peminjaman');
        Schema::dropIfExists('peminjamen');
    }
};
