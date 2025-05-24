<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();

            // Basic item information
            $table->string('nama_barang');
            $table->string('kode_barang', 50);
            $table->string('merk_model')->nullable();
            $table->string('ukuran', 100)->nullable();
            $table->string('bahan', 100)->nullable();
            $table->year('tahun_pembuatan_pembelian');
            $table->decimal('harga_beli', 15, 2)->nullable();
            $table->string('sumber', 100)->nullable();

            // Metadata
            $table->integer('jumlah_barang')->default(0);
            $table->foreignId('id_kategori')
                ->constrained('kategori_barang')
                ->restrictOnDelete();
            $table->boolean('menggunakan_nomor_seri')->default(true);

            // Soft delete and tracking
            $table->softDeletes();
            $table->boolean('is_terhapus')->default(false);
            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
