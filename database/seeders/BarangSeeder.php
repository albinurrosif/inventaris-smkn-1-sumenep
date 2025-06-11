<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;
use Carbon\Carbon;

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriElektronik = KategoriBarang::where('nama_kategori', 'Elektronik')->first();
        $kategoriMebel = KategoriBarang::where('nama_kategori', 'Mebel & Perabotan')->first();

        $labRpl1 = Ruangan::where('kode_ruangan', 'LAB-RPL-1')->first();
        $ruangGuruUmum = Ruangan::where('kode_ruangan', 'RG-UMUM')->first();
        $perpustakaan = Ruangan::where('kode_ruangan', 'PERPUS')->first();

        if (!$kategoriElektronik || !$kategoriMebel || !$labRpl1 || !$ruangGuruUmum) {
            $this->command->warn('Pastikan Seeder KategoriBarang dan Ruangan sudah dijalankan dan berhasil.');
            return;
        }

        $dataIndukBarang = [
            [
                'kode_barang' => 'LP-ASUS-001',
                'nama_barang' => 'Laptop ASUS ROG',
                'merk_model' => 'ROG Zephyrus G14',
                'id_kategori' => $kategoriElektronik->id,
                'menggunakan_nomor_seri' => true,
                'jumlah_unit_akan_dibuat' => 10,
                'ruangan_default_unit' => $labRpl1->id,
                'detail_induk' => ['tahun_pembuatan' => 2023, 'harga_perolehan_induk' => 15000000, 'sumber_perolehan_induk' => 'Pembelian Kantor'],
                'detail_unit' => ['sumber_dana_unit' => 'BOS 2023', 'no_dokumen_perolehan_unit' => 'INV/2023/LP001']
            ],
            [
                'kode_barang' => 'PR-EPSON-002',
                'nama_barang' => 'Proyektor Epson',
                'merk_model' => 'EB-X500',
                'id_kategori' => $kategoriElektronik->id,
                'menggunakan_nomor_seri' => true,
                'jumlah_unit_akan_dibuat' => 5,
                'ruangan_default_unit' => $perpustakaan->id,
                'detail_induk' => ['tahun_pembuatan' => 2022, 'harga_perolehan_induk' => 8000000, 'sumber_perolehan_induk' => 'Hibah APBN'],
                'detail_unit' => ['sumber_dana_unit' => 'Hibah Pusat 2022', 'no_dokumen_perolehan_unit' => 'BAST/HIBAH/2022/PR01']
            ],
            [
                'kode_barang' => 'MJ-ORB-003',
                'nama_barang' => 'Meja Guru',
                'merk_model' => 'Orbitrend Kayu Jati',
                'id_kategori' => $kategoriMebel->id,
                'menggunakan_nomor_seri' => false,
                'jumlah_unit_akan_dibuat' => 7,
                'ruangan_default_unit' => $ruangGuruUmum->id,
                'detail_induk' => ['tahun_pembuatan' => 2023, 'harga_perolehan_induk' => 2000000, 'sumber_perolehan_induk' => 'Dana Komite'],
                'detail_unit' => ['sumber_dana_unit' => 'Komite Sekolah 2023', 'no_dokumen_perolehan_unit' => 'KW/KOMITE/2023/MJ05']
            ],
        ];

        foreach ($dataIndukBarang as $data) {
            $dataInduk = array_merge($data, $data['detail_induk']);
            unset($dataInduk['jumlah_unit_akan_dibuat'], $dataInduk['ruangan_default_unit'], $dataInduk['detail_induk'], $dataInduk['detail_unit']);

            $barangInduk = Barang::firstOrCreate(
                ['kode_barang' => $data['kode_barang']],
                $dataInduk
            );

            for ($i = 1; $i <= $data['jumlah_unit_akan_dibuat']; $i++) {
                $kodeInventaris = BarangQrCode::generateKodeInventarisSekolah($barangInduk->id);
                if (BarangQrCode::where('kode_inventaris_sekolah', $kodeInventaris)->exists()) {
                    continue;
                }

                $noSeri = null;
                if ($barangInduk->menggunakan_nomor_seri) {
                    $noSeri = substr(strtoupper($barangInduk->merk_model), 0, 5) . Carbon::now()->format('Ymd') . str_pad($barangInduk->id, 3, '0', STR_PAD_LEFT) . str_pad($i, 3, '0', STR_PAD_LEFT) . rand(100, 999);
                }

                try {
                    BarangQrCode::createWithQrCodeImage(
                        idBarang: $barangInduk->id,
                        idRuangan: $data['ruangan_default_unit'],
                        noSeriPabrik: $noSeri,
                        kodeInventarisSekolah: $kodeInventaris,
                        hargaPerolehanUnit: $data['detail_induk']['harga_perolehan_induk'],
                        tanggalPerolehanUnit: Carbon::createFromFormat('Y', $data['detail_induk']['tahun_pembuatan'])->startOfYear()->addMonths(rand(0, 11))->addDays(rand(0, 28))->toDateString(),
                        sumberDanaUnit: $data['detail_unit']['sumber_dana_unit'],
                        noDokumenPerolehanUnit: $data['detail_unit']['no_dokumen_perolehan_unit'] . '/' . $i,
                        kondisi: ($i % 7 == 0) ? BarangQrCode::KONDISI_KURANG_BAIK : BarangQrCode::KONDISI_BAIK,
                        status: BarangQrCode::STATUS_TERSEDIA,
                        deskripsiUnit: 'Unit ke-' . $i . ' dari ' . $barangInduk->nama_barang . ' (Seeder)'
                    );
                } catch (\Exception $e) {
                    $this->command->error("Gagal membuat unit {$kodeInventaris} untuk {$barangInduk->nama_barang}: " . $e->getMessage());
                }
            }
        }
        $this->command->info('BarangSeeder (Induk dan Unit Awal) selesai.');
    }
}
