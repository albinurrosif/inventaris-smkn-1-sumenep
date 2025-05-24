<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;

class BarangQrCodeSeeder extends Seeder
{
    public function run(): void
    {
        if (!Storage::disk('public')->exists('qr_codes')) {
            Storage::disk('public')->makeDirectory('qr_codes');
        }

        $barangs = Barang::all();
        $ruanganIds = Ruangan::pluck('id')->toArray();

        if ($barangs->isEmpty()) {
            $this->command->warn('Tidak ada barang yang ditemukan. Pastikan BarangSeeder sudah dijalankan.');
            return;
        }

        foreach ($barangs as $barang) {
            $jumlah = $barang->jumlah_barang;

            // Tetapkan SATU ruangan acak untuk semua unit dalam agregat ini
            $id_ruangan = $ruanganIds[array_rand($ruanganIds)];

            // Generate semua unit dengan ruangan yang sama
            $this->generateQrCodes($barang, $jumlah, $id_ruangan);
        }

        $this->command->info('QR Code berhasil dibuat: ' . BarangQrCode::count() . ' unit.');
    }

    private function generateQrCodes(Barang $barang, int $count, int $id_ruangan): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $no_seri = $barang->kode_barang . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);

            if (BarangQrCode::where('no_seri_pabrik', $no_seri)->exists()) {
                continue;
            }

            $qr_image = QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->errorCorrection('H')
                ->encoding('UTF-8')
                ->generate($no_seri);

            $filename = "qr_codes/{$no_seri}.svg";
            Storage::disk('public')->put($filename, $qr_image);

            BarangQrCode::create([
                'id_barang' => $barang->id,
                'id_ruangan' => $id_ruangan, // Gunakan ruangan yang sama untuk semua unit
                'no_seri_pabrik' => $no_seri,
                'keadaan_barang' => BarangQrCode::KEADAAN_BARANG_BAIK,
                'status' => BarangQrCode::STATUS_TERSEDIA,
                'keterangan' => null,
                'qr_path' => $filename
            ]);
        }
    }
}
