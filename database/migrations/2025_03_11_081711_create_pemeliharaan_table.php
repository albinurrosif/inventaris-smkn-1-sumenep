<?php

// 9. File: database/migrations/2024_05_24_000008_create_pemeliharaans_table.php
// (Struktur diubah signifikan untuk mendukung alur pengajuan dan persetujuan)

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
        Schema::create('pemeliharaans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_barang_qr_code');

            // Kolom untuk alur pengajuan pemeliharaan
            $table->unsignedBigInteger('id_user_pengaju')->nullable();
            $table->timestamp('tanggal_pengajuan')->nullable()->useCurrent();
            $table->enum('status_pengajuan', ['Diajukan', 'Disetujui', 'Ditolak', 'Dibatalkan'])->default('Diajukan');
            $table->text('catatan_pengajuan')->nullable();

            // Kolom untuk persetujuan pemeliharaan
            $table->unsignedBigInteger('id_user_penyetuju')->nullable();
            $table->timestamp('tanggal_persetujuan')->nullable();
            $table->text('catatan_persetujuan')->nullable();

            // Kolom untuk pelaksanaan pemeliharaan
            $table->unsignedBigInteger('id_operator_pengerjaan')->nullable();
            $table->timestamp('tanggal_mulai_pengerjaan')->nullable();
            $table->timestamp('tanggal_selesai_pengerjaan')->nullable();
            $table->text('deskripsi_pekerjaan')->nullable();
            $table->decimal('biaya', 15, 2)->nullable();
            $table->enum('status_pengerjaan', ['Belum Dikerjakan', 'Sedang Dilakukan', 'Selesai', 'Gagal', 'Ditunda'])->default('Belum Dikerjakan');
            $table->text('hasil_pemeliharaan')->nullable();
            $table->text('catatan_pengerjaan')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_barang_qr_code')->references('id')->on('barang_qr_codes')->onDelete('cascade');
            $table->foreign('id_user_pengaju')->references('id')->on('users')->onDelete('set null');
            $table->foreign('id_user_penyetuju')->references('id')->on('users')->onDelete('set null');
            $table->foreign('id_operator_pengerjaan')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeliharaans');
    }
};
