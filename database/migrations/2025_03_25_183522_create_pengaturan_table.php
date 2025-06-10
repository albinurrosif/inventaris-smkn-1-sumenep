<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel untuk menyimpan pengaturan sistem key-value.
     */
    public function up(): void
    {
        Schema::create('pengaturans', function (Blueprint $table) {
            $table->id();

            // Kunci unik untuk setiap pengaturan, e.g., 'nama_sekolah'
            $table->string('key')->unique();

            // Nilai dari pengaturan, bisa berupa teks panjang
            $table->text('value')->nullable();

            // Tipe input yang akan digunakan di form (text, textarea, image, dll.)
            $table->string('type', 50)->default('text');

            // Deskripsi singkat untuk menjelaskan fungsi pengaturan di UI
            $table->string('description')->nullable();

            // Grup untuk mengelompokkan pengaturan di UI (e.g., 'Kop Surat', 'Umum')
            $table->string('group', 100)->default('Umum');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * Menghapus tabel jika migrasi di-rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturans');
    }
};
