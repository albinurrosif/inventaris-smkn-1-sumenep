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
        Schema::create('arsip_barangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_barang_qr_code')->unique(); // FK ke unit yang diarsipkan (unique karena satu unit hanya bisa diarsip sekali)


            // KODE BARU
            $table->foreignId('id_user_pengaju')->nullable()->constrained('users')->onDelete('set null'); // Siapa yang mengajukan arsip/penghapusan (operator)
            $table->foreignId('id_user_penyetuju')->nullable()->constrained('users')->onDelete('set null'); // Siapa yang menyetujui arsip/penghapusan (admin)
            $table->foreignId('dipulihkan_oleh')->nullable()->constrained('users')->onDelete('set null');   // FK ke users (admin)

            $table->enum('jenis_penghapusan', ['Rusak Berat', 'Hilang', 'Dimusnahkan', 'Dijual', 'Dihibahkan', 'Usang', 'Lain-lain'])->default('Lain-lain');
            $table->text('alasan_penghapusan')->nullable();
            $table->string('berita_acara_path')->nullable(); // Path dokumen berita acara penghapusan
            $table->string('foto_bukti_path')->nullable(); // Path foto bukti penghapusan/kondisi

            $table->timestamp('tanggal_pengajuan_arsip')->useCurrent();
            $table->timestamp('tanggal_penghapusan_resmi')->nullable(); // Tanggal aset secara resmi dihapus dari inventaris aktif

            $table->enum('status_arsip', ['Diajukan', 'Disetujui', 'Ditolak', 'Diarsipkan Permanen', 'Dipulihkan'])->default('Diajukan'); // Status alur pengarsipan


            $table->timestamp('tanggal_dipulihkan')->nullable(); // Jika aset dipulihkan

            // Simpan snapshot data unit saat diarsip untuk keperluan laporan historis
            $table->json('data_unit_snapshot')->nullable();

            $table->timestamps(); // created_at untuk kapan entri arsip dibuat, updated_at untuk perubahan status arsip

            $table->foreign('id_barang_qr_code')->references('id')->on('barang_qr_codes')->onDelete('cascade'); // Jika unit asli dihapus, arsip juga terhapus

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arsip_barangs');
    }
};
