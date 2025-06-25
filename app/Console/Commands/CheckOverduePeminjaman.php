<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\User; // Pastikan ini ada
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\LogAktivitas;

// Import Notifikasi yang sudah kita buat
use App\Notifications\PeminjamanReminder;
use App\Notifications\PeminjamanOverdue;

class CheckOverduePeminjaman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peminjaman:check-status'; // Ganti ke nama yang lebih umum jika ini akan jadi satu-satunya scheduler

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for overdue, upcoming, and unpicked-up loans, updates their status, and sends notifications.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automated loan status checks...');

        // ID user 'Sistem' atau user default untuk logging otomatis
        // Anda bisa membuat user khusus 'Sistem' atau 'Bot' di database
        // Jika tidak ada user 'system', akan mencari role ADMIN pertama. Pastikan ini valid.
        $systemUser = User::where('username', 'system')->first();
        if (!$systemUser) {
            $systemUser = User::where('role', User::ROLE_ADMIN)->first(); // Fallback ke Admin pertama
            if (!$systemUser) {
                $this->error('No system user or Admin found for logging automated actions. Please create one.');
                return Command::FAILURE;
            }
        }
        $systemUserId = $systemUser->id;


        // --- Bagian 1: Memeriksa Peminjaman yang Sedang Dipinjam (untuk Notifikasi Pengingat dan Terlambat) ---
        $this->info('Checking for currently borrowed loans (reminders and overdue)...');
        $activeBorrowedPeminjamans = Peminjaman::whereIn('status', [Peminjaman::STATUS_SEDANG_DIPINJAM, Peminjaman::STATUS_TERLAMBAT])
            ->whereNotNull('tanggal_harus_kembali')
            ->whereHas('guru') // Pastikan guru pengaju ada untuk notifikasi
            ->get();

        foreach ($activeBorrowedPeminjamans as $peminjaman) {
            $guru = $peminjaman->guru; // Guru pengaju
            if (!$guru) {
                Log::warning('Skipping loan ' . $peminjaman->id . ' due to missing associated teacher for notification.');
                continue;
            }

            $dueDate = Carbon::parse($peminjaman->tanggal_harus_kembali);
            $now = Carbon::now();

            // Logika Notifikasi Pengingat Mendekati Jatuh Tempo (Reminder)
            $daysUntilDue = $now->diffInDays($dueDate, false); // false = signed difference
            // Notifikasi 3 hari sebelum
            if ($daysUntilDue == 3) {
                $guru->notify(new PeminjamanReminder($peminjaman, 3));
                $this->info('Sent 3-day reminder for loan ' . $peminjaman->id . ' to ' . $guru->username);
            }
            // Notifikasi 1 hari sebelum
            else if ($daysUntilDue == 1) {
                $guru->notify(new PeminjamanReminder($peminjaman, 1));
                $this->info('Sent 1-day reminder for loan ' . $peminjaman->id . ' to ' . $guru->username);
            }
            // Notifikasi Peminjaman Terlambat
            else if ($now->isAfter($dueDate)) {
                $daysOverdue = $now->diffInDays($dueDate); // Hitung hari keterlambatan

                DB::beginTransaction();
                try {
                    $oldStatus = $peminjaman->status; // Simpan status lama
                    $hasStatusChanged = false; // Flag untuk melacak apakah status diubah
                    $isFirstTimeOverdue = false; // Flag untuk notifikasi pertama kali terlambat

                    if ($peminjaman->status !== Peminjaman::STATUS_TERLAMBAT) {
                        $peminjaman->status = Peminjaman::STATUS_TERLAMBAT;
                        $peminjaman->pernah_terlambat = true; // Set flag
                        $peminjaman->catatan_operator = ($peminjaman->catatan_operator ? $peminjaman->catatan_operator . ' | ' : '') .
                            'Status diperbarui otomatis menjadi Terlambat pada ' . now()->isoFormat('DD-MM-YYYY HH:mm:ss');
                        $hasStatusChanged = true;
                        $isFirstTimeOverdue = true;
                    }

                    if ($peminjaman->isDirty() || $hasStatusChanged) { // Pastikan ada perubahan yang perlu disimpan
                        $peminjaman->save();
                        $this->info("Peminjaman ID: {$peminjaman->id} updated to 'Terlambat'.");
                        if ($hasStatusChanged) { // Log hanya jika status berubah
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
                        }
                    }

                    // Kirim notifikasi terlambat ke Guru pengaju (setiap hari jika masih terlambat)
                    $guru->notify(new PeminjamanOverdue($peminjaman, $daysOverdue));
                    $this->info('Sent overdue notification for loan ' . $peminjaman->id . ' to ' . $guru->username);

                    // Kirim notifikasi ke Admin/Operator juga (jika pertama kali terlambat atau setiap hari)
                    if ($isFirstTimeOverdue || $daysOverdue > 0) { // Kirim setiap hari jika sudah terlambat
                        $adminsAndOperators = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->get();
                        foreach ($adminsAndOperators as $receiver) {
                            $receiver->notify(new PeminjamanOverdue($peminjaman, $daysOverdue));
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to process overdue loan ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]);
                    $this->error("Failed to process overdue loan ID {$peminjaman->id}. Check logs for details.");
                }
            }
        }
        $this->info("Finished checking currently borrowed loans (reminders and overdue).");


        // --- Bagian 2: Memeriksa Peminjaman yang Disetujui tetapi Tidak Diambil Tepat Waktu ---
        $this->info('Checking for approved but unpicked-up loans that passed their due date...');

        // Ambil peminjaman yang berstatus 'Disetujui' (siap diambil)
        // dan tanggal_rencana_pinjam sudah lewat (ini adalah tenggat pengambilan yang lebih logis)
        $unpickedApprovedPeminjamans = Peminjaman::where('status', Peminjaman::STATUS_DISETUJUI)
            ->whereNotNull('tanggal_rencana_pinjam')
            ->whereDate('tanggal_rencana_pinjam', '<', Carbon::now())
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

                        // Opsional: Log perubahan status detail item di BarangStatus jika diinginkan
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

                    // Kirim notifikasi pembatalan ke Guru pengaju
                    if ($peminjaman->id_guru) {
                        $guruPengaju = User::find($peminjaman->id_guru);
                        if ($guruPengaju) {
                            $reason = "Dibatalkan otomatis karena tidak diambil hingga tenggat waktu.";
                            $guruPengaju->notify(new \App\Notifications\PeminjamanStatusUpdated($peminjaman, $oldStatus, $reason));
                        }
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to cancel unpicked approved loan ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]);
                $this->error("Failed to cancel unpicked approved loan ID {$peminjaman->id}. Check logs for details.");
            }
        }
        $this->info("Finished checking approved but unpicked-up loans. {$cancelledUnpickedCount} loans automatically cancelled.");


        $this->info('Automated loan status checks completed.');
        return Command::SUCCESS;
    }
}
