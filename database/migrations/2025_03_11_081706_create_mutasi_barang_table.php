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
        Schema::create('mutasi_barangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_barang_qr_code'); // FK ke barang_qr_codes
            $table->unsignedBigInteger('id_ruangan_asal'); // FK ke ruangans
            $table->unsignedBigInteger('id_ruangan_tujuan'); // FK ke ruangans
            $table->timestamp('tanggal_mutasi')->useCurrent();
            $table->text('alasan_pemindahan')->nullable();
            $table->unsignedBigInteger('id_user_admin'); // Siapa yang melakukan mutasi
            $table->string('surat_pemindahan_path')->nullable(); // Path dokumen surat pemindahan
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_barang_qr_code')->references('id')->on('barang_qr_codes')->onDelete('cascade');
            $table->foreign('id_ruangan_asal')->references('id')->on('ruangans')->onDelete('restrict');
            $table->foreign('id_ruangan_tujuan')->references('id')->on('ruangans')->onDelete('restrict');
            $table->foreign('id_user_admin')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_barangs');
    }
};
