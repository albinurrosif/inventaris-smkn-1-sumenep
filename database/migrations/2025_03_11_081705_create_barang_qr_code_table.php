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
        Schema::create('barang_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_barang'); // FK ke tabel 'barangs' (induk)
            $table->unsignedBigInteger('id_ruangan')->nullable(); // FK ke tabel 'ruangans' (lokasi saat ini)
            $table->string('no_seri_pabrik')->unique()->nullable(); // Nomor seri dari pabrik, bisa unik
            $table->string('kode_inventaris_sekolah')->unique()->nullable(); // Kode unik inventaris internal sekolah (PENTING!)

            $table->text('deskripsi_unit')->nullable(); // Deskripsi tambahan untuk unit spesifik
            $table->decimal('harga_perolehan_unit', 15, 2)->nullable(); // Harga perolehan unit ini (bisa berbeda dari induk)
            $table->date('tanggal_perolehan_unit')->nullable(); // Tanggal perolehan unit ini
            $table->string('sumber_dana_unit')->nullable(); // Sumber dana unit ini (misal: BOS 2023, APBD 2024)
            $table->string('no_dokumen_perolehan_unit')->nullable(); // No. BAST/SPK/Faktur untuk perolehan unit ini

            $table->enum('kondisi', ['Baik', 'Kurang Baik', 'Rusak Berat', 'Hilang'])->default('Baik');
            $table->enum('status', ['Tersedia', 'Dipinjam', 'Dalam Pemeliharaan', 'Diarsipkan/Dihapus'])->default('Tersedia'); // Status ketersediaan
            $table->string('qr_path')->nullable(); // Path ke gambar QR Code

            $table->foreignId('id_pemegang_personal')->nullable()->constrained('users')->onDelete('set null'); // FK ke tabel 'users' (pemegang unit ini, bisa guru/pegawai)
            $table->foreignId('id_pencatat')->nullable()->constrained('users')->onDelete('set null');


            $table->timestamps();
            $table->softDeletes(); // Untuk soft delete unit (akan dipindahkan ke arsip)

            $table->foreign('id_barang')->references('id')->on('barangs')->onDelete('cascade'); // Jika induk terhapus, unit ikut terhapus
            $table->foreign('id_ruangan')->references('id')->on('ruangans')->onDelete('restrict'); // restrict agar ruangan tidak terhapus jika ada barang di dalamnya

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_qr_codes');
    }
};
