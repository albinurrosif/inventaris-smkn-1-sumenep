<?php

namespace App\Notifications;

use App\Models\Peminjaman;
use App\Models\User; // Ditambahkan
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log; // Ditambahkan

class PeminjamanStatusUpdated extends Notification
{
    use Queueable;

    protected $peminjaman;
    protected $oldStatus; // Status sebelumnya, opsional
    protected $reason; // Alasan perubahan status, opsional

    /**
     * Create a new notification instance.
     */
    public function __construct(Peminjaman $peminjaman, ?string $oldStatus = null, ?string $reason = null)
    {
        $this->peminjaman = $peminjaman;
        $this->oldStatus = $oldStatus;
        $this->reason = $reason;
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
        $message = 'Pengajuan peminjaman Anda untuk tujuan "' . $this->peminjaman->tujuan_peminjaman . '" telah ';
        $link = route('guru.peminjaman.show', $this->peminjaman->id); // Link ke detail peminjaman

        switch ($this->peminjaman->status) {
            case Peminjaman::STATUS_DISETUJUI:
                $message .= 'disetujui.';
                break;
            case Peminjaman::STATUS_DITOLAK:
                $message .= 'ditolak.';
                if ($this->reason) {
                    $message .= ' Alasan: ' . $this->reason;
                }
                break;
            case Peminjaman::STATUS_DIBATALKAN:
                $message .= 'dibatalkan.';
                if ($this->reason) {
                    $message .= ' Alasan: ' . $this->reason;
                }
                break;
            default:
                $message .= 'diperbarui statusnya menjadi "' . $this->peminjaman->status . '".';
                break;
        }

        return [
            'peminjaman_id' => $this->peminjaman->id,
            'status_baru' => $this->peminjaman->status,
            'message' => $message,
            'link' => $link,
            'old_status' => $this->oldStatus,
            'reason' => $this->reason,
        ];
    }
}
