<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\User;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use Carbon\Carbon;

class PeminjamanSeeder extends Seeder
{
    public function run(): void
    {
        $guruUsers = User::where('role', User::ROLE_GURU)->take(2)->get();
        $operatorUser = User::where('role', User::ROLE_OPERATOR)->first();
        $adminUser = User::where('role', User::ROLE_ADMIN)->first();

        if ($guruUsers->isEmpty() || !$operatorUser || !$adminUser) {
            $this->command->warn('User Guru, Operator atau Admin tidak ditemukan. Jalankan UserSeeder.');
            return;
        }

        // Ambil beberapa barang yang tersedia dari ruangan yang berbeda
        $barangUnits = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNotNull('id_ruangan') // Pastikan punya ruangan asal
            ->inRandomOrder()
            ->take(10) // Ambil 10 unit untuk dibuatkan peminjaman
            ->get();

        if ($barangUnits->count() < 2) { // Butuh minimal beberapa barang untuk didistribusikan
            $this->command->warn('Tidak cukup unit barang yang tersedia untuk PeminjamanSeeder.');
            return;
        }

        $ruangPinjam = Ruangan::inRandomOrder()->first();
        if (!$ruangPinjam) {
            $this->command->warn('Tidak ada ruangan untuk tujuan peminjaman.');
            return;
        }


        // Peminjaman 1: Menunggu Persetujuan
        $peminjaman1 = Peminjaman::create([
            'id_guru' => $guruUsers->first()->id,
            'tujuan_peminjaman' => 'Kegiatan Belajar Mengajar RPL XI',
            'tanggal_pengajuan' => Carbon::now()->subDays(5),
            'tanggal_harus_kembali' => Carbon::now()->subDays(5)->addDays(7),
            'tanggal_rencana_pinjam' => Carbon::now()->subDays(4),
            'tanggal_rencana_kembali' => Carbon::now()->subDays(4)->addDays(6),
            'status' => Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
            'id_ruangan_tujuan_peminjaman' => $ruangPinjam->id,
            'catatan_peminjam' => 'Mohon segera diproses untuk KBM besok.',
        ]);
        if ($barangUnits->get(0)) {
            DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman1->id,
                'id_barang_qr_code' => $barangUnits->get(0)->id,
                'kondisi_sebelum' => $barangUnits->get(0)->kondisi,
                'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
            ]);
        }
        if ($barangUnits->get(1)) {
            DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman1->id,
                'id_barang_qr_code' => $barangUnits->get(1)->id,
                'kondisi_sebelum' => $barangUnits->get(1)->kondisi,
                'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
            ]);
        }
        // Panggil updateStatusPeminjaman setelah semua detail dibuat
        $peminjaman1->updateStatusPeminjaman();


        // Peminjaman 2: Disetujui, beberapa sudah diambil, beberapa belum
        if ($guruUsers->get(1) && $barangUnits->get(2) && $barangUnits->get(3) && $barangUnits->get(4)) {
            $peminjaman2 = Peminjaman::create([
                'id_guru' => $guruUsers->get(1)->id,
                'tujuan_peminjaman' => 'Rapat MGMP Matematika',
                'tanggal_pengajuan' => Carbon::now()->subDays(3),
                'tanggal_disetujui' => Carbon::now()->subDays(2),
                'disetujui_oleh' => $operatorUser->id,
                'tanggal_harus_kembali' => Carbon::now()->addDays(2),
                'tanggal_rencana_pinjam' => Carbon::now()->subDays(1),
                'tanggal_rencana_kembali' => Carbon::now()->addDays(3),
                'status' => Peminjaman::STATUS_DISETUJUI, // Akan diupdate oleh detail
                'id_ruangan_tujuan_peminjaman' => $ruangPinjam->id,
            ]);

            // Item 1: Disetujui, sudah diambil
            $detail2_1 = DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman2->id,
                'id_barang_qr_code' => $barangUnits->get(2)->id,
                'kondisi_sebelum' => $barangUnits->get(2)->kondisi,
                'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI, // Akan diupdate oleh konfirmasiPengambilan
            ]);
            $detail2_1->konfirmasiPengambilan($operatorUser->id); // Operator yang mengkonfirmasi

            // Item 2: Disetujui, belum diambil
            DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman2->id,
                'id_barang_qr_code' => $barangUnits->get(3)->id,
                'kondisi_sebelum' => $barangUnits->get(3)->kondisi,
                'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI,
            ]);
            // Item 3: Disetujui, sudah diambil dan dikembalikan (selesai)
            $detail2_3 = DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman2->id,
                'id_barang_qr_code' => $barangUnits->get(4)->id,
                'kondisi_sebelum' => $barangUnits->get(4)->kondisi,
                'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI,
            ]);
            $detail2_3->konfirmasiPengambilan($operatorUser->id);
            $detail2_3->verifikasiPengembalian($operatorUser->id, BarangQrCode::KONDISI_BAIK, 'Dikembalikan dengan baik.');

            $peminjaman2->updateStatusPeminjaman();
        }


        // Peminjaman 3: Selesai (semua barang sudah dikembalikan)
        if ($guruUsers->first() && $barangUnits->get(5) && $barangUnits->get(6)) {
            $peminjaman3 = Peminjaman::create([
                'id_guru' => $guruUsers->first()->id,
                'tujuan_peminjaman' => 'Pelatihan Internal Guru',
                'tanggal_pengajuan' => Carbon::now()->subDays(10),
                'tanggal_disetujui' => Carbon::now()->subDays(9),
                'disetujui_oleh' => $adminUser->id,
                'tanggal_semua_diambil' => Carbon::now()->subDays(8),
                'tanggal_selesai' => Carbon::now()->subDays(2),
                'tanggal_harus_kembali' => Carbon::now()->subDays(3),
                'status' => Peminjaman::STATUS_SELESAI,
                'id_ruangan_tujuan_peminjaman' => $ruangPinjam->id,
            ]);
            $detail3_1 = DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman3->id,
                'id_barang_qr_code' => $barangUnits->get(5)->id,
                'kondisi_sebelum' => BarangQrCode::KONDISI_BAIK,
                'kondisi_setelah' => BarangQrCode::KONDISI_BAIK,
                'tanggal_diambil' => Carbon::now()->subDays(8),
                'tanggal_dikembalikan' => Carbon::now()->subDays(2),
                'status_unit' => DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN,
            ]);
            // Karena sudah selesai, update status barang secara manual di sini
            $barangUnits->get(5)->status = BarangQrCode::STATUS_TERSEDIA;
            $barangUnits->get(5)->save();


            $detail3_2 = DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman3->id,
                'id_barang_qr_code' => $barangUnits->get(6)->id,
                'kondisi_sebelum' => BarangQrCode::KONDISI_BAIK,
                'kondisi_setelah' => BarangQrCode::KONDISI_KURANG_BAIK, // Contoh kondisi berbeda
                'tanggal_diambil' => Carbon::now()->subDays(8),
                'tanggal_dikembalikan' => Carbon::now()->subDays(2),
                'status_unit' => DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN,
                'catatan_unit' => 'Ada sedikit goresan saat dikembalikan.',
            ]);
            $barangUnits->get(6)->status = BarangQrCode::STATUS_TERSEDIA;
            $barangUnits->get(6)->kondisi = BarangQrCode::KONDISI_KURANG_BAIK;
            $barangUnits->get(6)->save();

            $peminjaman3->updateStatusPeminjaman();
        }

        $this->command->info('PeminjamanSeeder selesai.');
    }
}
