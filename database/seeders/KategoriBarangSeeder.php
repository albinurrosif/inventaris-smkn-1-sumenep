<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriBarang;

class KategoriBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Membuat daftar Kategori Barang yang komprehensif...');

        // Struktur kategori yang lebih detail untuk SMK
        $daftarKategori = [
            // Kategori Peralatan Kantor & Administrasi
            'Perabotan Kantor & Kelas',
            'Peralatan Kantor Elektronik',
            'Brankas & Lemari Besi',

            // Kategori Komputer & Perangkat Keras
            'Komputer & Jaringan',
            'Laptop & Aksesoris',
            'Printer & Scanner',
            'Perangkat Keras Jaringan',

            // Kategori Audio Visual & Multimedia
            'Peralatan Audio Visual',
            'Peralatan Studio & Broadcasting',
            'Kamera & Aksesoris',

            // Kategori Peralatan Praktik Kejuruan
            'Peralatan Praktik RPL & TKJ',
            'Peralatan Praktik Multimedia',
            'Peralatan Praktik OTKP',
            'Peralatan Praktik Akuntansi',
            'Peralatan Praktik Perhotelan',
            'Peralatan Praktik Tata Boga',
            'Peralatan Bengkel Otomotif',

            // Kategori Umum & Penunjang
            'Peralatan Kebersihan',
            'Peralatan UKS & Kesehatan',
            'Peralatan Olahraga',
            'Perlengkapan Upacara',
            'Lain-lain',
        ];

        foreach ($daftarKategori as $nama) {
            // Model KategoriBarang akan otomatis membuat slug dari nama
            KategoriBarang::firstOrCreate(['nama_kategori' => $nama]);
        }

        $this->command->info(count($daftarKategori) . ' kategori barang berhasil dibuat atau ditemukan.');
    }
}
