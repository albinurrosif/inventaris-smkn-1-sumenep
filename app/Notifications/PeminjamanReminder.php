<?php

namespace App\Notifications;

use App\Models\Peminjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon; // Pastikan ini diimpor

class PeminjamanReminder extends Notification
{
    use Queueable;

    protected $peminjaman;
    protected $daysRemaining;

    /**
     * Create a new notification instance.
     */
    public function __construct(Peminjaman $peminjaman, int $daysRemaining)
    {
        $this->peminjaman = $peminjaman;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Atau ['database', 'mail'] jika Anda ingin mengirim email juga
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Peringatan: Pengembalian Barang Peminjaman Mendekati Jatuh Tempo!';
        $greeting = 'Halo ' . $notifiable->username . ',';
        $line1 = 'Ini adalah pengingat bahwa beberapa barang pinjaman Anda akan segera jatuh tempo.';
        $line2 = 'Peminjaman untuk tujuan "' . $this->peminjaman->tujuan_peminjaman . '" harus dikembalikan dalam ' . $this->daysRemaining . ' hari.';
        $actionText = 'Lihat Detail Peminjaman';
        $actionUrl = route('guru.peminjaman.show', $this->peminjaman->id);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1)
            ->line($line2)
            ->action($actionText, $actionUrl)
            ->line('Mohon segera kembalikan barang tepat waktu untuk menghindari denda.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'peminjaman_id' => $this->peminjaman->id,
            'tujuan_peminjaman' => $this->peminjaman->tujuan_peminjaman,
            'message' => 'Pengingat: Peminjaman untuk tujuan "' . $this->peminjaman->tujuan_peminjaman . '" akan jatuh tempo dalam ' . $this->daysRemaining . ' hari.',
            'link' => route('guru.peminjaman.show', $this->peminjaman->id),
            'type' => 'reminder',
        ];
    }
}
