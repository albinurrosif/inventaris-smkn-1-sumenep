<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\User;
use App\Models\BarangQrCode;
use Carbon\Carbon;

class PeminjamanSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk membuat data peminjaman yang bervariasi.
     *
     * @return void
     */
    public function run(): void
    {
        // Hapus data lama untuk menghindari duplikasi saat seeder dijalankan ulang
        DetailPeminjaman::query()->forceDelete();
        Peminjaman::query()->forceDelete();

        // Ambil user yang diperlukan dari database
        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $admin = User::where('role', 'Admin')->first();
        $operator = User::where('role', 'Operator')->first();

        // Jika user tidak ditemukan, hentikan seeder
        if (!$guru1 || !$guru2 || !$admin || !$operator) {
            $this->command->error('Pastikan user dengan peran Admin, Operator, dan minimal 2 Guru (guru1, guru2) ada di database.');
            return;
        }

        // Ambil beberapa barang yang tersedia untuk dijadikan sampel
        $availableUnits = BarangQrCode::where('status', 'Tersedia')
            ->whereNotIn('kondisi', ['Rusak Berat', 'Hilang'])
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->limit(30) // Ambil 30 barang untuk variasi
            ->get();

        if ($availableUnits->count() < 20) {
            $this->command->warn('Tidak cukup barang tersedia (butuh minimal 20) untuk menjalankan PeminjamanSeeder secara penuh.');
            return;
        }

        $usedUnitIds = [];

        // --- SKENARIO 1: PEMINJAMAN SELESAI (Tepat Waktu) ---
        $this->command->info('Membuat Peminjaman Selesai...');
        $units1 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();
        if ($units1->count() === 2) {
            $peminjaman1 = Peminjaman::create([
                'id_guru' => $guru1->id,
                'tujuan_peminjaman' => 'Kegiatan Belajar Mengajar Praktikum (Selesai)',
                'tanggal_pengajuan' => now()->subDays(15),
                'tanggal_rencana_pinjam' => now()->subDays(14),
                'tanggal_harus_kembali' => now()->subDays(7),
                'status' => Peminjaman::STATUS_SELESAI,
                'disetujui_oleh' => $operator->id,
                'tanggal_disetujui' => now()->subDays(14),
                'tanggal_semua_diambil' => now()->subDays(13),
                'tanggal_selesai' => now()->subDays(7),
                'catatan_operator' => 'Disetujui untuk KBM.',
            ]);
            foreach ($units1 as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman1->id,
                    'id_barang_qr_code' => $unit->id,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN,
                    'kondisi_sebelum' => 'Baik',
                    'kondisi_setelah' => 'Baik',
                    'tanggal_diambil' => now()->subDays(13),
                    'tanggal_dikembalikan' => now()->subDays(7),
                ]);
                $usedUnitIds[] = $unit->id;
            }
        }

        // --- SKENARIO 2: PEMINJAMAN TERLAMBAT (tapi Sudah Selesai) ---
        $this->command->info('Membuat Peminjaman Terlambat (tapi sudah selesai)...');
        $units2 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(1)->values();
        if ($units2->count() === 1) {
            $peminjaman2 = Peminjaman::create([
                'id_guru' => $guru2->id,
                'tujuan_peminjaman' => 'Lomba Internal Sekolah (Selesai Terlambat)',
                'tanggal_pengajuan' => now()->subDays(10),
                'tanggal_rencana_pinjam' => now()->subDays(9),
                'tanggal_harus_kembali' => now()->subDays(5),
                'status' => Peminjaman::STATUS_SELESAI,
                'disetujui_oleh' => $admin->id,
                'tanggal_disetujui' => now()->subDays(9),
                'tanggal_semua_diambil' => now()->subDays(8),
                'tanggal_selesai' => now()->subDays(2),
                'pernah_terlambat' => true,
                'catatan_operator' => 'Pengembalian terlambat 3 hari.',
            ]);
            foreach ($units2 as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman2->id,
                    'id_barang_qr_code' => $unit->id,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN,
                    'kondisi_sebelum' => 'Baik',
                    'kondisi_setelah' => 'Kurang Baik',
                    'tanggal_diambil' => now()->subDays(8),
                    'tanggal_dikembalikan' => now()->subDays(2),
                ]);
                $usedUnitIds[] = $unit->id;
            }
        }

        // --- SKENARIO 3: SEDANG DIPINJAM & TERLAMBAT ---
        $this->command->info('Membuat Peminjaman Sedang Dipinjam & TERLAMBAT...');
        $unitsLate = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();
        if ($unitsLate->count() === 2) {
            $peminjamanLate = Peminjaman::create([
                'id_guru' => $guru2->id,
                'tujuan_peminjaman' => 'Kegiatan Ekstrakurikuler (TERLAMBAT AKTIF)',
                'tanggal_pengajuan' => now()->subDays(10),
                'tanggal_rencana_pinjam' => now()->subDays(9),
                'tanggal_harus_kembali' => now()->subDays(3), // <-- KUNCI: Tanggal kembali sudah lewat
                'status' => Peminjaman::STATUS_TERLAMBAT,     // <-- KUNCI: Status sudah TERLAMBAT
                'pernah_terlambat' => true,
                'disetujui_oleh' => $operator->id,
                'tanggal_disetujui' => now()->subDays(9),
                'tanggal_semua_diambil' => now()->subDays(8),
                'tanggal_selesai' => null, // <-- KUNCI: Belum selesai
            ]);
            foreach ($unitsLate as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjamanLate->id,
                    'id_barang_qr_code' => $unit->id,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAMBIL,
                    'kondisi_sebelum' => $unit->kondisi,
                    'tanggal_diambil' => now()->subDays(8),
                ]);
                $unit->update(['status' => 'Dipinjam']); // Update status barangnya
                $usedUnitIds[] = $unit->id;
            }
        }

        // --- SKENARIO 4: SEDANG DIPINJAM (Tepat Waktu) ---
        $this->command->info('Membuat Peminjaman Sedang Dipinjam (Tepat Waktu)...');
        $units4 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(3)->values();
        if ($units4->count() === 3) {
            $peminjaman4 = Peminjaman::create([
                'id_guru' => $guru1->id,
                'tujuan_peminjaman' => 'Presentasi Rapat Dinas',
                'tanggal_pengajuan' => now()->subDays(2),
                'tanggal_rencana_pinjam' => now()->subDay(),
                'tanggal_harus_kembali' => now()->addDays(5), // <-- KUNCI: Masih ada waktu
                'status' => Peminjaman::STATUS_SEDANG_DIPINJAM,
                'disetujui_oleh' => $operator->id,
                'tanggal_disetujui' => now()->subDay(),
                'tanggal_semua_diambil' => now()->subDay(),
            ]);
            foreach ($units4 as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman4->id,
                    'id_barang_qr_code' => $unit->id,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAMBIL,
                    'kondisi_sebelum' => $unit->kondisi,
                    'tanggal_diambil' => now()->subDay(),
                ]);
                $unit->update(['status' => 'Dipinjam']);
                $usedUnitIds[] = $unit->id;
            }
        }

        // --- SKENARIO 5: DISETUJUI (Barang siap diambil) ---
        $this->command->info('Membuat Peminjaman Disetujui (siap diambil)...');
        $units5 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();
        if ($units5->count() === 2) {
            $peminjaman5 = Peminjaman::create([
                'id_guru' => $guru2->id,
                'tujuan_peminjaman' => 'Workshop Fotografi',
                'tanggal_pengajuan' => now()->subDay(),
                'tanggal_rencana_pinjam' => now(),
                'tanggal_harus_kembali' => now()->addDays(3),
                'status' => Peminjaman::STATUS_DISETUJUI,
                'disetujui_oleh' => $admin->id,
                'tanggal_disetujui' => now(),
            ]);
            foreach ($units5 as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman5->id,
                    'id_barang_qr_code' => $unit->id,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI,
                    'kondisi_sebelum' => $unit->kondisi,
                ]);
                $usedUnitIds[] = $unit->id;
            }
        }

        // --- SKENARIO 6: MENUNGGU PERSETUJUAN ---
        $this->command->info('Membuat Peminjaman Menunggu Persetujuan...');
        $units6 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();
        if ($units6->count() === 2) {
            $peminjaman6 = Peminjaman::create([
                'id_guru' => $guru1->id,
                'tujuan_peminjaman' => 'Ujian Praktik Kejuruan',
                'tanggal_pengajuan' => now(),
                'tanggal_rencana_pinjam' => now()->addDay(),
                'tanggal_harus_kembali' => now()->addDays(4),
                'status' => Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
            ]);
            foreach ($units6 as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman6->id,
                    'id_barang_qr_code' => $unit->id,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                    'kondisi_sebelum' => $unit->kondisi,
                ]);
                $usedUnitIds[] = $unit->id;
            }
        }

        // --- SKENARIO 7: DITOLAK ---
        $this->command->info('Membuat Peminjaman Ditolak...');
        $units7 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(1)->values();
        if ($units7->count() === 1) {
            $peminjaman7 = Peminjaman::create([
                'id_guru' => $guru2->id,
                'tujuan_peminjaman' => 'Acara Pribadi',
                'tanggal_pengajuan' => now()->subDays(3),
                'tanggal_rencana_pinjam' => now()->subDays(2),
                'tanggal_harus_kembali' => now()->subDay(),
                'status' => Peminjaman::STATUS_DITOLAK,
                'ditolak_oleh' => $admin->id,
                'tanggal_ditolak' => now()->subDays(2),
                'catatan_operator' => 'Tidak sesuai dengan kebijakan peminjaman sekolah.',
            ]);
            foreach ($units7 as $unit) {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman7->id,
                    'id_barang_qr_code' => $unit->id,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DITOLAK,
                    'catatan_unit' => 'Tidak diizinkan untuk acara pribadi.',
                    'kondisi_sebelum' => $unit->kondisi,
                ]);
                $usedUnitIds[] = $unit->id;
            }
        }

        $this->command->info('PeminjamanSeeder selesai dijalankan.');
    }
}
