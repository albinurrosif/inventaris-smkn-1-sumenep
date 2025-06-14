<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KibSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Memulai KIB (Kartu Inventaris Barang) Seeder...');

        // 1. Siapkan Kategori Default
        $kategoriDefault = KategoriBarang::firstOrCreate(
            ['nama_kategori' => 'Perlengkapan Umum'],
            ['slug' => 'perlengkapan-umum']
        );
        $this->command->info("Kategori '{$kategoriDefault->nama_kategori}' telah disiapkan.");

        // 2. Definisikan mapping nama file ke nama ruangan
        $fileMap = [
            '3. KIB Ruangan 1.xlsx - Air BIO Energi.csv' => 'Ruang Air BIO Energi',
            '3. KIB Ruangan 1.xlsx - Alfa Mart.csv' => 'Ruang Alfa Mart',
            '3. KIB Ruangan 1.xlsx - Bank Mini.csv' => 'Ruang Bank Mini',
            '3. KIB Ruangan 1.xlsx - Bengkel OTKP.csv' => 'Bengkel OTKP',
            '3. KIB Ruangan 1.xlsx - Dapur.csv' => 'Dapur',
            '3. KIB Ruangan 1.xlsx - Kantin SMK.csv' => 'Kantin SMK',
            '3. KIB Ruangan 1.xlsx - Lab,MM.csv' => 'Lab. Multimedia',
            '3. KIB Ruangan 1.xlsx - Lab. Akuntansi.csv' => 'Lab. Akuntansi',
            '3. KIB Ruangan 1.xlsx - Lab. APK.csv' => 'Lab. OTKP',
            '3. KIB Ruangan 1.xlsx - Lab. Biologi.csv' => 'Lab. Biologi',
            '3. KIB Ruangan 1.xlsx - Lab.Fisika.csv' => 'Lab. Fisika',
            '3. KIB Ruangan 1.xlsx - Lab. Hotelan.csv' => 'Lab. Perhotelan',
            '3. KIB Ruangan 1.xlsx - Lab. Pemasaran.csv' => 'Lab. Pemasaran',
            '3. KIB Ruangan 1.xlsx - Lab.RPL.csv' => 'Lab. RPL',
            '3. KIB Ruangan 1.xlsx - Lab.TKJ.csv' => 'Lab. TKJ',
            '3. KIB Ruangan 1.xlsx - Perpus.csv' => 'Perpustakaan',
            '3. KIB Ruangan 1.xlsx - Pos Pantau.csv' => 'Pos Pantau',
            '3. KIB Ruangan 1.xlsx - R.Guru.csv' => 'Ruang Guru',
            '3. KIB Ruangan 1.xlsx - R. Kasek.csv' => 'Ruang Kepala Sekolah',
            '3. KIB Ruangan 1.xlsx - R. Menjahit.csv' => 'Ruang Menjahit',
            '3. KIB Ruangan 1.xlsx - R. Resepsionis.csv' => 'Ruang Resepsionis',
            '3. KIB Ruangan 1.xlsx - R. TU.csv' => 'Ruang TU',
            '3. KIB Ruangan 1.xlsx - R. W.Kasek.csv' => 'Ruang Wakil Kepala Sekolah',
            '3. KIB Ruangan 1.xlsx - R.LSP.csv' => 'Ruang LSP',
            '3. KIB Ruangan 1.xlsx - Ruang UKS.csv' => 'Ruang UKS',
            '3. KIB Ruangan 1.xlsx - Ruang OSIS.csv' => 'Ruang OSIS',
            '3. KIB Ruangan 1.xlsx - Ruang. BP.csv' => 'Ruang BP',
            '3. KIB Ruangan 1.xlsx - Teaching Factory.csv' => 'Teaching Factory',
            '3. KIB Ruangan 1.xlsx - Ruang Bahasa.csv' => 'Ruang Bahasa',
        ];

        $totalUnitDibuat = 0;

        foreach ($fileMap as $fileName => $namaRuangan) {
            $this->command->comment("Memproses file untuk Ruangan: {$namaRuangan}...");

            // 3. Siapkan Ruangan
            $ruangan = Ruangan::firstOrCreate(
                ['nama_ruangan' => $namaRuangan],
                ['kode_ruangan' => Str::slug($namaRuangan, '-')]
            );

            $path = storage_path('app/uploads/' . $fileName);
            if (!file_exists($path)) {
                $this->command->error("File tidak ditemukan: {$path}");
                continue;
            }

            $csvData = array_map('str_getcsv', file($path));
            // Hapus baris header yang mungkin lebih dari satu
            $headerIndex = $this->findHeaderRow($csvData);
            if ($headerIndex === null) {
                $this->command->warn("Header 'Nama Barang' tidak ditemukan di file {$fileName}. Dilewati.");
                continue;
            }
            $dataRows = array_slice($csvData, $headerIndex + 1);

            foreach ($dataRows as $row) {
                if (empty($row[0]) || empty($row[1])) { // Lewati baris kosong
                    continue;
                }

                // Mapping Kolom ke Variabel
                $kodeBarang             = trim($row[2] ?? '');
                $namaBarang             = trim($row[1] ?? 'Nama Barang Tidak Ditemukan');
                $merkModel              = trim($row[3] ?? null);
                $noSeri                 = trim($row[4] ?? null);
                $tahunBeli              = trim($row[5] ?? date('Y'));
                $asalBarang             = trim($row[6] ?? 'Lain-lain');
                $jumlah                 = (int) (trim($row[7] ?? 1));
                $harga                  = (float) preg_replace("/[^0-9]/", "", $row[8] ?? 0);
                $kondisiTeks            = trim($row[9] ?? 'Baik');
                $keterangan             = trim($row[10] ?? null);

                if (empty($kodeBarang)) {
                    $kodeBarang = 'KIB-' . Str::slug(substr($namaBarang, 0, 10)) . '-' . rand(100, 999);
                }
                if ($jumlah == 0) $jumlah = 1;

                // 4. Buat atau cari Master Barang
                $barangInduk = Barang::firstOrCreate(
                    ['kode_barang' => $kodeBarang],
                    [
                        'nama_barang' => $namaBarang,
                        'id_kategori' => $kategoriDefault->id,
                        'merk_model' => $merkModel,
                        'tahun_pembuatan' => is_numeric($tahunBeli) ? $tahunBeli : date('Y'),
                        'harga_perolehan_induk' => $harga,
                        'sumber_perolehan_induk' => $asalBarang,
                        'menggunakan_nomor_seri' => !empty($noSeri) && $jumlah === 1,
                    ]
                );

                // 5. Normalisasi Kondisi
                $kondisi = $this->normalizeKondisi($kondisiTeks);

                // 6. Buat Unit Barang
                for ($i = 1; $i <= $jumlah; $i++) {
                    try {
                        BarangQrCode::createWithQrCodeImage(
                            idBarang: $barangInduk->id,
                            idRuangan: $ruangan->id,
                            noSeriPabrik: ($jumlah === 1 && !empty($noSeri)) ? $noSeri : null,
                            hargaPerolehanUnit: $harga,
                            tanggalPerolehanUnit: Carbon::createFromFormat('Y', $barangInduk->tahun_pembuatan ?? date('Y'))->startOfYear()->toDateString(),
                            sumberDanaUnit: $asalBarang,
                            kondisi: $kondisi,
                            status: BarangQrCode::STATUS_TERSEDIA,
                            deskripsiUnit: $keterangan ?? "Unit dari KIB Ruangan {$ruangan->nama_ruangan}",
                            idPemegangPersonal: null,
                            idPemegangPencatat: User::where('role', 'Admin')->first()->id ?? 1 // Asumsi user id 1 adalah Admin
                        );
                        $totalUnitDibuat++;
                    } catch (\Exception $e) {
                        $this->command->error("Gagal membuat unit untuk barang '{$namaBarang}': " . $e->getMessage());
                        Log::error("KIB Seeder Error: " . $e->getMessage());
                    }
                }
            }
        }

        $this->command->info("KIB Seeder selesai. Total {$totalUnitDibuat} unit barang berhasil dibuat.");
    }

    /**
     * Cari baris header di file CSV.
     */
    private function findHeaderRow(array $csvData): ?int
    {
        foreach ($csvData as $index => $row) {
            if (isset($row[1]) && str_contains(strtolower($row[1]), 'nama barang')) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Normalisasi teks kondisi menjadi nilai enum yang valid.
     */
    private function normalizeKondisi(string $kondisiTeks): string
    {
        $kondisiLower = strtolower($kondisiTeks);
        if (str_contains($kondisiLower, 'rusak berat')) {
            return BarangQrCode::KONDISI_RUSAK_BERAT;
        }
        if (str_contains($kondisiLower, 'rusak ringan') || str_contains($kondisiLower, 'kurang baik')) {
            return BarangQrCode::KONDISI_KURANG_BAIK;
        }
        return BarangQrCode::KONDISI_BAIK;
    }
}
