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
        Schema::create('rekap_stoks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_barang'); // FK ke barangs
            $table->unsignedBigInteger('id_ruangan'); // FK ke ruangans
            $table->integer('jumlah_tercatat_sistem'); // Jumlah tercatat di sistem pada periode rekap
            $table->integer('jumlah_fisik_terakhir')->nullable(); // Jumlah fisik terakhir yang tercatat dari stok opname
            $table->date('periode_rekap'); // Periode rekap (misal: akhir bulan)
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('id_barang')->references('id')->on('barangs')->onDelete('cascade');
            $table->foreign('id_ruangan')->references('id')->on('ruangans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_stoks');
    }
};
