<?php

namespace App\Notifications;

use App\Models\BarangQrCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BarangTransferredPersonal extends Notification
{
    use Queueable;

    protected $barangQrCode;
    protected $oldHolder; // User yang sebelumnya memegang
    protected $actorUser; // User yang melakukan transfer

    /**
     * Create a new notification instance.
     */
    public function __construct(BarangQrCode $barangQrCode, User $oldHolder, User $actorUser)
    {
        $this->barangQrCode = $barangQrCode;
        $this->oldHolder = $oldHolder;
        $this->actorUser = $actorUser;
        // Muat relasi yang dibutuhkan
        $this->barangQrCode->load('barang');
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
        $namaBarang = optional($this->barangQrCode->barang)->nama_barang ?? 'N/A';
        $kodeInventaris = optional($this->barangQrCode)->kode_inventaris_sekolah ?? 'N/A';
        $actorUsername = optional($this->actorUser)->username ?? 'Sistem';

        // Tentukan pesan berdasarkan siapa yang menerima notifikasi
        if ($notifiable->id === $this->oldHolder->id) {
            // Pesan untuk pemegang lama
            $message = "Barang '{$namaBarang}' ({$kodeInventaris}) yang Anda pegang telah ditransfer ke " . optional($this->barangQrCode->pemegangPersonal)->username . ".";
            $message .= " Aksi dilakukan oleh: {$actorUsername}.";
        } else {
            // Pesan untuk pemegang baru
            $message = "Anda telah menerima transfer barang '{$namaBarang}' ({$kodeInventaris}) dari " . optional($this->oldHolder)->username . ".";
            $message .= " Aksi dilakukan oleh: {$actorUsername}.";
        }

        // Tentukan link secara dinamis berdasarkan peran penerima
        $link = route('redirect-dashboard'); // Default
        if ($notifiable->hasRole(User::ROLE_ADMIN)) {
            $link = route('admin.barang-qr-code.show', $this->barangQrCode->id);
        } elseif ($notifiable->hasRole(User::ROLE_OPERATOR) || $notifiable->hasRole(User::ROLE_GURU)) {
            // Keduanya diarahkan ke halaman detail unit barang
            $link = route($notifiable->getRolePrefix() . 'barang-qr-code.show', $this->barangQrCode->id);
        }

        return [
            'barang_qr_code_id' => $this->barangQrCode->id,
            'nama_barang' => $namaBarang,
            'kode_inventaris' => $kodeInventaris,
            'old_holder_username' => optional($this->oldHolder)->username ?? 'N/A',
            'new_holder_username' => optional($this->barangQrCode->pemegangPersonal)->username ?? 'N/A',
            'actor_username' => $actorUsername,
            'message' => $message,
            'link' => $link,
            'type' => 'barang_transferred_personal',
        ];
    }
}
