<?php

namespace App\Notifications;

use App\Models\Peminjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon; // Pastikan ini diimpor

class PeminjamanOverdue extends Notification
{
    use Queueable;

    protected $peminjaman;
    protected $daysOverdue;

    /**
     * Create a new notification instance.
     */
    public function __construct(Peminjaman $peminjaman, int $daysOverdue)
    {
        $this->peminjaman = $peminjaman;
        $this->daysOverdue = $daysOverdue;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Atau ['database', 'mail']
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'PERINGATAN! Peminjaman Barang Terlambat Dikembalikan!';
        $greeting = 'Halo ' . $notifiable->username . ',';
        $line1 = 'Peminjaman Anda untuk tujuan "' . $this->peminjaman->tujuan_peminjaman . '" sudah terlambat dikembalikan!';
        $line2 = 'Sudah terlambat selama ' . $this->daysOverdue . ' hari sejak tanggal seharusnya dikembalikan (' . Carbon::parse($this->peminjaman->tanggal_harus_kembali)->isoFormat('DD MMMM YYYY') . ').';
        $actionText = 'Lihat Detail Peminjaman';
        $actionUrl = route('guru.peminjaman.show', $this->peminjaman->id);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1)
            ->line($line2)
            ->action($actionText, $actionUrl)
            ->error() // Menambahkan warna merah pada email
            ->line('Mohon segera kembalikan barang dan hubungi operator jika ada kendala.');
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
            'message' => 'PERINGATAN: Peminjaman untuk tujuan "' . $this->peminjaman->tujuan_peminjaman . '" sudah terlambat ' . $this->daysOverdue . ' hari!',
            'link' => route('guru.peminjaman.show', $this->peminjaman->id),
            'type' => 'overdue',
        ];
    }
}
