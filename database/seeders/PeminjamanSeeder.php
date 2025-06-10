<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\User;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeminjamanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai PeminjamanSeeder...');

        $guruUsers = User::where('role', User::ROLE_GURU)->get();
        $operatorUser = User::where('role', User::ROLE_OPERATOR)->first();
        $adminUser = User::where('role', User::ROLE_ADMIN)->first();
        $approver = $operatorUser ?? $adminUser;

        if ($guruUsers->isEmpty() || !$approver) {
            $this->command->warn('User Guru atau Admin/Operator tidak ditemukan. Seeder tidak dapat berjalan.');
            return;
        }

        $availableUnits = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereIn('kondisi', [BarangQrCode::KONDISI_BAIK, BarangQrCode::KONDISI_KURANG_BAIK])
            ->whereNull('deleted_at')
            ->inRandomOrder()->take(20)->get();

        if ($availableUnits->count() < 10) {
            $this->command->warn('Tidak cukup unit barang yang tersedia untuk PeminjamanSeeder.');
            return;
        }
        $usedUnitIds = [];

        // --- Skenario 1: Pengajuan Baru (Menunggu Persetujuan) ---
        $units1 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(3)->values();
        if ($units1->count() === 3) {
            $peminjaman1 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'tujuan_peminjaman' => 'Kegiatan Belajar Mengajar Praktikum Jaringan',
                'tanggal_pengajuan' => now()->subDays(1),
                'tanggal_rencana_pinjam' => now()->addDay(),
                'tanggal_harus_kembali' => now()->addDays(8),
            ]);
            foreach ($units1 as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman1->id,
                    'id_barang_qr_code' => $unit->id,
                    'kondisi_sebelum' => $unit->kondisi,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                ]);
                $usedUnitIds[] = $unit->id;
            }
            $peminjaman1->updateStatusPeminjaman();
            $this->command->info("Skenario 1: Peminjaman #{$peminjaman1->id} (Menunggu Persetujuan) dibuat.");
        }

        // --- Skenario 2: Persetujuan Parsial (Menunggu Finalisasi) ---
        $units2 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(3)->values();
        if ($units2->count() === 3) {
            $peminjaman2 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'tujuan_peminjaman' => 'Rapat Koordinasi Wali Murid',
                'tanggal_pengajuan' => now()->subDays(2),
                'tanggal_rencana_pinjam' => now(),
                'tanggal_harus_kembali' => now()->addDays(4),
            ]);

            DetailPeminjaman::create(['id_peminjaman' => $peminjaman2->id, 'id_barang_qr_code' => $units2[0]->id, 'kondisi_sebelum' => $units2[0]->kondisi, 'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI]);
            DetailPeminjaman::create(['id_peminjaman' => $peminjaman2->id, 'id_barang_qr_code' => $units2[1]->id, 'kondisi_sebelum' => $units2[1]->kondisi, 'status_unit' => DetailPeminjaman::STATUS_ITEM_DITOLAK, 'catatan_unit' => 'Item sedang dipelihara']);
            DetailPeminjaman::create(['id_peminjaman' => $peminjaman2->id, 'id_barang_qr_code' => $units2[2]->id, 'kondisi_sebelum' => $units2[2]->kondisi, 'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN]);

            $usedUnitIds = array_merge($usedUnitIds, $units2->pluck('id')->toArray());
            $peminjaman2->updateStatusPeminjaman();
            $this->command->info("Skenario 2: Peminjaman #{$peminjaman2->id} (Persetujuan Parsial) dibuat.");
        }

        // --- Skenario 3: Peminjaman Disetujui (Menunggu Diambil) ---
        $units3 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();
        if ($units3->count() === 2) {
            $peminjaman3 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'tujuan_peminjaman' => 'Lomba Cepat Tepat antar Kelas',
                'tanggal_pengajuan' => now()->subDays(5),
                'tanggal_rencana_pinjam' => now()->subDay(),
                'tanggal_harus_kembali' => now()->addDays(5),
                'status' => Peminjaman::STATUS_DISETUJUI,
                'disetujui_oleh' => $approver->id,
                'tanggal_disetujui' => now()->subDay(),
                'catatan_operator' => 'Semua barang tersedia, silakan diambil.',
            ]);
            foreach ($units3 as $unit) {
                DetailPeminjaman::create(['id_peminjaman' => $peminjaman3->id, 'id_barang_qr_code' => $unit->id, 'kondisi_sebelum' => $unit->kondisi, 'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI]);
                $usedUnitIds[] = $unit->id;
            }
            $this->command->info("Skenario 3: Peminjaman #{$peminjaman3->id} (Disetujui) dibuat.");
        }

        // --- Skenario 4: Peminjaman Sedang Dipinjam ---
        $units4 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();
        if ($units4->count() === 2) {
            $peminjaman4 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'tujuan_peminjaman' => 'Pelatihan Internal Guru',
                'tanggal_pengajuan' => now()->subDays(10),
                'tanggal_rencana_pinjam' => now()->subDays(7),
                'tanggal_harus_kembali' => now()->addDays(2),
                'status' => Peminjaman::STATUS_SEDANG_DIPINJAM,
                'disetujui_oleh' => $approver->id,
                'tanggal_disetujui' => now()->subDays(8),
            ]);

            $detail4_1 = DetailPeminjaman::create(['id_peminjaman' => $peminjaman4->id, 'id_barang_qr_code' => $units4[0]->id, 'kondisi_sebelum' => $units4[0]->kondisi, 'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI]);
            $detail4_1->konfirmasiPengambilan($approver->id);

            DetailPeminjaman::create(['id_peminjaman' => $peminjaman4->id, 'id_barang_qr_code' => $units4[1]->id, 'kondisi_sebelum' => $units4[1]->kondisi, 'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI]);

            $usedUnitIds = array_merge($usedUnitIds, $units4->pluck('id')->toArray());
            $peminjaman4->updateStatusPeminjaman();
            $this->command->info("Skenario 4: Peminjaman #{$peminjaman4->id} (Sedang Dipinjam) dibuat.");
        }

        // --- Skenario 5: Peminjaman Terlambat ---
        $units5 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(1)->values();
        if ($units5->count() === 1) {
            $peminjaman5 = Peminjaman::create([
                'id_guru' => $guruUsers->random()->id,
                'tujuan_peminjaman' => 'Kegiatan OSIS bulan lalu',
                'tanggal_pengajuan' => now()->subDays(20),
                'tanggal_rencana_pinjam' => now()->subDays(15),
                'tanggal_harus_kembali' => now()->subDays(5),
                'status' => Peminjaman::STATUS_TERLAMBAT,
                'disetujui_oleh' => $approver->id,
                'tanggal_disetujui' => now()->subDays(18),
            ]);

            $detail5_1 = DetailPeminjaman::create(['id_peminjaman' => $peminjaman5->id, 'id_barang_qr_code' => $units5[0]->id, 'kondisi_sebelum' => $units5[0]->kondisi, 'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI]);
            $detail5_1->konfirmasiPengambilan($approver->id);

            $usedUnitIds = array_merge($usedUnitIds, $units5->pluck('id')->toArray());
            $peminjaman5->updateStatusPeminjaman();
            $this->command->info("Skenario 5: Peminjaman #{$peminjaman5->id} (Terlambat) dibuat.");
        }

        $this->command->info('PeminjamanSeeder selesai.');
    }
}
