<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pemeliharaan;
use App\Models\BarangQrCode;
use App\Models\User;
use Carbon\Carbon;

class PemeliharaanSeeder extends Seeder
{
    public function run(): void
    {
        $operatorUser = User::where('role', User::ROLE_OPERATOR)->first();
        $adminUser = User::where('role', User::ROLE_ADMIN)->first();
        $guruUser = User::where('role', User::ROLE_GURU)->first();

        if (!$operatorUser || !$adminUser || !$guruUser) {
            $this->command->warn('User Operator, Admin atau Guru tidak ditemukan.');
            return;
        }

        $barangUntukDipelihara = BarangQrCode::where('status', 'Tersedia')->inRandomOrder()->take(3)->get();
        if ($barangUntukDipelihara->count() < 3) {
            $this->command->warn('Tidak cukup barang tersedia untuk PemeliharaanSeeder.');
            return;
        }

        // Skenario 1: Diajukan, menunggu persetujuan
        Pemeliharaan::create([
            'id_barang_qr_code' => $barangUntukDipelihara[0]->id,
            'id_user_pengaju' => $guruUser->id,
            'tanggal_pengajuan' => now()->subDays(10),
            'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN,
            'catatan_pengajuan' => 'Layar laptop bergaris, mohon diperiksa.',
            'deskripsi_pekerjaan' => 'Pemeriksaan dan potensi perbaikan layar laptop.',
        ]);

        // Skenario 2: Disetujui, sedang dikerjakan
        Pemeliharaan::create([
            'id_barang_qr_code' => $barangUntukDipelihara[1]->id,
            'id_user_pengaju' => $operatorUser->id,
            'tanggal_pengajuan' => now()->subDays(5),
            'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI,
            'catatan_pengajuan' => 'Proyektor lampu mulai redup.',
            'id_user_penyetuju' => $adminUser->id,
            'tanggal_persetujuan' => now()->subDays(4),
            'catatan_persetujuan' => 'Setuju, silakan ganti lampu jika perlu.',
            'id_operator_pengerjaan' => $operatorUser->id,
            'tanggal_mulai_pengerjaan' => now()->subDay(),
            'deskripsi_pekerjaan' => 'Penggantian lampu proyektor dan pembersihan lensa.',
            'status_pengerjaan' => Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN,
        ]);
        $barangUntukDipelihara[1]->update(['status' => BarangQrCode::STATUS_DALAM_PEMELIHARAAN]);

        // Skenario 3: Selesai
        Pemeliharaan::create([
            'id_barang_qr_code' => $barangUntukDipelihara[2]->id,
            'id_user_pengaju' => $guruUser->id,
            'tanggal_pengajuan' => now()->subDays(20),
            'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI,
            'catatan_pengajuan' => 'Keyboard beberapa tombol tidak berfungsi.',
            'id_user_penyetuju' => $adminUser->id,
            'tanggal_persetujuan' => now()->subDays(19),
            'id_operator_pengerjaan' => $operatorUser->id,
            'tanggal_mulai_pengerjaan' => now()->subDays(15),
            'tanggal_selesai_pengerjaan' => now()->subDays(14),
            'deskripsi_pekerjaan' => 'Pembersihan dan perbaikan konektor keyboard.',
            'biaya' => 50000,
            'status_pengerjaan' => Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
            'hasil_pemeliharaan' => 'Keyboard berfungsi normal kembali.',
            'kondisi_barang_setelah_pemeliharaan' => BarangQrCode::KONDISI_BAIK,
            'catatan_pengerjaan' => 'Debu menumpuk di bawah tombol.',
        ]);
        $barangUntukDipelihara[2]->update(['status' => BarangQrCode::STATUS_TERSEDIA, 'kondisi' => BarangQrCode::KONDISI_BAIK]);

        $this->command->info('PemeliharaanSeeder selesai.');
    }
}
