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
            UserSeeder::class,
            KategoriBarangSeeder::class,
            RuanganSeeder::class,
            BarangSeeder::class,
            //BarangQrCodeSeeder::class,
            //KibSeeder::class,
            PeminjamanSeeder::class,
            PemeliharaanSeeder::class,
            PengaturanSeeder::class
        ]);
    }
}
