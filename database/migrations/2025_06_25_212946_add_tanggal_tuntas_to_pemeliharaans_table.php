<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemeliharaans', function (Blueprint $table) {
            // Menambahkan kolom baru setelah kolom tanggal_selesai_pengerjaan
            $table->timestamp('tanggal_tuntas')->nullable()->after('tanggal_selesai_pengerjaan')->comment('Waktu barang diterima kembali oleh pelapor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pemeliharaans', function (Blueprint $table) {
            // Menghapus kolom jika migrasi di-rollback
            $table->dropColumn('tanggal_tuntas');
        });
    }
};
