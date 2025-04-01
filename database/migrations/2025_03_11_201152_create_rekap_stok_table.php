<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekap_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang')->constrained('barang')->onDelete('cascade');
            $table->foreignId('id_ruangan')->constrained('ruangan')->onDelete('cascade');
            $table->enum('semester', ['I', 'II']);
            $table->year('tahun');
            $table->integer('stok')->default(0);
            $table->timestamps();

            // Index untuk mempercepat pencarian berdasarkan semester dan tahun
            $table->index(['semester', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_stok');
    }
};
