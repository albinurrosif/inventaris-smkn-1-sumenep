<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage; // Pastikan ini di-import

class BarangSeeder extends Seeder
{
public function run(): void
{
// Pastikan direktori qr\_codes ada jika createWithQrCodeImage tidak membuatnya
if (!Storage::disk('public')->exists('qr\_codes')) {
Storage::disk('public')->makeDirectory('qr\_codes');
}

$kategoriElektronik = KategoriBarang::where('nama_kategori', 'Elektronik')->first();
$kategoriMebel = KategoriBarang::where('nama_kategori', 'Mebel & Perabotan')->first()
?? KategoriBarang::where('nama_kategori', 'Mebelier')->first();

$labRpl1 = Ruangan::where('kode_ruangan', 'LAB-RPL-1')->first();
$ruangGuruUmum = Ruangan::where('kode_ruangan', 'RG-UMUM')->first();
$perpustakaan = Ruangan::where('kode_ruangan', 'PERPUS')->first();

if (!$kategoriElektronik || !$kategoriMebel) {
$this->command->warn('Kategori dasar (Elektronik/Mebel) tidak ditemukan. Pastikan KategoriBarangSeeder sudah dijalankan.');
}
if (!$labRpl1 || !$ruangGuruUmum || !$perpustakaan) {
$this->command->warn('Ruangan dasar (LAB-RPL-1/RG-UMUM/PERPUS) tidak ditemukan. Pastikan RuanganSeeder sudah dijalankan.');
}

$dataIndukBarang = [
[
'kode_barang' => 'LP-ASUS-001',
'nama_barang' => 'Laptop ASUS ROG',
'merk_model' => 'ROG Zephyrus G14',
'ukuran' => '14 inch',
'bahan' => 'Metal',
'tahun_pembuatan' => 2023,
'harga_perolehan_induk' => 15000000,
'sumber_perolehan_induk' => 'Pembelian Kantor',
'id_kategori' => $kategoriElektronik?->id,
'menggunakan_nomor_seri' => true,
'jumlah_unit_akan_dibuat' => 10, // Jumlah unit spesifik untuk jenis barang ini
'ruangan_default_unit' => $labRpl1?->id,
// Tambahkan detail perolehan unit jika ingin bervariasi dari induknya
'detail_unit' => [
'sumber_dana_awal' => 'BOS 2023',
'no_dokumen_awal' => 'INV/2023/LP001',
]
],
[
'kode_barang' => 'PR-EPSON-002',
'nama_barang' => 'Proyektor Epson',
'merk_model' => 'EB-X500',
'ukuran' => null,
'bahan' => 'Plastik',
'tahun_pembuatan' => 2022,
'harga_perolehan_induk' => 8000000,
'sumber_perolehan_induk' => 'Hibah APBN',
'id_kategori' => $kategoriElektronik?->id,
'menggunakan_nomor_seri' => true,
'jumlah_unit_akan_dibuat' => 5,
'ruangan_default_unit' => $perpustakaan?->id ?? $labRpl1?->id,
'detail_unit' => [
'sumber_dana_awal' => 'Hibah Pusat 2022',
'no_dokumen_awal' => 'BAST/HIBAH/2022/PR01',
]
],
[
'kode_barang' => 'MJ-ORB-003',
'nama_barang' => 'Meja Guru',
'merk_model' => 'Orbitrend Kayu Jati',
'ukuran' => '120x60x75 cm',
'bahan' => 'Kayu Jati',
'tahun_pembuatan' => 2023,
'harga_perolehan_induk' => 2000000,
'sumber_perolehan_induk' => 'Dana Komite',
'id_kategori' => $kategoriMebel?->id,
'menggunakan_nomor_seri' => false,
'jumlah_unit_akan_dibuat' => 7,
'ruangan_default_unit' => $ruangGuruUmum?->id,
'detail_unit' => [
'sumber_dana_awal' => 'Komite Sekolah 2023',
'no_dokumen_awal' => 'KW/KOMITE/2023/MJ05',
]
],
];

foreach ($dataIndukBarang as $data) {
if (empty($data['id_kategori']) || empty($data['ruangan_default_unit'])) {
$this->command->warn("Melewatkan barang '{$data['nama_barang']}' karena kategori atau ruangan default tidak ditemukan.");
continue;
}

$jumlahUnit = $data['jumlah_unit_akan_dibuat'];
$ruanganIdDefault = $data['ruangan_default_unit'];
$detailUnitInfo = $data['detail_unit'] ?? []; // Ambil info detail unit

// Data untuk membuat Barang (Induk)
$dataInduk = [
'id_kategori' => $data['id_kategori'],
'nama_barang' => $data['nama_barang'],
'kode_barang' => $data['kode_barang'],
'merk_model' => $data['merk_model'],
'ukuran' => $data['ukuran'],
'bahan' => $data['bahan'],
'tahun_pembuatan' => $data['tahun_pembuatan'],
'harga_perolehan_induk' => $data['harga_perolehan_induk'],
'sumber_perolehan_induk' => $data['sumber_perolehan_induk'],
'menggunakan_nomor_seri' => $data['menggunakan_nomor_seri'],
// total_jumlah_unit akan dihandle oleh model BarangQrCode
];

$barangInduk = Barang::firstOrCreate(
['kode_barang' => $data['kode_barang']],
$dataInduk
);

// Buat Unit Barang (BarangQrCode) menggunakan metode createWithQrCodeImage
for ($i = 1; $i <= $jumlahUnit; $i++) {
    $kodeInventaris=BarangQrCode::generateKodeInventarisSekolah($barangInduk->id);

    // Hindari duplikasi jika kode inventaris sudah ada (meskipun generateKodeInventarisSekolah seharusnya unik)
    if (BarangQrCode::where('kode_inventaris_sekolah', $kodeInventaris)->exists()) {
    $this->command->info("Unit dengan kode inventaris {$kodeInventaris} untuk {$barangInduk->nama_barang} sudah ada, dilewati.");
    continue;
    }

    $noSeri = null;
    if ($barangInduk->menggunakan_nomor_seri) {
    // Membuat nomor seri yang lebih unik
    $noSeri = strtoupper(substr(str_replace('-', '', $barangInduk->merk_model ?? 'SERI'), 0, 5)) .
    Carbon::now()->format('Ymd') .
    str_pad($barangInduk->id, 3, '0', STR_PAD_LEFT) .
    str_pad($i, 3, '0', STR_PAD_LEFT) .
    rand(100,999);
    // Pastikan noSeriPabrik unik jika ada constraint unique di DB
    while(BarangQrCode::where('no_seri_pabrik', $noSeri)->exists()){
    $noSeri = strtoupper(substr(str_replace('-', '', $barangInduk->merk_model ?? 'SERI'), 0, 5)) .
    Carbon::now()->format('Ymd') .
    str_pad($barangInduk->id, 3, '0', STR_PAD_LEFT) .
    str_pad($i, 3, '0', STR_PAD_LEFT) .
    rand(100,999);
    }
    }


    try {
    BarangQrCode::createWithQrCodeImage(
    idBarang: $barangInduk->id,
    idRuangan: $ruanganIdDefault,
    noSeriPabrik: $noSeri,
    kodeInventarisSekolah: $kodeInventaris, // Sudah digenerate dan dicek
    hargaPerolehanUnit: $data['harga_perolehan_induk'], // Bisa bervariasi jika ada data spesifik per unit
    tanggalPerolehanUnit: Carbon::createFromFormat('Y', $data['tahun_pembuatan'])->startOfYear()->addMonths(rand(0, 11))->addDays(rand(0, 28))->toDateString(),
    sumberDanaUnit: $detailUnitInfo['sumber_dana_awal'] ?? $data['sumber_perolehan_induk'],
    noDokumenPerolehanUnit: ($detailUnitInfo['no_dokumen_awal'] ?? 'DOC-SEED') . '/' . $i,
    kondisi: ($i % 7 == 0) ? BarangQrCode::KONDISI_KURANG_BAIK : BarangQrCode::KONDISI_BAIK, // Variasi kondisi
    status: BarangQrCode::STATUS_TERSEDIA,
    deskripsiUnit: 'Unit ke-' . $i . ' dari ' . $barangInduk->nama_barang . ' (Seeder)'
    // id_pemegang_personal bisa diisi jika ada user default yang memegang
    );
    } catch (\Exception $e) {
    $this->command->error("Gagal membuat unit barang {$kodeInventaris} untuk {$barangInduk->nama_barang}: " . $e->getMessage());
    }
    }
    }
    $this->command->info('BarangSeeder (Induk dan Unit Awal) selesai.');
    }


    }