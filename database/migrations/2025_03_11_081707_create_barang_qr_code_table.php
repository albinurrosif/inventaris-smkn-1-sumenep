<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('barang_qr_codes', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('kode_barang', 50); // Kode unik barang
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
            $table->string('nama_barang', 100); // Nama barang
            $table->integer('jumlah'); // Jumlah barang
            $table->text('deskripsi')->nullable(); // Deskripsi barang
            $table->string('qr_code')->nullable(); // Path atau string QR Code
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_qr_codes');
    }
};
