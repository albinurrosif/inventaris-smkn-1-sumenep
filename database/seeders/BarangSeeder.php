<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\Ruangan; // Import model Ruangan

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada ruangan sebelum membuat barang
        $ruangan1 = Ruangan::first(); // Ambil ruangan pertama
        $ruangan2 = Ruangan::skip(1)->first(); // Ambil ruangan kedua
        $ruangan3 = Ruangan::skip(2)->first();

        if (!$ruangan1 && !$ruangan2 && !$ruangan3) {
            $this->command->warn('Tidak ada ruangan yang tersedia. Buat ruangan terlebih dahulu.');
            return; // Hentikan seeder jika tidak ada ruangan
        }

        // Contoh data barang menggunakan firstOrCreate secara individual
        Barang::firstOrCreate(
            ['kode_barang' => 'LP-ASUS-001'],
            [
                'nama_barang' => 'Laptop ASUS ROG',
                'merk_model' => 'ROG Zephyrus G14',
                'no_seri_pabrik' => 'ABC123456789',
                'ukuran' => '14 inch',
                'bahan' => 'Metal',
                'tahun_pembuatan_pembelian' => 2023,
                'jumlah_barang' => 10,
                'harga_beli' => 15000000,
                'sumber' => 'Pembelian Kantor',
                'keadaan_barang' => 'Baik',
                'keterangan_mutasi' => null,
                'id_ruangan' => $ruangan1->id,
            ]
        );

        Barang::firstOrCreate(
            ['kode_barang' => 'PR-EPSON-002'],
            [
                'nama_barang' => 'Proyektor Epson',
                'merk_model' => 'EB-X500',
                'no_seri_pabrik' => 'XYZ987654321',
                'ukuran' => null,
                'bahan' => 'Plastik',
                'tahun_pembuatan_pembelian' => 2022,
                'jumlah_barang' => 5,
                'harga_beli' => 8000000,
                'sumber' => 'Hibah',
                'keadaan_barang' => 'Baik',
                'keterangan_mutasi' => null,
                'id_ruangan' => $ruangan2->id,
            ]
        );

        Barang::firstOrCreate(
            ['kode_barang' => 'MJ-ORB-003'],
            [
                'nama_barang' => 'Meja Guru',
                'merk_model' => 'Orbitrend',
                'no_seri_pabrik' => 'ORB23456789',
                'ukuran' => '120x60',
                'bahan' => 'Kayu',
                'tahun_pembuatan_pembelian' => 2023,
                'jumlah_barang' => 7,
                'harga_beli' => 2000000,
                'sumber' => 'Pembelian Kantor',
                'keadaan_barang' => 'Baik',
                'keterangan_mutasi' => null,
                'id_ruangan' => $ruangan3->id,
            ]
        );
    }
}
