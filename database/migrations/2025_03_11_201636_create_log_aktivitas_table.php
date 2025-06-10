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
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            // KODE BARU
            $table->foreignId('id_user')->nullable()->constrained('users')->onDelete('set null'); // Siapa yang melakukan aktivitas
            $table->string('aktivitas'); // Deskripsi aktivitas (ex: "Menambah Barang", "Meminjam Laptop X")
            $table->string('model_terkait')->nullable(); // Model yang terkait (ex: 'BarangQrCode', 'Peminjaman')
            $table->unsignedBigInteger('id_model_terkait')->nullable(); // ID dari model yang terkait
            $table->json('data_lama')->nullable(); // Snapshot data sebelum perubahan
            $table->json('data_baru')->nullable(); // Snapshot data setelah perubahan
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
