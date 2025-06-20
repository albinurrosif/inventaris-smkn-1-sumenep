<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Tetap jalankan UserSeeder untuk membuat akun pengguna (Admin, Guru, dll)
            UserSeeder::class,
            RuanganSeeder::class,
            KategoriBarangSeeder::class,
            BarangSeeder::class,
            // 2. Gantikan KategoriBarang, Ruangan, dan BarangSeeder dengan seeder CSV baru ini.
            // Seeder ini akan mengisi data inventaris dari awal berdasarkan file CSV.
            //InventarisRealSeeder::class, // <-- INI SEEDER BARU KITA

            // 3. Jalankan seeder lain yang tidak terkait data inventaris (jika diperlukan)
            // Seeder ini biasanya untuk data transaksi dummy atau data awal lainnya.
            PeminjamanSeeder::class,
            PemeliharaanSeeder::class,
            PengaturanSeeder::class
        ]);
    }
}
