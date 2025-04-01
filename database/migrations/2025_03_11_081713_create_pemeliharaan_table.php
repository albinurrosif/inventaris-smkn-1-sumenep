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
        Schema::create('pemeliharaan', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade'); // Foreign Key ke barang
            $table->foreignId('id_operator')->constrained('users')->onDelete('cascade'); // Foreign Key ke user sebagai operator
            $table->foreignId('id_ruangan')->nullable()->constrained('ruangan')->onDelete('set null'); // Ruangan tempat barang diperbaiki (opsional)
            $table->date('tanggal_pemeliharaan'); // Tanggal pemeliharaan dilakukan
            $table->text('deskripsi'); // Deskripsi pemeliharaan
            $table->decimal('biaya', 10, 2)->nullable(); // Biaya pemeliharaan (opsional)
            $table->enum('status', ['Diajukan', 'Diproses', 'Selesai'])->default('Diajukan'); // Status pemeliharaan
            $table->enum('hasil_pemeliharaan', ['Berhasil', 'Gagal', 'Butuh Perbaikan Lanjutan'])->nullable(); // Hasil pemeliharaan
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeliharaan');
    }
};
