<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriBarang;

class KategoriBarangSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            'Elektronik',
            'Mebel & Perabotan',
            'Alat Tulis Kantor (ATK)',
            'Peralatan Olahraga',
            'Peralatan Laboratorium',
        ];

        foreach ($kategori as $nama) {
            KategoriBarang::firstOrCreate(['nama_kategori' => $nama]); // Cukup nama_kategori [cite: 22]
        }
    }
}
