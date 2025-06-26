<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
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
            $table->date('tanggal_rencana_pinjam')->nullable();
            $table->date('tanggal_rencana_kembali')->nullable();
            $table->timestamp('tanggal_proses')->nullable();
            $table->text('catatan_peminjam')->nullable();
            $table->foreignId('id_ruangan_tujuan_peminjaman')->nullable()->constrained('ruangans')->onDelete('set null');
            $table->boolean('dapat_diperpanjang')->default(false);
            $table->boolean('diperpanjang')->default(false);
            $table->boolean('pernah_terlambat')->default(false);
            $table->string('catatan_operator')->nullable();
            $table->enum('status', ['Menunggu Persetujuan', 'Disetujui', 'Ditolak', 'Sedang Dipinjam', 'Selesai', 'Terlambat', 'Dibatalkan', 'Menunggu Verifikasi Kembali', 'Sebagian Diajukan Kembali'])->default('Menunggu Persetujuan');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detail_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_peminjaman')->constrained('peminjamen')->onDelete('cascade');
            $table->foreignId('id_barang_qr_code')->constrained('barang_qr_codes')->onDelete('cascade');
            $table->enum('kondisi_sebelum', ['Baik', 'Kurang Baik', 'Rusak Berat'])->default('Baik');
            $table->enum('kondisi_setelah', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang'])->nullable();
            $table->timestamp('tanggal_diambil')->nullable();
            $table->timestamp('tanggal_dikembalikan')->nullable();
            $table->string('catatan_unit')->nullable();
            $table->enum('status_unit', ['Diajukan', 'Disetujui', 'Ditolak', 'Diambil', 'Dikembalikan', 'Rusak Saat Dipinjam', 'Hilang Saat Dipinjam'])->default('Diajukan');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_peminjaman');
        Schema::dropIfExists('peminjamen');
    }
};
