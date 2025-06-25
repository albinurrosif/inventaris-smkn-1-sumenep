<?php

namespace App\Notifications;

use App\Models\Pemeliharaan; // Import model Pemeliharaan
use App\Models\User; // Import model User
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewPemeliharaanRequest extends Notification
{
    use Queueable;

    protected $pemeliharaan;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pemeliharaan $pemeliharaan)
    {
        $this->pemeliharaan = $pemeliharaan;
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
        $deskripsiKerusakan = $this->pemeliharaan->catatan_pengajuan ?? 'Tanpa deskripsi.';

        $message = 'Laporan pemeliharaan baru telah diajukan oleh ' . $pengajuUsername . ' untuk barang ' . $namaBarang . ' (' . $kodeInventaris . ').';
        $message .= ' Deskripsi: "' . $deskripsiKerusakan . '".';

        $link = '#'; // Default link
        if ($notifiable->hasRole(User::ROLE_ADMIN)) {
            $link = route('admin.pemeliharaan.show', $this->pemeliharaan->id);
        } elseif ($notifiable->hasRole(User::ROLE_OPERATOR)) {
            $link = route('operator.pemeliharaan.show', $this->pemeliharaan->id);
        }

        return [
            'pemeliharaan_id' => $this->pemeliharaan->id,
            'pengaju_username' => $pengajuUsername,
            'nama_barang' => $namaBarang,
            'kode_inventaris' => $kodeInventaris,
            'deskripsi_kerusakan' => $deskripsiKerusakan,
            'message' => $message,
            'link' => $link,
        ];
    }
}
