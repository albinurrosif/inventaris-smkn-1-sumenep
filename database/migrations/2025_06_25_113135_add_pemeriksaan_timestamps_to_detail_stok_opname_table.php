<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_stok_opname', function (Blueprint $table) {
            $table->timestamp('waktu_pertama_diperiksa')->nullable()->after('catatan_fisik');
            $table->timestamp('waktu_terakhir_diperiksa')->nullable()->after('waktu_pertama_diperiksa');
        });
    }

    public function down(): void
    {
        Schema::table('detail_stok_opname', function (Blueprint $table) {
            $table->dropColumn(['waktu_pertama_diperiksa', 'waktu_terakhir_diperiksa']);
        });
    }
};
