<?php

// 8. File: database/migrations/2024_05_24_000007_create_barang_statuses_table.php
// (Struktur diubah signifikan untuk log yang lebih kaya dan terhubung ke transaksi)

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
        Schema::create('barang_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_barang_qr_code');
            $table->unsignedBigInteger('id_user_pencatat')->nullable(); // User yang mencatat perubahan ini
            $table->timestamp('tanggal_pencatatan')->useCurrent();

            // Menyimpan kondisi dan status ketersediaan sebelum dan sesudah perubahan
            $table->enum('kondisi_sebelumnya', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang'])->nullable();
            $table->enum('kondisi_sesudahnya', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang'])->nullable();
            $table->enum('status_ketersediaan_sebelumnya', ['Tersedia', 'Dipinjam', 'Dalam Pemeliharaan', 'Diarsipkan/Dihapus'])->nullable();
            $table->enum('status_ketersediaan_sesudahnya', ['Tersedia', 'Dipinjam', 'Dalam Pemeliharaan', 'Diarsipkan/Dihapus'])->nullable();

            // Menyimpan info lokasi dan pemegang personal sebelum dan sesudah (jika relevan dengan perubahan)
            $table->unsignedBigInteger('id_ruangan_sebelumnya')->nullable();
            $table->unsignedBigInteger('id_ruangan_sesudahnya')->nullable();
            $table->unsignedBigInteger('id_pemegang_personal_sebelumnya')->nullable();
            $table->unsignedBigInteger('id_pemegang_personal_sesudahnya')->nullable();

            $table->text('deskripsi_kejadian')->nullable(); // Deskripsi mengapa status/kondisi berubah

            // Foreign keys ke tabel transaksi yang mungkin memicu perubahan status/kondisi
            $table->unsignedBigInteger('id_detail_peminjaman_trigger')->nullable();
            $table->unsignedBigInteger('id_mutasi_barang_trigger')->nullable();
            $table->unsignedBigInteger('id_pemeliharaan_trigger')->nullable();
            $table->unsignedBigInteger('id_detail_stok_opname_trigger')->nullable();
            $table->unsignedBigInteger('id_arsip_barang_trigger')->nullable();
            // Jika ada proses pengadaan yang mengubah status awal, bisa ditambahkan FK ke tabel pengadaan

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_barang_qr_code')->references('id')->on('barang_qr_codes')->onDelete('cascade');
            $table->foreign('id_user_pencatat')->references('id')->on('users')->onDelete('set null');
            $table->foreign('id_ruangan_sebelumnya')->references('id')->on('ruangans')->onDelete('set null');
            $table->foreign('id_ruangan_sesudahnya')->references('id')->on('ruangans')->onDelete('set null');
            $table->foreign('id_pemegang_personal_sebelumnya')->references('id')->on('users')->onDelete('set null');
            $table->foreign('id_pemegang_personal_sesudahnya')->references('id')->on('users')->onDelete('set null');
            $table->foreign('id_detail_peminjaman_trigger')->references('id')->on('detail_peminjaman')->onDelete('set null');
            $table->foreign('id_mutasi_barang_trigger')->references('id')->on('mutasi_barangs')->onDelete('set null');
            $table->foreign('id_pemeliharaan_trigger')->references('id')->on('pemeliharaans')->onDelete('set null');
            $table->foreign('id_detail_stok_opname_trigger')->references('id')->on('detail_stok_opname')->onDelete('set null');
            $table->foreign('id_arsip_barang_trigger')->references('id')->on('arsip_barangs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_statuses');
    }
};
