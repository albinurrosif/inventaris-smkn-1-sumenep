<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stok_opname', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ruangan')->constrained('ruangans')->onDelete('cascade');
            $table->foreignId('id_operator')->constrained('users')->onDelete('cascade');
            $table->date('tanggal_opname');
            $table->text('catatan')->nullable();
            $table->enum('status', ['Draft', 'Selesai', 'Dibatalkan'])->default('Draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detail_stok_opname', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_stok_opname')->constrained('stok_opname')->onDelete('cascade');
            $table->foreignId('id_barang_qr_code')->constrained('barang_qr_codes')->onDelete('cascade');
            $table->enum('kondisi_tercatat', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang', 'Diarsipkan'])->default('Baik');
            // Memastikan 'Ditemukan' ada di enum kondisi_fisik
            $table->enum('kondisi_fisik', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang', 'Ditemukan'])->nullable();
            $table->text('catatan_fisik')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_stok_opname');
        Schema::dropIfExists('stok_opname');
    }
};
