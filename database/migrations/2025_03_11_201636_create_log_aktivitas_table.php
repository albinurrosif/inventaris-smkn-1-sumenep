<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade'); // Relasi ke users
            $table->string('aksi', 100); // Jenis aksi yang dilakukan
            $table->text('deskripsi')->nullable(); // Detail aktivitas
            $table->string('ip_address', 45)->nullable(); // IP pengguna (Opsional)
            $table->text('user_agent')->nullable(); // Informasi perangkat (Opsional)
            $table->timestamps(); // created_at & updated_at otomatis
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
