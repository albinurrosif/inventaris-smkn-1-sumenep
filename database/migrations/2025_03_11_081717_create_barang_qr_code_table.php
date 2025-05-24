<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===== TABEL BARANG_QR_CODE (UNIT) =====
        Schema::create('barang_qr_code', function (Blueprint $table) {
            $table->id();

            // RELASI
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade');
            $table->foreignId('id_ruangan')->constrained('ruangan')->onDelete('restrict');

            // DATA UNIT - Unik per unit individual
            $table->string('no_seri_pabrik')->unique();
            $table->enum('keadaan_barang', ['Baik', 'Kurang Baik', 'Rusak Berat'])->default('Baik');
            $table->enum('status', ['Tersedia', 'Dipinjam', 'Hilang', 'Rusak', 'Maintenance'])->default('Tersedia');
            $table->text('keterangan')->nullable();
            $table->string('qr_path')->nullable();

            // DATA PENGHAPUSAN UNIT
            $table->text('alasan_penghapusan')->nullable();
            $table->string('berita_acara')->nullable();
            $table->string('foto_pendukung')->nullable();

            // SOFT DELETE + AUDIT
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_qr_code');
    }
};
