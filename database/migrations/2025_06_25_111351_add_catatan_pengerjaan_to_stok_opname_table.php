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
        // dalam method up()
        Schema::table('stok_opname', function (Blueprint $table) {
            // Menambahkan kolom setelah kolom 'catatan'
            $table->text('catatan_pengerjaan')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // dalam method down()
        Schema::table('stok_opname', function (Blueprint $table) {
            $table->dropColumn('catatan_pengerjaan');
        });
    }
};
