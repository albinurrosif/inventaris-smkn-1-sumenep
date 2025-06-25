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
        Schema::table('stok_opname', function (Blueprint $table) {
            // Menambahkan kolom setelah kolom 'catatan'
            $table->timestamp('tanggal_mulai_pengerjaan')->nullable()->after('catatan');
            $table->timestamp('tanggal_selesai_pengerjaan')->nullable()->after('tanggal_mulai_pengerjaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stok_opname', function (Blueprint $table) {
            $table->dropColumn('tanggal_mulai_pengerjaan');
            $table->dropColumn('tanggal_selesai_pengerjaan');
        });
    }
};
