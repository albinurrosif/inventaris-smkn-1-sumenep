<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Ruangan;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InventarisRealSeeder extends Seeder
{
    /**
     * Seed the application's database from real CSV data.
     *
     * @return void
     */
    public function run(): void
    {
        if ($this->command->confirm('Apakah Anda yakin ingin menghapus semua data inventaris dan mengisinya ulang dari file CSV?', true)) {

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $this->command->info('Menghapus data inventaris lama...');
            BarangQrCode::truncate();
            Barang::truncate();
            Ruangan::truncate();
            KategoriBarang::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->command->info('Memulai proses seeding data inventaris dari file CSV...');
            $this->seedInventaris();

            $this->command->info('✅ Seeding data inventaris rill berhasil diselesaikan.');
        } else {
            $this->command->info('Proses seeding dibatalkan.');
        }
    }

    /**
     * Seeds inventory data from CSV files.
     */
    private function seedInventaris(): void
    {
        $files = $this->getCsvFilesMapping();

        // Pastikan direktori penyimpanan QR codes ada
        $qrStoragePath = public_path('qr_codes');
        if (!file_exists($qrStoragePath)) {
            mkdir($qrStoragePath, 0755, true);
            $this->command->info("Direktori QR codes dibuat: {$qrStoragePath}");
        }

        // Cache untuk barang induk yang sudah dibuat
        $existingBarangs = [];

        foreach ($files as $fileName => $details) {
            $this->command->line("⚙️  Memproses file untuk: {$details['nama_ruangan']}");

            $ruangan = Ruangan::firstOrCreate(
                ['nama_ruangan' => $details['nama_ruangan']],
                ['kode_ruangan' => Str::slug($details['nama_ruangan'])]
            );

            $dataRows = $this->readCsv($fileName, $details['skip_rows']);

            if ($dataRows->isEmpty()) {
                $this->command->warn("‼️ Tidak ada baris data yang ditemukan setelah skipping untuk file: {$fileName}");
                continue;
            }

            foreach ($dataRows as $index => $row) {
                // --- Validasi untuk Melewatkan Baris Kosong atau Footer (Tanda Tangan dll) ---
                $rowContent = implode('', $row);
                if (trim(str_replace(';', '', $rowContent)) === '' || count(array_filter($row, 'trim')) < 2) {
                    continue;
                }

                // --- Mapping Kolom CSV ke Variabel ---
                $namaBarang = trim($row[1] ?? '');
                $merkModel = trim($row[3] ?? null);
                $noSeriPabrikAsli = trim($row[4] ?? null);
                $ukuran = trim($row[5] ?? null);
                $bahan = trim($row[6] ?? null);
                $tahunPerolehanStr = trim($row[7] ?? (string)date('Y'));
                $kodeBarangIndukCsv = trim($row[8] ?? null);
                $jumlahBarang = (int) ($row[9] ?? 0);
                $hargaPerolehanUnitStr = trim($row[10] ?? '0');
                $sumberDanaUnit = trim($row[11] ?? 'Tidak Diketahui');
                $kondisiUnit = in_array(trim($row[12] ?? ''), ['Baik', 'Kurang Baik', 'Rusak Berat']) ? trim($row[12]) : 'Baik';

                // --- Pembersihan dan Validasi Data Awal ---
                $tahunPerolehan = (int) $tahunPerolehanStr;
                $hargaPerolehanUnit = (int) preg_replace('/[^\d]/', '', $hargaPerolehanUnitStr);

                if (empty($namaBarang)) {
                    $this->command->warn("⚠️ Melewatkan baris {$index} di ruangan '{$details['nama_ruangan']}' karena nama barang kosong. Data mentah: " . json_encode($row));
                    continue;
                }
                if ($jumlahBarang <= 0) {
                    $this->command->warn("⚠️ Melewatkan baris {$index} di ruangan '{$details['nama_ruangan']}', barang '{$namaBarang}' karena jumlah barang ({$jumlahBarang}) tidak valid. Data mentah: " . json_encode($row));
                    continue;
                }

                // 1. Tentukan nama kategori berdasarkan nama barang
                $namaKategori = $this->guessKategori($namaBarang);
                $kategori = KategoriBarang::firstOrCreate(['nama_kategori' => $namaKategori]);

                // 2. Cari atau buat barang induk (hanya buat jika belum ada di cache untuk nama barang ini)
                if (!isset($existingBarangs[$namaBarang])) {
                    $barang = Barang::firstOrCreate(
                        ['nama_barang' => $namaBarang],
                        [
                            'id_kategori' => $kategori->id,
                            'kode_barang' => $kodeBarangIndukCsv, // Ambil dari CSV
                            'merk_model' => $merkModel, // Ambil dari CSV
                            'ukuran' => $ukuran, // Ambil dari CSV
                            'bahan' => $bahan, // Ambil dari CSV
                            'tahun_pembuatan' => $tahunPerolehan, // Ambil dari CSV
                            'harga_perolehan_induk' => $hargaPerolehanUnit, // Ambil dari CSV
                            'sumber_perolehan_induk' => $sumberDanaUnit, // Ambil dari CSV
                            'total_jumlah_unit' => 0, // Dibiarkan 0 karena dilacak per unit
                            'menggunakan_nomor_seri' => true, // Selalu true karena ada QR code per unit
                        ]
                    );
                    $existingBarangs[$namaBarang] = $barang;
                } else {
                    $barang = $existingBarangs[$namaBarang];
                }


                // 3. Buat entri BarangQrCode untuk setiap unit dan generate QR fisik
                for ($i = 1; $i <= $jumlahBarang; $i++) {
                    $nomorUnitPadded = str_pad($i, 3, '0', STR_PAD_LEFT);

                    // Kode Inventaris Sekolah yang unik per unit
                    // Gunakan kombinasi yang kuat seperti UUID atau Hash untuk menghindari duplikasi
                    $kodeInventarisSekolah = Str::uuid()->toString(); // Ini akan selalu unik

                    // --- Penanganan no_seri_pabrik: null jika kosong/strip, UUID jika duplikat atau tidak valid ---
                    $finalNoSeriPabrik = null; // Default ke NULL
                    $trimmedNoSeriPabrik = trim($noSeriPabrikAsli);

                    if (!empty($trimmedNoSeriPabrik) && $trimmedNoSeriPabrik !== '-') {
                        $finalNoSeriPabrik = $trimmedNoSeriPabrik;
                    }
                    // Jika finalNoSeriPabrik masih NULL, biarkan NULL, karena kolom `nullable()`

                    // --- GENERATE QR CODE FISIK ---
                    $qrFileName = Str::slug($kodeInventarisSekolah) . '.svg'; // Nama file berdasarkan kode inventaris sekolah yang unik
                    $qrFilePath = $qrStoragePath . '/' . $qrFileName;
                    $qrDbPath = null;

                    try {
                        QrCode::format('svg')
                            ->size(200)
                            ->errorCorrection('H')
                            ->generate($kodeInventarisSekolah, $qrFilePath); // Encode kode_inventaris_sekolah
                        $qrDbPath = 'qr_codes/' . $qrFileName;
                    } catch (\Exception $e) {
                        $this->command->error("❌ Gagal membuat QR code untuk {$kodeInventarisSekolah}: " . $e->getMessage());
                    }

                    // --- Coba membuat BarangQrCode ---
                    try {
                        BarangQrCode::create([
                            'id_barang' => $barang->id,
                            'id_ruangan' => $ruangan->id,
                            'no_seri_pabrik' => $finalNoSeriPabrik, // Akan null, nilai asli, atau UUID jika nanti ada try-catch
                            'kode_inventaris_sekolah' => $kodeInventarisSekolah, // Ini adalah pengenal unik utama untuk unit
                            'deskripsi_unit' => "Unit ke-{$i} dari {$namaBarang}",
                            'harga_perolehan_unit' => $hargaPerolehanUnit,
                            'tanggal_perolehan_unit' => "{$tahunPerolehan}-01-01",
                            'sumber_dana_unit' => $sumberDanaUnit,
                            'kondisi' => $kondisiUnit,
                            'qr_path' => $qrDbPath,
                        ]);
                        // $this->command->info("✅ Barang QR Code dibuat: {$kodeInventarisSekolah} (SN: " . ($finalNoSeriPabrik ?? 'NULL') . ") di ruangan {$ruangan->nama_ruangan}");

                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        // Jika tetap ada UniqueConstraintViolationException (misalnya kode_inventaris_sekolah atau no_seri_pabrik)
                        $originalError = $e->getMessage();
                        $this->command->warn("⚠️ Duplikat entri terdeteksi untuk unit {$namaBarang} (Kode: {$kodeInventarisSekolah}, SN: '{$finalNoSeriPabrik}'). Mencoba kembali dengan UUID baru untuk no_seri_pabrik dan kode_inventaris_sekolah.");

                        // Coba lagi dengan UUID baru untuk kedua kolom unik
                        try {
                            $newKodeInventarisSekolah = Str::uuid()->toString();
                            $newQrFileName = Str::slug($newKodeInventarisSekolah) . '.svg';
                            $newQrFilePath = $qrStoragePath . '/' . $newQrFileName;

                            // Regenerate QR with new unique code
                            QrCode::format('svg')->size(200)->errorCorrection('H')->generate($newKodeInventarisSekolah, $newQrFilePath);
                            $newQrDbPath = 'qr_codes/' . $newQrFileName;

                            BarangQrCode::create([
                                'id_barang' => $barang->id,
                                'id_ruangan' => $ruangan->id,
                                'no_seri_pabrik' => (string) Str::uuid(), // Selalu UUID baru di sini
                                'kode_inventaris_sekolah' => $newKodeInventarisSekolah, // UUID baru
                                'deskripsi_unit' => "Unit ke-{$i} dari {$namaBarang} (Duplikat ditangani)",
                                'harga_perolehan_unit' => $hargaPerolehanUnit,
                                'tanggal_perolehan_unit' => "{$tahunPerolehan}-01-01",
                                'sumber_dana_unit' => $sumberDanaUnit,
                                'kondisi' => $kondisiUnit,
                                'qr_path' => $newQrDbPath,
                            ]);
                            $this->command->info("✅ Barang QR Code dibuat dengan UUID baru untuk no_seri_pabrik dan kode_inventaris_sekolah.");
                        } catch (\Exception $e2) {
                            $this->command->error("❌ Gagal menyimpan Barang QR Code bahkan setelah UUID baru: {$e2->getMessage()}. Original Error: {$originalError}");
                        }
                    } catch (\Exception $e) {
                        $this->command->error("❌ Gagal menyimpan Barang QR Code untuk {$namaBarang} (Unit {$i}): " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Guesses the category name based on the item name.
     *
     * @param string $namaBarang
     * @return string
     */
    private function guessKategori(string $namaBarang): string
    {
        $nama = strtolower($namaBarang);
        if (Str::contains($nama, ['pc', 'komputer', 'printer', 'monitor', 'proyektor', 'lcd', 'speaker', 'cctv', 'ac', 'keyboard', 'mouse'])) return 'Peralatan Elektronik';
        if (Str::contains($nama, ['mesin', 'alat', 'gerinda', 'bor', 'obeng'])) return 'Peralatan Praktik';
        if (Str::contains($nama, ['meja', 'kursi', 'lemari', 'rak', 'papan', 'sofa', 'bufet'])) return 'Mebel';
        if (Str::contains($nama, ['dispenser', 'brankas', 'filling', 'arsip'])) return 'Peralatan Kantor';

        return 'Lain-lain';
    }

    private function readCsv(string $fileName, int $skipRows): \Illuminate\Support\Collection
    {
        $path = database_path("seeders/data_inventaris/{$fileName}");
        if (!file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$fileName}");
            return collect();
        }

        $data = array_map(function ($line) {
            return str_getcsv($line, ';'); // Pastikan delimiter ';'
        }, file($path));

        $dataRows = array_slice($data, $skipRows + 1);

        return collect($dataRows);
    }

    private function getCsvFilesMapping(): array
    {
        // VERIFIKASI ini adalah TUGAS PENTING ANDA.
        // Asumsi semua file memiliki 10 baris header (skip_rows = 9).
        // Sesuaikan jika ada file yang berbeda.
        return [
            "3. KIB Ruangan 1.xlsx - Air BIO Energi.csv" => ['nama_ruangan' => 'Air BIO Energi', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Alfa Mart.csv" => ['nama_ruangan' => 'Alfa Mart', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Bank Mini.csv" => ['nama_ruangan' => 'Bank Mini', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Bengkel OTKP.csv" => ['nama_ruangan' => 'Bengkel OTKP', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Dapur.csv" => ['nama_ruangan' => 'Dapur', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Kantin SMK.csv" => ['nama_ruangan' => 'Kantin SMK', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab,MM.csv" => ['nama_ruangan' => 'Lab Multimedia', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab. Akuntansi.csv" => ['nama_ruangan' => 'Lab Akuntansi', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab. APK.csv" => ['nama_ruangan' => 'Lab APK', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab. Biologi.csv" => ['nama_ruangan' => 'Lab Biologi', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab.Fisika.csv" => ['nama_ruangan' => 'Lab Fisika', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab. Hotelan.csv" => ['nama_ruangan' => 'Lab Perhotelan', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab. Pemasaran.csv" => ['nama_ruangan' => 'Lab Pemasaran', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab.RPL.csv" => ['nama_ruangan' => 'Lab RPL', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Lab.TKJ.csv" => ['nama_ruangan' => 'Lab TKJ', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Perpus.csv" => ['nama_ruangan' => 'Perpustakaan', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Pos Pantau.csv" => ['nama_ruangan' => 'Pos Pantau', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - R.Guru.csv" => ['nama_ruangan' => 'Ruang Guru', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - R. Kasek.csv" => ['nama_ruangan' => 'Ruang Kepala Sekolah', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - R. Menjahit.csv" => ['nama_ruangan' => 'Ruang Menjahit', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - R. Resepsionis.csv" => ['nama_ruangan' => 'Ruang Resepsionis', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - R. TU.csv" => ['nama_ruangan' => 'Ruang Tata Usaha', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - R. W.Kasek.csv" => ['nama_ruangan' => 'Ruang Wakil Kepala Sekolah', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - R.LSP.csv" => ['nama_ruangan' => 'Ruang LSP', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Ruang Bahasa.csv" => ['nama_ruangan' => 'Ruang Bahasa', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Ruang OSIS.csv" => ['nama_ruangan' => 'Ruang OSIS', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Ruang UKS.csv" => ['nama_ruangan' => 'Ruang UKS', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Ruang. BP.csv" => ['nama_ruangan' => 'Ruang BP', 'skip_rows' => 9],
            "3. KIB Ruangan 1.xlsx - Teaching Factory.csv" => ['nama_ruangan' => 'Teaching Factory', 'skip_rows' => 9],
        ];
    }
}
