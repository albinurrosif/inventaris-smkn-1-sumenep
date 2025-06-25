<?php

namespace App\Notifications;

use App\Models\Peminjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Pastikan ini diimport

class NewPeminjamanRequest extends Notification
{
    use Queueable;

    protected $peminjaman;

    /**
     * Create a new notification instance.
     */
    public function __construct(Peminjaman $peminjaman)
    {
        $this->peminjaman = $peminjaman;
        $this->peminjaman->load('guru'); // Pastikan relasi guru dimuat
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
        $guru = $this->peminjaman->guru;

        // --- HAPUS BLOK INI KARENA TIDAK PERLU JIKA RELASI DIMUAT DENGAN BENAR ---
        // if (is_array($guru)) {
        //     $guru = new \App\Models\User($guru);
        // }
        // --- AKHIR BLOK YANG DIHAPUS ---

        $pengajuUsername = optional($guru)->username ?? 'N/A';
        // Log::debug('Tipe dari guru:', ['class' => get_class(optional($guru))]); // Hapus log ini jika tidak diperlukan lagi

        // Tentukan link berdasarkan peran penerima
        $link = '#'; // Default link
        if ($notifiable->hasRole(User::ROLE_ADMIN)) {
            $link = route('admin.peminjaman.show', $this->peminjaman->id);
        } elseif ($notifiable->hasRole(User::ROLE_OPERATOR)) {
            $link = route('operator.peminjaman.show', $this->peminjaman->id);
        }
        // Guru tidak menerima notifikasi ini, tapi jika di masa depan ada, bisa ditambahkan
        // elseif ($notifiable->hasRole(\App\Models\User::ROLE_GURU)) {
        //     $link = route('guru.peminjaman.show', $this->peminjaman->id);
        // }


        return [
            'peminjaman_id' => $this->peminjaman->id,
            'tujuan_peminjaman' => $this->peminjaman->tujuan_peminjaman,
            'pengaju_username' => $pengajuUsername,
            'message' => 'Pengajuan peminjaman baru telah diajukan oleh ' . $pengajuUsername . ' untuk tujuan "' . $this->peminjaman->tujuan_peminjaman . '".',
            'link' => $link,
        ];
    }
}
