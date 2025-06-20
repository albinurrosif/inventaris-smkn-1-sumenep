<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman; // Tambahkan ini
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\LogAktivitas; // Tambahkan ini
use App\Models\User; // Tambahkan ini jika LogAktivitas memerlukan id_user yang valid

class CheckOverduePeminjaman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:overdue-peminjaman';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for overdue loans and updates their status, and cancels unpicked-up approved loans.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting overdue peminjaman check...');

        // ID user 'Sistem' atau user default untuk logging otomatis
        // Anda bisa membuat user khusus 'Sistem' atau 'Bot' di database
        $systemUser = User::where('username', 'system')->first() ?? User::where('role', User::ROLE_ADMIN)->first();
        $systemUserId = $systemUser ? $systemUser->id : null;

        // --- Bagian 1: Memeriksa Peminjaman yang Sedang Dipinjam dan Terlambat ---
        $this->info('Checking for currently borrowed and overdue loans...');
        $overdueBorrowedPeminjamans = Peminjaman::where('status', Peminjaman::STATUS_SEDANG_DIPINJAM)
            ->whereNotNull('tanggal_harus_kembali')
            ->whereDate('tanggal_harus_kembali', '<', Carbon::now())
            ->get();

        $updatedOverdueCount = 0;
        foreach ($overdueBorrowedPeminjamans as $peminjaman) {
            DB::beginTransaction();
            try {
                // Periksa apakah masih ada item yang berstatus Diambil atau Rusak Saat Dipinjam
                // Ini memastikan peminjaman masih 'aktif' dan belum semua dikembalikan
                $stillActiveBorrowedItems = $peminjaman->detailPeminjaman()
                    ->whereIn('status_unit', [
                        DetailPeminjaman::STATUS_ITEM_DIAMBIL,
                        DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM
                    ])
                    ->exists();

                if ($stillActiveBorrowedItems) {
                    $oldStatus = $peminjaman->status; // Simpan status lama untuk log
                    $peminjaman->status = Peminjaman::STATUS_TERLAMBAT;
                    $peminjaman->catatan_operator = ($peminjaman->catatan_operator ? $peminjaman->catatan_operator . ' | ' : '') . 'Status diperbarui otomatis menjadi Terlambat pada ' . now()->format('d-m-Y H:i:s');
                    $peminjaman->save();
                    $updatedOverdueCount++;

                    LogAktivitas::create([
                        'id_user' => $systemUserId,
                        'aktivitas' => 'Update Status Peminjaman Otomatis',
                        'deskripsi' => "Peminjaman ID {$peminjaman->id} ({$peminjaman->tujuan_peminjaman}) berubah status menjadi Terlambat secara otomatis.",
                        'model_terkait' => Peminjaman::class,
                        'id_model_terkait' => $peminjaman->id,
                        'data_lama' => json_encode(['status' => $oldStatus]),
                        'data_baru' => json_encode(['status' => Peminjaman::STATUS_TERLAMBAT]),
                        'ip_address' => 'CLI',
                        'user_agent' => 'Laravel Scheduler',
                    ]);
                    $this->info("Peminjaman ID: {$peminjaman->id} updated to 'Terlambat'.");
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to update overdue loan ID {$peminjaman->id}: " . $e->getMessage());
                $this->error("Failed to update overdue loan ID {$peminjaman->id}. Check logs for details.");
            }
        }
        $this->info("Finished checking currently borrowed and overdue loans. {$updatedOverdueCount} loans updated to 'Terlambat'.");

        // --- Bagian 2: Memeriksa Peminjaman yang Disetujui tetapi Tidak Diambil Tepat Waktu ---
        $this->info('Checking for approved but unpicked-up loans that passed their due date...');

        // Ambil peminjaman yang berstatus 'Disetujui' (siap diambil)
        // dan tanggal_harus_kembali (atau tanggal_rencana_pinjam jika ini tenggat pickup) sudah lewat
        $unpickedApprovedPeminjamans = Peminjaman::where('status', Peminjaman::STATUS_DISETUJUI)
            ->whereNotNull('tanggal_harus_kembali') // Menggunakan tanggal_harus_kembali sebagai tenggat pengambilan
            ->whereDate('tanggal_harus_kembali', '<', Carbon::now())
            ->get();

        $cancelledUnpickedCount = 0;
        foreach ($unpickedApprovedPeminjamans as $peminjaman) {
            DB::beginTransaction();
            try {
                // Pastikan tidak ada satupun item yang sudah diambil dari peminjaman ini
                $anyItemPickedUp = $peminjaman->detailPeminjaman()
                    ->where('status_unit', DetailPeminjaman::STATUS_ITEM_DIAMBIL)
                    ->exists();

                if (!$anyItemPickedUp) {
                    $oldStatus = $peminjaman->status; // Simpan status lama untuk log

                    // Ubah status peminjaman induk menjadi Dibatalkan
                    $peminjaman->status = Peminjaman::STATUS_DIBATALKAN;
                    $peminjaman->ditolak_oleh = $systemUserId; // Ditentukan oleh sistem
                    $peminjaman->tanggal_ditolak = now();
                    $peminjaman->catatan_operator = ($peminjaman->catatan_operator ? $peminjaman->catatan_operator . ' | ' : '') . 'Dibatalkan otomatis karena tidak diambil hingga tenggat waktu pada ' . now()->format('d-m-Y H:i:s');
                    $peminjaman->save();
                    $cancelledUnpickedCount++;

                    // Ubah status semua detail peminjaman yang masih 'Disetujui' menjadi 'Ditolak'
                    foreach ($peminjaman->detailPeminjaman()->where('status_unit', DetailPeminjaman::STATUS_ITEM_DISETUJUI)->get() as $detail) {
                        $oldDetailStatus = $detail->status_unit; // Simpan status detail lama
                        $detail->status_unit = DetailPeminjaman::STATUS_ITEM_DITOLAK;
                        $detail->catatan_unit = ($detail->catatan_unit ? $detail->catatan_unit . ' | ' : '') . 'Ditolak otomatis: peminjaman tidak diambil hingga tenggat.';
                        $detail->save();

                        // Opsional: Log perubahan status detail item
                        // BarangStatus::create()
                    }

                    LogAktivitas::create([
                        'id_user' => $systemUserId,
                        'aktivitas' => 'Pembatalan Peminjaman Otomatis',
                        'deskripsi' => "Peminjaman ID {$peminjaman->id} ({$peminjaman->tujuan_peminjaman}) dibatalkan secara otomatis karena tidak diambil hingga tenggat waktu.",
                        'model_terkait' => Peminjaman::class,
                        'id_model_terkait' => $peminjaman->id,
                        'data_lama' => json_encode(['status' => $oldStatus]),
                        'data_baru' => json_encode(['status' => Peminjaman::STATUS_DIBATALKAN]),
                        'ip_address' => 'CLI',
                        'user_agent' => 'Laravel Scheduler',
                    ]);
                    $this->info("Peminjaman ID: {$peminjaman->id} (Approved but Unpicked) cancelled automatically.");
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to cancel unpicked approved loan ID {$peminjaman->id}: " . $e->getMessage());
                $this->error("Failed to cancel unpicked approved loan ID {$peminjaman->id}. Check logs for details.");
            }
        }
        $this->info("Finished checking approved but unpicked-up loans. {$cancelledUnpickedCount} loans automatically cancelled.");


        $this->info('Overdue peminjaman check finished.');
        return Command::SUCCESS;
    }
}
