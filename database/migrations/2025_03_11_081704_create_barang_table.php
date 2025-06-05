<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kategori'); // FK ke kategori_barangs
            $table->string('nama_barang');
            $table->string('kode_barang')->nullable(); // Kode barang umum (misal: LPT-ACER-001)
            $table->string('merk_model')->nullable();
            $table->string('ukuran')->nullable();
            $table->string('bahan')->nullable();
            $table->year('tahun_pembuatan')->nullable(); // Tahun pembuatan barang
            $table->decimal('harga_perolehan_induk', 15, 2)->nullable(); // Harga rata-rata atau harga perolehan jenis barang
            $table->string('sumber_perolehan_induk')->nullable(); // Misal: BOS, APBN, Hibah

            // Jumlah barang di sini hanya relevan jika barang tidak dilacak per unit (menggunakan_nomor_seri = false)
            $table->integer('total_jumlah_unit')->default(0);

            $table->boolean('menggunakan_nomor_seri')->default(true); // true jika dilacak per unit dengan QR, false jika per kelompok

            $table->timestamps();
            $table->softDeletes(); // Untuk soft delete jenis barang

            $table->foreign('id_kategori')->references('id')->on('kategori_barangs')->onDelete('restrict'); // restrict agar kategori tidak terhapus jika masih ada barang
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
