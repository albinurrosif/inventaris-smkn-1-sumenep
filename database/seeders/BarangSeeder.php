<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Memulai BarangSeeder yang telah disesuaikan...');

        // Ambil data master yang dibutuhkan
        $kategoriList = KategoriBarang::all();
        $ruanganList = Ruangan::all();
        $adminUser = User::where('role', 'Admin')->first();

        if ($kategoriList->isEmpty() || $ruanganList->isEmpty() || !$adminUser) {
            $this->command->error('Kategori, Ruangan, atau User Admin tidak ditemukan. Pastikan seeder terkait sudah dijalankan.');
            return;
        }

        // Kumpulan data master barang yang lebih banyak dan bervariasi
        $dataMasterBarang = [
            // Kategori: Komputer & Jaringan
            ['nama' => 'PC All-in-One', 'kode' => 'PC-AIO-01', 'kategori' => 'Komputer & Jaringan', 'merk' => 'HP', 'harga' => 7500000, 'seri' => true, 'jml_min' => 15, 'jml_max' => 25],
            ['nama' => 'Laptop Siswa', 'kode' => 'LP-STD-02', 'kategori' => 'Laptop & Aksesoris', 'merk' => 'Acer', 'harga' => 5000000, 'seri' => true, 'jml_min' => 20, 'jml_max' => 30],
            ['nama' => 'Router Jaringan', 'kode' => 'RTR-MKT-03', 'kategori' => 'Perangkat Keras Jaringan', 'merk' => 'MikroTik', 'harga' => 1200000, 'seri' => true, 'jml_min' => 3, 'jml_max' => 5],
            ['nama' => 'Switch Hub 24 Port', 'kode' => 'SW-TPL-04', 'kategori' => 'Perangkat Keras Jaringan', 'merk' => 'TP-Link', 'harga' => 800000, 'seri' => false, 'jml_min' => 5, 'jml_max' => 10],

            // Kategori: Perabotan Kantor & Kelas
            ['nama' => 'Meja Kerja Guru', 'kode' => 'MJ-GR-05', 'kategori' => 'Perabotan Kantor & Kelas', 'merk' => 'Olympic', 'harga' => 700000, 'seri' => false, 'jml_min' => 10, 'jml_max' => 20],
            ['nama' => 'Kursi Siswa', 'kode' => 'KR-SSW-06', 'kategori' => 'Perabotan Kantor & Kelas', 'merk' => 'Chitose', 'harga' => 250000, 'seri' => false, 'jml_min' => 50, 'jml_max' => 100],
            ['nama' => 'Lemari Arsip Besi', 'kode' => 'LM-ARS-07', 'kategori' => 'Perabotan Kantor & Kelas', 'merk' => 'Lion', 'harga' => 2500000, 'seri' => false, 'jml_min' => 5, 'jml_max' => 10],
            ['nama' => 'Papan Tulis Whiteboard', 'kode' => 'PT-WBD-08', 'kategori' => 'Perabotan Kantor & Kelas', 'merk' => 'Sakura', 'harga' => 450000, 'seri' => false, 'jml_min' => 10, 'jml_max' => 15],
            ['nama' => 'Mesin Fotocopy', 'kode' => 'FC-KYC-16', 'kategori' => 'Peralatan Kantor Elektronik', 'merk' => 'Kyocera', 'harga' => 25000000, 'seri' => true, 'jml_min' => 1, 'jml_max' => 3],

            // Kategori: Peralatan Elektronik & Audio Visual
            ['nama' => 'Proyektor LCD', 'kode' => 'PRJ-EPS-09', 'kategori' => 'Peralatan Audio Visual', 'merk' => 'Epson', 'harga' => 6000000, 'seri' => true, 'jml_min' => 8, 'jml_max' => 12],
            ['nama' => 'Printer All-in-One', 'kode' => 'PRN-CN-10', 'kategori' => 'Printer & Scanner', 'merk' => 'Canon', 'harga' => 3000000, 'seri' => true, 'jml_min' => 4, 'jml_max' => 6],
            ['nama' => 'AC Split 1 PK', 'kode' => 'AC-PN-11', 'kategori' => 'Lain-lain', 'merk' => 'Panasonic', 'harga' => 4000000, 'seri' => true, 'jml_min' => 10, 'jml_max' => 15],
            ['nama' => 'CCTV Indoor', 'kode' => 'CTV-HIK-12', 'kategori' => 'Lain-lain', 'merk' => 'Hikvision', 'harga' => 500000, 'seri' => true, 'jml_min' => 15, 'jml_max' => 20],

            // Kategori: Peralatan Laboratorium
            ['nama' => 'Mikroskop Binokuler', 'kode' => 'LAB-MKB-13', 'kategori' => 'Peralatan Praktik Kejuruan', 'merk' => 'Olympus', 'harga' => 3500000, 'seri' => true, 'jml_min' => 10, 'jml_max' => 20],
            ['nama' => 'Osiloscop Digital', 'kode' => 'LAB-OSC-14', 'kategori' => 'Peralatan Praktik RPL & TKJ', 'merk' => 'Rigol', 'harga' => 5000000, 'seri' => true, 'jml_min' => 5, 'jml_max' => 8],

            // Lain-lain
            ['nama' => 'Genset 5000W', 'kode' => 'GEN-YMH-15', 'kategori' => 'Lain-lain', 'merk' => 'Yamaha', 'harga' => 15000000, 'seri' => true, 'jml_min' => 1, 'jml_max' => 2],
        ];

        DB::transaction(function () use ($dataMasterBarang, $kategoriList, $ruanganList, $adminUser) {
            $kategoriLain = $kategoriList->where('nama_kategori', 'Lain-lain')->first();

            foreach ($dataMasterBarang as $data) {
                // Mencari kategori yang sesuai
                $kategoriDitemukan = $kategoriList->where('nama_kategori', $data['kategori'])->first();

                // 1. Buat atau cari master barang
                $barangInduk = Barang::firstOrCreate(
                    ['kode_barang' => $data['kode']],
                    [
                        'nama_barang' => $data['nama'],
                        // --- BAGIAN YANG DIPERBAIKI ---
                        // Jika kategori ditemukan, gunakan id-nya. Jika tidak, gunakan id kategori "Lain-lain".
                        'id_kategori' => $kategoriDitemukan ? $kategoriDitemukan->id : $kategoriLain->id,
                        'merk_model' => $data['merk'],
                        'tahun_pembuatan' => rand(2018, 2024),
                        'harga_perolehan_induk' => $data['harga'],
                        'sumber_perolehan_induk' => ['Dana BOS', 'Hibah Pemerintah', 'Dana Komite'][array_rand(['Dana BOS', 'Hibah Pemerintah', 'Dana Komite'])],
                        'menggunakan_nomor_seri' => $data['seri'],
                        'total_jumlah_unit' => 0,
                    ]
                );

                // 2. Tentukan jumlah unit yang akan dibuat
                $jumlahUnit = rand($data['jml_min'], $data['jml_max']);
                $this->command->line("   Membuat {$jumlahUnit} unit untuk '{$data['nama']}'...");

                // 3. Buat unit-unit barangnya
                for ($i = 1; $i <= $jumlahUnit; $i++) {
                    $ruanganAcak = $ruanganList->random();
                    $tahunPerolehan = rand((int)$barangInduk->tahun_pembuatan, 2024);
                    $kondisi = (rand(1, 10) > 8) ? BarangQrCode::KONDISI_KURANG_BAIK : BarangQrCode::KONDISI_BAIK;

                    BarangQrCode::createWithQrCodeImage(
                        idBarang: $barangInduk->id,
                        idRuangan: $ruanganAcak->id,
                        noSeriPabrik: $barangInduk->menggunakan_nomor_seri ? strtoupper($data['merk']) . '-' . rand(100000, 999999) . $i : null,
                        hargaPerolehanUnit: $data['harga'],
                        tanggalPerolehanUnit: Carbon::createFromFormat('Y', $tahunPerolehan)->startOfYear()->addMonths(rand(0, 11))->toDateString(),
                        sumberDanaUnit: $barangInduk->sumber_perolehan_induk,
                        kondisi: $kondisi,
                        status: BarangQrCode::STATUS_TERSEDIA,
                        deskripsiUnit: 'Unit ke-' . $i . ' dari ' . $barangInduk->nama_barang . ' (Seeder Otomatis)',
                        idPemegangPersonal: null,
                        idPemegangPencatat: $adminUser->id
                    );
                }
            }
        });

        $this->command->info('BarangSeeder yang disesuaikan telah selesai dijalankan.');
    }
}
