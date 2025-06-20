<?php

// database/migrations/YYYY_MM_DD_HHMMSS_add_pernah_terlambat_to_peminjamen_table.php

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
        Schema::table('peminjamen', function (Blueprint $table) {
            $table->boolean('pernah_terlambat')->default(false)->after('diperpanjang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjamen', function (Blueprint $table) {
            $table->dropColumn('pernah_terlambat');
        });
    }
};
