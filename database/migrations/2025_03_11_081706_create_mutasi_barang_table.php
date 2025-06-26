<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mutasi_barangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang_qr_code')->constrained('barang_qr_codes')->onDelete('cascade');
            $table->string('jenis_mutasi')->nullable();
            $table->foreignId('id_ruangan_asal')->nullable()->constrained('ruangans')->onDelete('restrict');
            $table->foreignId('id_pemegang_asal')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('id_ruangan_tujuan')->nullable()->constrained('ruangans')->onDelete('restrict');
            $table->foreignId('id_pemegang_tujuan')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_mutasi')->useCurrent();
            $table->text('alasan_pemindahan')->nullable();
            $table->foreignId('id_user_admin')->nullable()->constrained('users')->onDelete('set null');
            $table->string('surat_pemindahan_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mutasi_barangs');
    }
};
