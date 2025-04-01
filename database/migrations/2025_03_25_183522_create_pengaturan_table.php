<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('key')->unique(); // Nama pengaturan unik
            $table->text('value')->nullable(); // Nilai pengaturan (bisa string, angka, boolean, dll.)
            $table->string('kategori')->nullable(); // Kategori pengaturan (misal: peminjaman, akses, dll.)
            $table->enum('tipe', ['integer', 'string', 'boolean', 'json'])->default('string'); // Tipe data pengaturan
            $table->text('deskripsi')->nullable(); // Deskripsi singkat pengaturan untuk UI
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
