<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('stok_opname', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_operator')->constrained('users')->onDelete('cascade'); // Operator yang memulai opname
            $table->foreignId('id_ruangan')->constrained('ruangan')->onDelete('cascade'); // Ruangan tempat opname dilakukan
            $table->date('tanggal_opname'); // Tanggal opname dilakukan
            $table->enum('status', ['Sedang Berlangsung', 'Selesai'])->default('Sedang Berlangsung'); // Status opname
            $table->text('keterangan')->nullable(); // Keterangan tambahan
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_opname');
    }
};
