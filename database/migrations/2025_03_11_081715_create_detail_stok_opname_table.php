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
        Schema::create('detail_stok_opname', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_stok_opname')->constrained('stok_opname')->onDelete('cascade'); // Relasi ke stok_opname
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade'); // Relasi ke barang
            $table->integer('jumlah_tercatat'); // Jumlah barang menurut sistem sebelum opname
            $table->integer('jumlah_fisik'); // Jumlah barang yang ditemukan saat opname
            $table->enum('kondisi', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang']); // Kondisi barang saat opname
            
            // Opsional: Jika ada barang rusak/hilang, bisa langsung buat laporan atau pemeliharaan
            $table->foreignId('id_barang_status')->nullable()->constrained('barang_status')->onDelete('set null'); 
            $table->foreignId('id_pemeliharaan')->nullable()->constrained('pemeliharaan')->onDelete('set null'); 

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_stok_opname');
    }
};
