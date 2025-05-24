<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mutasi_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang_qr_code')->constrained('barang_qr_code')->restrictOnDelete();
            $table->foreignId('id_ruangan_asal')->constrained('ruangan')->restrictOnDelete();
            $table->foreignId('id_ruangan_tujuan')->constrained('ruangan')->restrictOnDelete();

            $table->timestamp('tanggal_mutasi')->useCurrent();
            $table->text('alasan_pemindahan');

            $table->foreignId('id_user_admin')->constrained('users')->restrictOnDelete();
            $table->string('surat_pemindahan_path')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('tanggal_mutasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_barang');
    }
};
