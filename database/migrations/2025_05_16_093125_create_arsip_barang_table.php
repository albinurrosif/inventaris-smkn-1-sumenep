<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===== TABEL ARSIP_BARANG =====
        Schema::create('arsip_barang', function (Blueprint $table) {
            $table->id();

            // SNAPSHOT DATA (tidak ada foreign key agar data tetap ada meski induk dihapus)
            $table->string('no_seri_pabrik');
            $table->json('data_unit');

            // DATA PENGHAPUSAN
            $table->foreignId('id_user')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_dihapus')->useCurrent();
            $table->text('alasan');
            $table->string('berita_acara_path');
            $table->string('foto_bukti_path')->nullable();

            // DATA PEMULIHAN
            $table->foreignId('dipulihkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_dipulihkan')->nullable();

            $table->timestamps();

            $table->index('no_seri_pabrik');
            $table->index('tanggal_dihapus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_barang');
    }
};
