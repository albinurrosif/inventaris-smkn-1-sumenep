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
        $guruUser = User::where('role', User::ROLE_GURU)->first(); // Bisa jadi pengaju

        if (!$operatorUser || !$adminUser || !$guruUser) {
            $this->command->warn('User Operator, Admin atau Guru tidak ditemukan. Jalankan UserSeeder.');
            return;
        }

        // Ambil beberapa barang untuk disimulasikan pemeliharaannya
        $barangUntukDipelihara = BarangQrCode::inRandomOrder()->take(5)->get();

        if ($barangUntukDipelihara->count() < 2) {
            $this->command->warn('Tidak cukup barang untuk PemeliharaanSeeder.');
            return;
        }

        // Pemeliharaan 1: Diajukan, menunggu persetujuan
        if ($barangUntukDipelihara->get(0)) {
            Pemeliharaan::create([
                'id_barang_qr_code' => $barangUntukDipelihara->get(0)->id,
                'id_user_pengaju' => $guruUser->id,
                'tanggal_pengajuan' => Carbon::now()->subDays(10),
                'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN,
                'catatan_pengajuan' => 'Layar laptop bergaris, mohon diperiksa.',
                'deskripsi_pekerjaan' => 'Pemeriksaan dan potensi perbaikan layar laptop.',
                'status_pengerjaan' => Pemeliharaan::STATUS_PENGERJAAN_BELUM_DIKERJAKAN,
            ]);
        }

        // Pemeliharaan 2: Disetujui, sedang dikerjakan
        if ($barangUntukDipelihara->get(1)) {
            $pemeliharaan2 = Pemeliharaan::create([
                'id_barang_qr_code' => $barangUntukDipelihara->get(1)->id,
                'id_user_pengaju' => $operatorUser->id,
                'tanggal_pengajuan' => Carbon::now()->subDays(5),
                'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI,
                'catatan_pengajuan' => 'Proyektor lampu mulai redup.',
                'id_user_penyetuju' => $adminUser->id,
                'tanggal_persetujuan' => Carbon::now()->subDays(4),
                'catatan_persetujuan' => 'Setuju, silakan ganti lampu jika perlu.',
                'id_operator_pengerjaan' => $operatorUser->id,
                'tanggal_mulai_pengerjaan' => Carbon::now()->subDays(1),
                'deskripsi_pekerjaan' => 'Penggantian lampu proyektor dan pembersihan lensa.',
                'status_pengerjaan' => Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN,
            ]);
            // Update status barang Qr Code
            $barangUntukDipelihara->get(1)->status = BarangQrCode::STATUS_DALAM_PEMELIHARAAN;
            $barangUntukDipelihara->get(1)->save();
        }

        // Pemeliharaan 3: Selesai
        if ($barangUntukDipelihara->get(2)) {
            Pemeliharaan::create([
                'id_barang_qr_code' => $barangUntukDipelihara->get(2)->id,
                'id_user_pengaju' => $guruUser->id,
                'tanggal_pengajuan' => Carbon::now()->subDays(20),
                'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI,
                'catatan_pengajuan' => 'Keyboard beberapa tombol tidak berfungsi.',
                'id_user_penyetuju' => $adminUser->id,
                'tanggal_persetujuan' => Carbon::now()->subDays(19),
                'id_operator_pengerjaan' => $operatorUser->id,
                'tanggal_mulai_pengerjaan' => Carbon::now()->subDays(15),
                'tanggal_selesai_pengerjaan' => Carbon::now()->subDays(14),
                'deskripsi_pekerjaan' => 'Pembersihan dan perbaikan konektor keyboard.',
                'biaya' => 50000,
                'status_pengerjaan' => Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
                'hasil_pemeliharaan' => 'Keyboard berfungsi normal kembali.',
                'catatan_pengerjaan' => 'Debu menumpuk di bawah tombol.',
            ]);
            // Update status barang Qr Code
            $barangUntukDipelihara->get(2)->status = BarangQrCode::STATUS_TERSEDIA;
            $barangUntukDipelihara->get(2)->kondisi = BarangQrCode::KONDISI_BAIK;
            $barangUntukDipelihara->get(2)->save();
        }


        $this->command->info('PemeliharaanSeeder selesai.');
    }
}
