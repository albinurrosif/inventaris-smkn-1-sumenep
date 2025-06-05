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
        Schema::create('ruangans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ruangan')->unique();
            $table->string('kode_ruangan')->unique();
            $table->unsignedBigInteger('id_operator')->nullable(); // Operator penanggung jawab ruangan
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_operator')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruangans');
    }
};
