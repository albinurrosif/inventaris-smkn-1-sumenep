<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use App\Models\User; // Ditambahkan
use App\Notifications\PeminjamanReminder; // Ditambahkan
use App\Notifications\PeminjamanOverdue; // Ditambahkan
use Illuminate\Console\Command;
use Illuminate\Support\Carbon; // Ditambahkan
use Illuminate\Support\Facades\Log; // Ditambahkan

class CheckPeminjamanStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peminjaman:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for upcoming and overdue loans and sends notifications.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking loan statuses...');

        // Ambil peminjaman yang sedang 'Sedang Dipinjam' dan belum 'Selesai'
        $activeLoans = Peminjaman::where('status', Peminjaman::STATUS_SEDANG_DIPINJAM)
            ->whereNotNull('tanggal_harus_kembali')
            ->whereHas('guru') // Pastikan guru pengaju ada
            ->get();

        foreach ($activeLoans as $peminjaman) {
            $guru = $peminjaman->guru;
            if (!$guru) {
                Log::warning('Skipping loan ' . $peminjaman->id . ' due to missing associated teacher.');
                continue;
            }

            $dueDate = Carbon::parse($peminjaman->tanggal_harus_kembali);
            $now = Carbon::now();

            // Cek untuk peminjaman yang mendekati jatuh tempo (misal, 1 atau 3 hari lagi)
            $daysUntilDue = $now->diffInDays($dueDate, false); // false = signed difference

            // Pengingat 3 hari sebelum jatuh tempo
            if ($daysUntilDue == 3) {
                // Logika untuk mengirim notifikasi pengingat
                $guru->notify(new PeminjamanReminder($peminjaman, 3));
                $this->info('Sent 3-day reminder for loan ' . $peminjaman->id);
            }
            // Pengingat 1 hari sebelum jatuh tempo
            else if ($daysUntilDue == 1) {
                // Logika untuk mengirim notifikasi pengingat
                $guru->notify(new PeminjamanReminder($peminjaman, 1));
                $this->info('Sent 1-day reminder for loan ' . $peminjaman->id);
            }
            // Notifikasi peminjaman terlambat
            else if ($now->isAfter($dueDate)) {
                // Hitung berapa hari terlambat
                $daysOverdue = $now->diffInDays($dueDate);

                // Kirim notifikasi terlambat, hanya jika statusnya belum 'Terlambat'
                // atau jika sudah 'Terlambat' tapi notifikasi terakhir sudah lama (misal, seminggu sekali)
                // Untuk kesederhanaan awal, kita ubah status menjadi 'Terlambat'
                // dan kirim notifikasi setiap hari jika masih terlambat.

                if ($peminjaman->status !== Peminjaman::STATUS_TERLAMBAT) {
                    $oldStatus = $peminjaman->status; // Simpan status lama
                    $peminjaman->status = Peminjaman::STATUS_TERLAMBAT;
                    $peminjaman->pernah_terlambat = true; // Set flag pernah_terlambat
                    $peminjaman->catatan_operator = ($peminjaman->catatan_operator ? $peminjaman->catatan_operator . ' | ' : '') .
                        'Status diperbarui otomatis menjadi Terlambat pada ' . now()->isoFormat('DD-MM-YYYY HH:mm:ss');
                    $peminjaman->save();

                    // Kirim notifikasi pertama kali terlambat
                    $guru->notify(new PeminjamanOverdue($peminjaman, $daysOverdue));
                    $this->info('Loan ' . $peminjaman->id . ' is now overdue. Notified ' . $guru->username);

                    // Opsional: Kirim notifikasi juga ke Admin/Operator jika peminjaman terlambat
                    $adminsAndOperators = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->get();
                    foreach ($adminsAndOperators as $receiver) {
                        $receiver->notify(new PeminjamanOverdue($peminjaman, $daysOverdue));
                    }
                } else {
                    // Jika sudah terlambat dan notifikasi terakhir dikirim lebih dari 24 jam yang lalu (atau setiap X hari)
                    // Untuk kesederhanaan, kita bisa kirim setiap hari jika statusnya masih terlambat.
                    // Atau bisa juga tambahkan kolom last_overdue_notification_sent_at di peminjamen.
                    $guru->notify(new PeminjamanOverdue($peminjaman, $daysOverdue));
                    $this->info('Sent daily overdue reminder for loan ' . $peminjaman->id);
                }
            }
        }

        $this->info('Loan status check completed.');
    }
}
