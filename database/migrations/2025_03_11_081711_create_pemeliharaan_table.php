<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pemeliharaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang_qr_code')->constrained('barang_qr_codes')->onDelete('cascade');
            $table->foreignId('id_user_pengaju')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_pengajuan')->nullable()->useCurrent();
            $table->enum('status_pengajuan', ['Diajukan', 'Disetujui', 'Ditolak', 'Dibatalkan'])->default('Diajukan');
            $table->text('catatan_pengajuan')->nullable();
            $table->foreignId('id_user_penyetuju')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_persetujuan')->nullable();
            $table->text('catatan_persetujuan')->nullable();
            $table->string('foto_kerusakan_path')->nullable();
            $table->foreignId('id_operator_pengerjaan')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_mulai_pengerjaan')->nullable();
            $table->timestamp('tanggal_selesai_pengerjaan')->nullable();
            $table->timestamp('tanggal_tuntas')->nullable();
            $table->string('foto_tuntas_path')->nullable();
            $table->text('deskripsi_pekerjaan')->nullable();
            $table->decimal('biaya', 15, 2)->nullable();
            $table->enum('status_pengerjaan', ['Belum Dikerjakan', 'Sedang Dilakukan', 'Selesai', 'Gagal', 'Tidak Dapat Diperbaiki', 'Ditunda'])->default('Belum Dikerjakan');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->string('kondisi_saat_lapor')->nullable();
            $table->string('status_saat_lapor')->nullable();
            $table->text('hasil_pemeliharaan')->nullable();
            $table->string('kondisi_barang_setelah_pemeliharaan')->nullable();
            $table->text('catatan_pengerjaan')->nullable();
            $table->string('foto_perbaikan_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pemeliharaans');
    }
};
