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
        $guruUsers = User::where('role', User::ROLE_GURU)->get();
        $approver = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->first();

        if ($guruUsers->isEmpty() || !$approver) {
            $this->command->warn('Dibutuhkan minimal 1 Guru dan 1 Admin/Operator untuk PeminjamanSeeder.');
            return;
        }

        $availableUnits = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNull('deleted_at')->get();

        if ($availableUnits->count() < 10) {
            $this->command->warn('Tidak cukup unit barang tersedia untuk membuat skenario peminjaman.');
            return;
        }

        $usedUnitIds = [];

        // Skenario 1: Peminjaman Menunggu Persetujuan
        $units1 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2);
        if ($units1->count() == 2) {
            $peminjaman1 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'tujuan_peminjaman' => 'Kegiatan Belajar Mengajar Praktikum Jaringan',
                'tanggal_pengajuan' => now()->subDay(),
                'tanggal_rencana_pinjam' => now()->addDay(),
                'tanggal_harus_kembali' => now()->addDays(8),
                'status' => Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
            ]);
            foreach ($units1 as $unit) {
                DetailPeminjaman::create(['id_peminjaman' => $peminjaman1->id, 'id_barang_qr_code' => $unit->id, 'kondisi_sebelum' => $unit->kondisi]);
                $usedUnitIds[] = $unit->id;
            }
        }

        // Skenario 2: Peminjaman Disetujui, siap diambil
        $units2 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(3);
        if ($units2->count() == 3) {
            $peminjaman2 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'disetujui_oleh' => $approver->id,
                'tujuan_peminjaman' => 'Lomba Cepat Tepat antar Kelas',
                'tanggal_pengajuan' => now()->subDays(5),
                'tanggal_disetujui' => now()->subDays(4),
                'tanggal_rencana_pinjam' => now()->subDays(3),
                'tanggal_harus_kembali' => now()->addDays(4),
                'status' => Peminjaman::STATUS_DISETUJUI,
            ]);
            foreach ($units2 as $unit) {
                DetailPeminjaman::create(['id_peminjaman' => $peminjaman2->id, 'id_barang_qr_code' => $unit->id, 'status_unit' => 'Disetujui', 'kondisi_sebelum' => $unit->kondisi]);
                $usedUnitIds[] = $unit->id;
            }
        }

        // Skenario 3: Sedang Dipinjam
        $units3 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2);
        if ($units3->count() == 2) {
            $peminjaman3 = Peminjaman::create(['id_guru' => $guruUsers->random()->id, 'disetujui_oleh' => $approver->id, 'tujuan_peminjaman' => 'Pelatihan Internal Guru', 'tanggal_pengajuan' => now()->subDays(10), 'tanggal_disetujui' => now()->subDays(9), 'tanggal_semua_diambil' => now()->subDays(8), 'tanggal_rencana_pinjam' => now()->subDays(8), 'tanggal_harus_kembali' => now()->addDays(5), 'status' => Peminjaman::STATUS_SEDANG_DIPINJAM]);
            foreach ($units3 as $unit) {
                DetailPeminjaman::create(['id_peminjaman' => $peminjaman3->id, 'id_barang_qr_code' => $unit->id, 'status_unit' => 'Diambil', 'tanggal_diambil' => $peminjaman3->tanggal_semua_diambil, 'kondisi_sebelum' => $unit->kondisi]);
                $unit->update(['status' => BarangQrCode::STATUS_DIPINJAM]);
                $usedUnitIds[] = $unit->id;
            }
        }

        // Skenario 4: Selesai, ada yang rusak saat kembali
        $units4 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2);
        if ($units4->count() == 2) {
            $peminjaman4 = Peminjaman::create(['id_guru' => $guruUsers->random()->id, 'disetujui_oleh' => $approver->id, 'tujuan_peminjaman' => 'Kegiatan OSIS bulan lalu', 'tanggal_pengajuan' => now()->subDays(20), 'tanggal_disetujui' => now()->subDays(19), 'tanggal_semua_diambil' => now()->subDays(18), 'tanggal_selesai' => now()->subDays(10), 'tanggal_rencana_pinjam' => now()->subDays(18), 'tanggal_harus_kembali' => now()->subDays(11), 'status' => Peminjaman::STATUS_SELESAI]);

            $unit4_1 = $units4->first();
            DetailPeminjaman::create(['id_peminjaman' => $peminjaman4->id, 'id_barang_qr_code' => $unit4_1->id, 'status_unit' => 'Dikembalikan', 'tanggal_diambil' => $peminjaman4->tanggal_semua_diambil, 'tanggal_dikembalikan' => $peminjaman4->tanggal_selesai, 'kondisi_sebelum' => 'Baik', 'kondisi_setelah' => 'Baik']);
            $unit4_1->update(['status' => BarangQrCode::STATUS_TERSEDIA, 'kondisi' => 'Baik']);
            $usedUnitIds[] = $unit4_1->id;

            $unit4_2 = $units4->last();
            DetailPeminjaman::create(['id_peminjaman' => $peminjaman4->id, 'id_barang_qr_code' => $unit4_2->id, 'status_unit' => 'Rusak Saat Dipinjam', 'tanggal_diambil' => $peminjaman4->tanggal_semua_diambil, 'tanggal_dikembalikan' => $peminjaman4->tanggal_selesai, 'kondisi_sebelum' => 'Baik', 'kondisi_setelah' => 'Rusak Berat']);
            $unit4_2->update(['status' => BarangQrCode::STATUS_DALAM_PEMELIHARAAN, 'kondisi' => BarangQrCode::KONDISI_RUSAK_BERAT]);
            $usedUnitIds[] = $unit4_2->id;
        }

        // --- PENAMBAHAN: Skenario 5: Peminjaman Sedang Dipinjam (TERLAMBAT) ---
        $unit5 = $availableUnits->whereNotIn('id', $usedUnitIds)->first();
        if ($unit5) {
            // Logika utama: Buat tanggal harus kembali di masa lalu
            $tanggalHarusKembali = now()->subDays(rand(2, 4));
            $tanggalAmbil = $tanggalHarusKembali->copy()->subDays(rand(3, 5));
            $tanggalPengajuan = $tanggalAmbil->copy()->subDay();
            $tanggalDisetujui = $tanggalAmbil->copy()->subHours(rand(1, 5));

            $peminjaman5 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'disetujui_oleh' => $approver->id,
                'tujuan_peminjaman' => 'Presentasi Rapat Komite (Terlambat)',
                'tanggal_pengajuan' => $tanggalPengajuan,
                'tanggal_disetujui' => $tanggalDisetujui,
                'tanggal_semua_diambil' => $tanggalAmbil,
                'tanggal_rencana_pinjam' => $tanggalAmbil->toDateString(),
                'tanggal_harus_kembali' => $tanggalHarusKembali,
                // Statusnya tetap 'Sedang Dipinjam', logika aplikasi (accessor/query scope) yang akan menandainya 'Terlambat'
                'status' => Peminjaman::STATUS_SEDANG_DIPINJAM
            ]);

            DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman5->id,
                'id_barang_qr_code' => $unit5->id,
                'status_unit' => 'Diambil',
                'tanggal_diambil' => $tanggalAmbil,
                'kondisi_sebelum' => $unit5->kondisi,
            ]);

            // Jangan lupa update status asli barangnya
            $unit5->update(['status' => BarangQrCode::STATUS_DIPINJAM]);
            $usedUnitIds[] = $unit5->id;

            $this->command->info("Peminjaman Terlambat (ID: {$peminjaman5->id}) dibuat.");
        }
    }
}
