<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use Carbon\Carbon;

class BarangQrCodeSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan direktori qr_codes ada
        if (!Storage::disk('public')->exists('qr_codes')) {
            Storage::disk('public')->makeDirectory('qr_codes');
        }

        $barangs = Barang::all();
        $ruanganIds = Ruangan::pluck('id')->toArray();

        if ($barangs->isEmpty()) {
            $this->command->warn('Tidak ada data di tabel "barangs". Pastikan BarangSeeder (induk) sudah dijalankan.');
            return;
        }

        if (empty($ruanganIds)) {
            $this->command->warn('Tidak ada data di tabel "ruangans". Pastikan RuanganSeeder sudah dijalankan.');
            return;
        }

        $totalUnitDibuat = 0;

        foreach ($barangs as $barang) {
            // Tentukan berapa banyak unit yang ingin dibuat per barang induk.
            // Kolom 'jumlah_barang' tidak lagi ada di tabel 'barangs' baru.
            // Anda bisa set jumlah tetap, atau mengambil dari properti lain jika ada.
            // Untuk contoh ini, kita buat 2-3 unit per barang induk.
            $jumlahUnitUntukDibuat = rand(2, 3);

            // Tetapkan SATU ruangan acak untuk semua unit dalam barang induk ini
            // (Anda bisa variasikan jika perlu)
            $id_ruangan_default = !empty($ruanganIds) ? $ruanganIds[array_rand($ruanganIds)] : null;
            if (!$id_ruangan_default) {
                $this->command->warn("Tidak ada ruangan tersedia untuk barang: {$barang->nama_barang}. Unit tidak dibuat.");
                continue;
            }

            for ($i = 1; $i <= $jumlahUnitUntukDibuat; $i++) {
                // 1. Generate Kode Inventaris Sekolah yang unik
                $kodeInventarisSekolah = BarangQrCode::generateKodeInventarisSekolah($barang->id); // [cite: 337]

                // Cek apakah unit dengan kode inventaris ini sudah ada
                if (BarangQrCode::where('kode_inventaris_sekolah', $kodeInventarisSekolah)->exists()) {
                    $this->command->info("Unit dengan kode inventaris {$kodeInventarisSekolah} sudah ada, dilewati.");
                    continue;
                }

                // 2. Generate Nomor Seri Pabrik (jika barang menggunakan nomor seri)
                $noSeriPabrik = null;
                if ($barang->menggunakan_nomor_seri) { // [cite: 34]
                    $noSeriPabrik = strtoupper(str_replace(' ', '-', $barang->merk_model ?? 'SERI')) . '-SN' . substr(uniqid(), -5) . $i;
                }

                // 3. Generate dan Simpan Gambar QR Code
                // Konten QR Code adalah Kode Inventaris Sekolah [cite: 328]
                $qrImageContent = $kodeInventarisSekolah;
                $qr_image = QrCode::format('svg')
                    ->size(300)
                    ->margin(2)
                    ->errorCorrection('H')
                    ->encoding('UTF-8')
                    ->generate($qrImageContent);

                $filename = "qr_codes/{$qrImageContent}.svg";
                Storage::disk('public')->put($filename, $qr_image);

                // 4. Buat Record BarangQrCode
                BarangQrCode::create([
                    'id_barang' => $barang->id, // [cite: 42]
                    'id_ruangan' => $id_ruangan_default, // [cite: 42]
                    'no_seri_pabrik' => $noSeriPabrik, // [cite: 42]
                    'kode_inventaris_sekolah' => $kodeInventarisSekolah, // [cite: 43]
                    'deskripsi_unit' => 'Unit Seeder ke-' . $i . ' untuk ' . $barang->nama_barang, // [cite: 43]
                    'harga_perolehan_unit' => $barang->harga_perolehan_induk ?? rand(100000, 5000000), // [cite: 43]
                    'tanggal_perolehan_unit' => Carbon::createFromFormat('Y', $barang->tahun_pembuatan ?? date('Y'))->subMonths(rand(1, 12))->toDateString(), // [cite: 43]
                    'sumber_dana_unit' => $barang->sumber_perolehan_induk ?? 'Sumber Seeder', // [cite: 43]
                    'no_dokumen_perolehan_unit' => 'DOC-SEEDER/' . date('Y/m') . '/' . rand(100, 999), // [cite: 44]
                    'kondisi' => BarangQrCode::KONDISI_BAIK, // [cite: 44, 306]
                    'status' => BarangQrCode::STATUS_TERSEDIA, // [cite: 45, 308]
                    'qr_path' => $filename, // [cite: 45]
                ]);
                $totalUnitDibuat++;
            }
        }

        $this->command->info("Seeder unit barang selesai. Total {$totalUnitDibuat} unit BarangQrCode berhasil dibuat/diperbarui.");
    }
}
