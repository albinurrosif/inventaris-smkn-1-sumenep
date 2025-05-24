<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\KategoriBarang;

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        $elektronik = KategoriBarang::where('nama_kategori', 'Elektronik')->first();
        $mebelier = KategoriBarang::where('nama_kategori', 'Mebelier')->first();

        Barang::firstOrCreate(
            ['kode_barang' => 'LP-ASUS-001'],
            [
                'nama_barang' => 'Laptop ASUS ROG',
                'merk_model' => 'ROG Zephyrus G14',
                'ukuran' => '14 inch',
                'bahan' => 'Metal',
                'tahun_pembuatan_pembelian' => 2023,
                'jumlah_barang' => 10,
                'harga_beli' => 15000000,
                'sumber' => 'Pembelian Kantor',
                'id_kategori' => $elektronik?->id,
            ]
        );

        Barang::firstOrCreate(
            ['kode_barang' => 'PR-EPSON-002'],
            [
                'nama_barang' => 'Proyektor Epson',
                'merk_model' => 'EB-X500',
                'ukuran' => null,
                'bahan' => 'Plastik',
                'tahun_pembuatan_pembelian' => 2022,
                'jumlah_barang' => 5,
                'harga_beli' => 8000000,
                'sumber' => 'Hibah',
                'id_kategori' => $elektronik?->id,
            ]
        );

        Barang::firstOrCreate(
            ['kode_barang' => 'MJ-ORB-003'],
            [
                'nama_barang' => 'Meja Guru',
                'merk_model' => 'Orbitrend',
                'ukuran' => '120x60',
                'bahan' => 'Kayu',
                'tahun_pembuatan_pembelian' => 2023,
                'jumlah_barang' => 7,
                'harga_beli' => 2000000,
                'sumber' => 'Pembelian Kantor',
                'id_kategori' => $mebelier?->id,
            ]
        );
    }
}
