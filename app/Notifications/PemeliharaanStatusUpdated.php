<?php

namespace App\Notifications;

use App\Models\Pemeliharaan; // Import model Pemeliharaan
use App\Models\User; // Import model User
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PemeliharaanStatusUpdated extends Notification
{
    use Queueable;

    protected $pemeliharaan;
    protected $oldStatusPengajuan;
    protected $oldStatusPengerjaan;
    protected $reason; // Alasan perubahan, opsional

    /**
     * Create a new notification instance.
     */
    public function __construct(Pemeliharaan $pemeliharaan, ?string $oldStatusPengajuan = null, ?string $oldStatusPengerjaan = null, ?string $reason = null)
    {
        $this->pemeliharaan = $pemeliharaan;
        $this->oldStatusPengajuan = $oldStatusPengajuan;
        $this->oldStatusPengerjaan = $oldStatusPengerjaan;
        $this->reason = $reason;
        // Load relasi yang mungkin dibutuhkan untuk data notifikasi
        $this->pemeliharaan->load('pengaju', 'barangQrCode.barang', 'barangQrCode.ruangan', 'barangQrCode.pemegangPersonal');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $pengajuUsername = optional($this->pemeliharaan->pengaju)->username ?? 'N/A';
        $namaBarang = optional($this->pemeliharaan->barangQrCode->barang)->nama_barang ?? 'N/A';
        $kodeInventaris = optional($this->pemeliharaan->barangQrCode)->kode_inventaris_sekolah ?? 'N/A';
        // KODE BARU YANG LEBIH BAIK
        $currentStatus = $this->pemeliharaan->status; // Otomatis menggunakan accessor baru getStatusAttribute()

        $message = 'Status laporan pemeliharaan Anda untuk barang ' . $namaBarang . ' (' . $kodeInventaris . ') telah diperbarui menjadi "' . $currentStatus . '".';

        if ($this->reason) {
            $message .= ' Catatan: ' . $this->reason;
        }

        // KODE BARU YANG LEBIH BAIK
        $rolePrefix = $notifiable->getRolePrefix(); // 'admin.', 'operator.', atau 'guru.'
        $link = route($rolePrefix . 'pemeliharaan.show', $this->pemeliharaan->id);
        return [
            'pemeliharaan_id' => $this->pemeliharaan->id,
            'status_baru_pemeliharaan' => $currentStatus,
            'message' => $message,
            'link' => $link,
            'old_status_pengajuan' => $this->oldStatusPengajuan,
            'old_status_pengerjaan' => $this->oldStatusPengerjaan,
            'reason' => $this->reason,
            'nama_barang' => $namaBarang,
            'kode_inventaris' => $kodeInventaris,
        ];
    }
}
