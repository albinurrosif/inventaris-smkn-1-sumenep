<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\User;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Untuk transaksi jika diperlukan

class PeminjamanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai PeminjamanSeeder...');

        // Ambil data pengguna
        $guruUsers = User::where('role', User::ROLE_GURU)->get();
        $operatorUsers = User::where('role', User::ROLE_OPERATOR)->get();
        $adminUsers = User::where('role', User::ROLE_ADMIN)->get();

        if ($guruUsers->isEmpty()) {
            $this->command->warn('Tidak ada User Guru ditemukan. Jalankan UserSeeder terlebih dahulu.');
            return;
        }
        if ($operatorUsers->isEmpty() && $adminUsers->isEmpty()) {
            $this->command->warn('Tidak ada User Operator atau Admin ditemukan untuk proses persetujuan/penolakan. Jalankan UserSeeder.');
            // Bisa dilanjutkan jika hanya ingin membuat pengajuan menunggu.
        }

        // Ambil ruangan untuk tujuan peminjaman
        $ruanganTujuan = Ruangan::inRandomOrder()->first();
        if (!$ruanganTujuan) {
            $this->command->warn('Tidak ada Ruangan ditemukan untuk tujuan peminjaman. Jalankan RuanganSeeder.');
            // return; // Bisa dilanjutkan tanpa ruangan tujuan jika opsional
        }

        // Ambil beberapa unit barang yang tersedia untuk dipinjam
        $availableUnits = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereIn('kondisi', [BarangQrCode::KONDISI_BAIK, BarangQrCode::KONDISI_KURANG_BAIK])
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->take(15) // Ambil lebih banyak untuk variasi
            ->get();

        if ($availableUnits->count() < 5) { // Perlu beberapa unit untuk variasi
            $this->command->warn('Tidak cukup unit BarangQrCode yang tersedia/valid untuk PeminjamanSeeder. Jalankan BarangQrCodeSeeder.');
            return;
        }

        $usedUnitIds = []; // Untuk melacak unit yang sudah digunakan dalam seeder ini

        // --- Skenario 1: Peminjaman Menunggu Persetujuan ---
        if ($guruUsers->count() >= 1 && $availableUnits->count() >= 2) {
            $guru1 = $guruUsers->get(0);
            // PASTIKAN MENGGUNAKAN ->values() SETELAH TAKE
            $unitsToBorrow1 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();
            if ($unitsToBorrow1->count() == 2) {
                $peminjaman1 = Peminjaman::create([
                    'id_guru' => $guru1->id,
                    'tujuan_peminjaman' => 'Persiapan Mengajar Kelas XII RPL Semester Genap',
                    'tanggal_pengajuan' => Carbon::now()->subDays(rand(3, 7)),
                    'tanggal_rencana_pinjam' => Carbon::now()->subDays(rand(0, 2)),
                    'tanggal_harus_kembali' => Carbon::now()->addDays(rand(5, 10)),
                    'status' => Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                    'id_ruangan_tujuan_peminjaman' => $ruanganTujuan?->id,
                    'catatan_peminjam' => 'Mohon bantuan untuk approvalnya segera.',
                ]);

                foreach ($unitsToBorrow1 as $unit) {
                    if ($unit) { // Tambahan pengecekan jika unit null
                        DetailPeminjaman::create([
                            'id_peminjaman' => $peminjaman1->id,
                            'id_barang_qr_code' => $unit->id,
                            'kondisi_sebelum' => $unit->kondisi,
                            'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                        ]);
                        $usedUnitIds[] = $unit->id;
                    }
                }
                $peminjaman1->updateStatusPeminjaman();
                $this->command->info("Peminjaman Menunggu Persetujuan (ID: {$peminjaman1->id}) dibuat.");
            }
        }

        // --- Skenario 2: Peminjaman Disetujui, beberapa item sudah diambil ---
        $approver = $operatorUsers->first() ?? $adminUsers->first();
        if ($guruUsers->count() >= 2 && $availableUnits->whereNotIn('id', $usedUnitIds)->count() >= 3 && $approver) {
            $guru2 = $guruUsers->get(1);
            // PASTIKAN MENGGUNAKAN ->values() SETELAH TAKE
            $unitsToBorrow2 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(3)->values();

            if ($unitsToBorrow2->count() == 3) {
                $peminjaman2 = Peminjaman::create([
                    'id_guru' => $guru2->id,
                    'tujuan_peminjaman' => 'Workshop Kurikulum Merdeka tingkat sekolah',
                    'tanggal_pengajuan' => Carbon::now()->subDays(rand(8, 12)),
                    'tanggal_disetujui' => Carbon::now()->subDays(rand(6, 7)),
                    'disetujui_oleh' => $approver->id,
                    'tanggal_rencana_pinjam' => Carbon::now()->subDays(rand(4, 5)),
                    'tanggal_harus_kembali' => Carbon::now()->addDays(rand(3, 6)),
                    'status' => Peminjaman::STATUS_DISETUJUI, // Akan diupdate oleh detail
                    'catatan_operator' => 'Silakan diambil sesuai jadwal.',
                ]);

                // Item 1: Diambil
                if ($unitsToBorrow2->get(0)) {
                    $detail2_1 = DetailPeminjaman::create([
                        'id_peminjaman' => $peminjaman2->id,
                        'id_barang_qr_code' => $unitsToBorrow2->get(0)->id,
                        'kondisi_sebelum' => $unitsToBorrow2->get(0)->kondisi,
                        'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI,
                    ]);
                    $detail2_1->konfirmasiPengambilan($approver->id);
                    $usedUnitIds[] = $unitsToBorrow2->get(0)->id;
                }

                // Item 2: Masih Disetujui (belum diambil)
                if ($unitsToBorrow2->get(1)) {
                    DetailPeminjaman::create([
                        'id_peminjaman' => $peminjaman2->id,
                        'id_barang_qr_code' => $unitsToBorrow2->get(1)->id,
                        'kondisi_sebelum' => $unitsToBorrow2->get(1)->kondisi,
                        'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI,
                    ]);
                    $usedUnitIds[] = $unitsToBorrow2->get(1)->id;
                }

                // Item 3: Diambil lalu dikembalikan dengan kondisi baik
                if ($unitsToBorrow2->get(2)) {
                    $detail2_3 = DetailPeminjaman::create([
                        'id_peminjaman' => $peminjaman2->id,
                        'id_barang_qr_code' => $unitsToBorrow2->get(2)->id,
                        'kondisi_sebelum' => $unitsToBorrow2->get(2)->kondisi,
                        'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI,
                    ]);
                    $detail2_3->konfirmasiPengambilan($approver->id);
                    $detail2_3->verifikasiPengembalian($approver->id, BarangQrCode::KONDISI_BAIK, 'Dikembalikan tepat waktu.');
                    $usedUnitIds[] = $unitsToBorrow2->get(2)->id;
                }

                $peminjaman2->updateStatusPeminjaman();
                $this->command->info("Peminjaman Disetujui/Sebagian Diambil (ID: {$peminjaman2->id}) dibuat.");
            }
        }

        // --- Skenario 3: Peminjaman Selesai (Semua item dikembalikan) ---
        if ($guruUsers->count() >= 1 && $availableUnits->whereNotIn('id', $usedUnitIds)->count() >= 2 && $adminUsers->first()) {
            $guru3 = $guruUsers->get(0);
            // PASTIKAN MENGGUNAKAN ->values() SETELAH TAKE
            $unitsToBorrow3 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(2)->values();

            if ($unitsToBorrow3->count() == 2) {
                $peminjaman3 = Peminjaman::create([
                    'id_guru' => $guru3->id,
                    'tujuan_peminjaman' => 'Kegiatan Ekstrakurikuler Fotografi',
                    'tanggal_pengajuan' => Carbon::now()->subDays(rand(15, 20)),
                    'tanggal_disetujui' => Carbon::now()->subDays(rand(13, 14)),
                    'disetujui_oleh' => $adminUsers->first()->id,
                    'tanggal_semua_diambil' => Carbon::now()->subDays(rand(11, 12)),
                    'tanggal_selesai' => Carbon::now()->subDays(rand(1, 3)),
                    'tanggal_rencana_pinjam' => Carbon::now()->subDays(rand(11, 12)),
                    'tanggal_harus_kembali' => Carbon::now()->subDays(rand(4, 6)),
                    'status' => Peminjaman::STATUS_SELESAI,
                ]);

                foreach ($unitsToBorrow3 as $key => $unit) {
                    if ($unit) { // Tambahan pengecekan
                        $kondisiSetelah = ($key % 2 == 0) ? BarangQrCode::KONDISI_BAIK : BarangQrCode::KONDISI_KURANG_BAIK;
                        $catatanUnit = ($key % 2 == 0) ? 'Dikembalikan dalam kondisi baik.' : 'Ada sedikit lecet pada casing.';

                        DetailPeminjaman::create([
                            'id_peminjaman' => $peminjaman3->id,
                            'id_barang_qr_code' => $unit->id,
                            'kondisi_sebelum' => $unit->kondisi,
                            'kondisi_setelah' => $kondisiSetelah,
                            'tanggal_diambil' => $peminjaman3->tanggal_semua_diambil,
                            'tanggal_dikembalikan' => $peminjaman3->tanggal_selesai,
                            'status_unit' => DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN,
                            'catatan_unit' => $catatanUnit,
                        ]);

                        $unit->status = BarangQrCode::STATUS_TERSEDIA;
                        $unit->kondisi = $kondisiSetelah;
                        $unit->save();
                        $usedUnitIds[] = $unit->id;
                    }
                }
                $peminjaman3->updateStatusPeminjaman();
                $this->command->info("Peminjaman Selesai (ID: {$peminjaman3->id}) dibuat.");
            }
        }

        // --- Skenario 4: Peminjaman Ditolak ---
        if ($guruUsers->count() >= 2 && $availableUnits->whereNotIn('id', $usedUnitIds)->count() >= 1 && ($operatorUsers->first() || $adminUsers->first())) {
            $guru4 = $guruUsers->get(1);
            // PASTIKAN MENGGUNAKAN ->values() SETELAH TAKE (meskipun hanya 1 item)
            $unitToBorrow4 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(1)->values()->first();
            $penolak = $operatorUsers->first() ?? $adminUsers->first();

            if ($unitToBorrow4 && $penolak) {
                $peminjaman4 = Peminjaman::create([
                    'id_guru' => $guru4->id,
                    'tujuan_peminjaman' => 'Uji coba proyektor baru untuk presentasi',
                    'tanggal_pengajuan' => Carbon::now()->subDays(rand(2, 4)),
                    'tanggal_rencana_pinjam' => Carbon::now()->subDays(rand(0, 1)),
                    'tanggal_harus_kembali' => Carbon::now()->addDays(rand(1, 3)),
                    'status' => Peminjaman::STATUS_DITOLAK,
                    'ditolak_oleh' => $penolak->id,
                    'tanggal_ditolak' => Carbon::now()->subDays(rand(0, 1)),
                    'catatan_operator' => 'Barang sedang dalam jadwal pemeliharaan rutin minggu ini.',
                ]);
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman4->id,
                    'id_barang_qr_code' => $unitToBorrow4->id,
                    'kondisi_sebelum' => $unitToBorrow4->kondisi,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                ]);
                $usedUnitIds[] = $unitToBorrow4->id;
                $peminjaman4->updateStatusPeminjaman();
                $this->command->info("Peminjaman Ditolak (ID: {$peminjaman4->id}) dibuat.");
            }
        }

        // --- Skenario 5: Peminjaman Sedang Dipinjam (Terlambat) ---
        if ($guruUsers->count() >= 1 && $availableUnits->whereNotIn('id', $usedUnitIds)->count() >= 1 && $approver) {
            $guru5 = $guruUsers->first();
            // PASTIKAN MENGGUNAKAN ->values() SETELAH TAKE
            $unitToBorrow5 = $availableUnits->whereNotIn('id', $usedUnitIds)->take(1)->values()->first();

            if ($unitToBorrow5 && $approver) {
                $tanggalHarusKembali = Carbon::now()->subDays(rand(2, 3));
                $tanggalAmbil = Carbon::now()->subDays(rand(5, 7));

                $peminjaman5 = Peminjaman::create([
                    'id_guru' => $guru5->id,
                    'tujuan_peminjaman' => 'Penggunaan untuk kegiatan OSIS',
                    'tanggal_pengajuan' => $tanggalAmbil->copy()->subDays(rand(1, 2)),
                    'tanggal_disetujui' => $tanggalAmbil->copy()->subDay(),
                    'disetujui_oleh' => $approver->id,
                    'tanggal_rencana_pinjam' => $tanggalAmbil,
                    'tanggal_harus_kembali' => $tanggalHarusKembali,
                    'status' => Peminjaman::STATUS_SEDANG_DIPINJAM,
                ]);

                $detail5_1 = DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman5->id,
                    'id_barang_qr_code' => $unitToBorrow5->id,
                    'kondisi_sebelum' => $unitToBorrow5->kondisi,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI,
                ]);
                $detail5_1->konfirmasiPengambilan($approver->id);
                $usedUnitIds[] = $unitToBorrow5->id;

                $peminjaman5->updateStatusPeminjaman();
                $this->command->info("Peminjaman Terlambat (ID: {$peminjaman5->id}) dibuat.");
            }
        }

        $this->command->info('PeminjamanSeeder selesai.');
    }
}
