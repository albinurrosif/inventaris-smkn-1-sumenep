<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_peminjam')->constrained('users')->onDelete('cascade'); // Relasi ke users
            $table->dateTime('tanggal_pengajuan')->useCurrent(); // Tanggal pengajuan

            // Status persetujuan keseluruhan peminjaman
            $table->enum('status_persetujuan', [
                'menunggu_verifikasi',  // Saat pertama kali diajukan
                'diproses',             // Saat sedang diproses (sebagian item sudah disetujui/ditolak)
                'disetujui',            // Semua item disetujui
                'ditolak',              // Semua item ditolak
                'sebagian_disetujui'    // Sebagian item disetujui, sebagian ditolak
            ])->default('menunggu_verifikasi');

            // Status pengambilan keseluruhan peminjaman
            $table->enum('status_pengambilan', [
                'belum_diambil',        // Belum ada item yang diambil
                'sebagian_diambil',     // Sebagian item sudah diambil
                'sudah_diambil'         // Semua item sudah diambil
            ])->default('belum_diambil');

            // Status pengembalian keseluruhan peminjaman
            $table->enum('status_pengembalian', [
                'belum_dikembalikan',      // Belum ada item yang dikembalikan
                'sebagian_dikembalikan',   // Sebagian item sudah dikembalikan
                'sudah_dikembalikan'       // Semua item sudah dikembalikan
            ])->default('belum_dikembalikan');

            $table->timestamp('tanggal_disetujui')->nullable(); // Saat peminjaman disetujui (semua)
            $table->timestamp('tanggal_semua_diambil')->nullable(); // Saat semua barang diambil
            $table->timestamp('tanggal_selesai')->nullable(); // Saat semua barang dikembalikan
            $table->foreignId('pengajuan_disetujui_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('pengajuan_ditolak_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->text('keterangan')->nullable();
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
