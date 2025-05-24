<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriBarang;

class KategoriBarangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama_kategori' => 'Elektronik', 'deskripsi' => 'Peralatan elektronik seperti laptop, proyektor, dll'],
            ['nama_kategori' => 'Mebelier', 'deskripsi' => 'Perabotan seperti meja, kursi, lemari, dll'],
            ['nama_kategori' => 'Dekorasi', 'deskripsi' => 'Barang dekoratif, interior, dll'],
        ];

        foreach ($data as $kategori) {
            KategoriBarang::firstOrCreate(
                ['nama_kategori' => $kategori['nama_kategori']],
                ['deskripsi' => $kategori['deskripsi']]
            );
        }
    }
}
