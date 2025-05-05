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
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_peminjam')->constrained('users')->onDelete('cascade'); // Relasi ke users
            $table->dateTime('tanggal_pengajuan')->useCurrent(); // Tanggal pengajuan peminjaman
            $table->enum('status_pengajuan', ['menunggu_verifikasi', 'disetujui', 'dipinjam', 'selesai', 'ditolak'])->default('menunggu_verifikasi'); // Status pengajuan
            $table->foreignId('pengajuan_disetujui_oleh')->nullable()->constrained('users')->onDelete('set null'); // Admin/operator yang menyetujui pengajuan
            $table->text('keterangan')->nullable(); // Keterangan tambahan
            $table->timestamp('tanggal_disetujui')->nullable(); // Tanggal disetujui
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
