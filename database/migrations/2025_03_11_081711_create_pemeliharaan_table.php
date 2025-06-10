<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Pemeliharaan; // Digunakan untuk mengakses konstanta enum

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
            $table->foreignId('id_user_pengaju')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_pengajuan')->nullable()->useCurrent();
            $table->enum('status_pengajuan', Pemeliharaan::getValidStatusPengajuan(false))->default(Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN); // Menggunakan konstanta dari model
            $table->text('catatan_pengajuan')->nullable()->comment('Deskripsi kerusakan atau keluhan awal'); // Ini untuk deskripsi kerusakan

            // Kolom untuk persetujuan pemeliharaan
            $table->foreignId('id_user_penyetuju')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_persetujuan')->nullable();
            $table->text('catatan_persetujuan')->nullable();

            // Kolom untuk pelaksanaan pemeliharaan
            $table->foreignId('id_operator_pengerjaan')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_mulai_pengerjaan')->nullable();
            $table->timestamp('tanggal_selesai_pengerjaan')->nullable();
            $table->text('deskripsi_pekerjaan')->nullable()->comment('Deskripsi pekerjaan yang dilakukan');
            $table->decimal('biaya', 15, 2)->nullable();
            $table->enum('status_pengerjaan', Pemeliharaan::getValidStatusPengerjaan(false))->default(Pemeliharaan::STATUS_PENGERJAAN_BELUM_DIKERJAKAN); // Menggunakan konstanta

            // Tambahan kolom 'prioritas'
            $table->enum('prioritas', Pemeliharaan::getValidPrioritas(false))->default(Pemeliharaan::PRIORITAS_SEDANG)->comment('Prioritas pemeliharaan');

            $table->text('hasil_pemeliharaan')->nullable()->comment('Hasil dari pemeliharaan');
            // Tambahan kolom 'kondisi_barang_setelah_pemeliharaan'
            $table->string('kondisi_barang_setelah_pemeliharaan')->nullable()->comment('Kondisi barang setelah pemeliharaan selesai');
            $table->text('catatan_pengerjaan')->nullable()->comment('Catatan tambahan dari teknisi/operator');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_barang_qr_code')->references('id')->on('barang_qr_codes')->onDelete('cascade');
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
