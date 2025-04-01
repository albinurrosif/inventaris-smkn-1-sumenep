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
        Schema::create('barang', function (Blueprint $table) {
            $table->id(); // Primary Key (Auto Increment)
            $table->string('nama_barang');
            $table->string('merk_model')->nullable();
            $table->string('no_seri_pabrik', 100)->nullable();
            $table->string('ukuran', 100)->nullable();
            $table->string('bahan', 100)->nullable();
            $table->year('tahun_pembuatan_pembelian')->nullable();
            $table->string('kode_barang', 50); // Kode unik untuk setiap barang
            $table->index('kode_barang');
            $table->integer('jumlah_barang')->default(0); // Format stok dalam bentuk register (ex: "001-022")
            $table->decimal('harga_beli', 15, 2)->nullable()->check('harga_beli >= 0');
            $table->string('sumber', 100)->nullable();
            $table->enum('keadaan_barang', ['Baik', 'Kurang Baik', 'Rusak Berat']);
            $table->text('keterangan_mutasi')->nullable();
            $table->foreignId('id_ruangan')->constrained('ruangan')->onDelete('cascade'); // Foreign key ke ruangan
            $table->timestamps();
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
