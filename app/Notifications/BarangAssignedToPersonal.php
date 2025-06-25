<?php

namespace App\Notifications;

use App\Models\BarangQrCode; // Import model BarangQrCode
use App\Models\User; // Import model User
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BarangAssignedToPersonal extends Notification
{
    use Queueable;

    protected $barangQrCode;
    protected $assignedByUser; // User yang menyerahkan barang

    public function __construct(BarangQrCode $barangQrCode, User $assignedByUser)
    {
        $this->barangQrCode = $barangQrCode;
        $this->assignedByUser = $assignedByUser;
        $this->barangQrCode->load('barang', 'ruangan', 'pemegangPersonal');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $namaBarang = optional($this->barangQrCode->barang)->nama_barang ?? 'N/A';
        $kodeInventaris = optional($this->barangQrCode)->kode_inventaris_sekolah ?? 'N/A';
        $assignerUsername = $this->assignedByUser->username ?? 'Sistem';

        $message = "Anda telah ditugaskan sebagai pemegang personal untuk barang '{$namaBarang}' ({$kodeInventaris}).";
        $message .= " Penyerahan dilakukan oleh: {$assignerUsername}.";

        // Tentukan link secara dinamis berdasarkan peran penerima notifikasi ($notifiable)
        $link = '#'; // Default link
        if ($notifiable->hasRole(User::ROLE_ADMIN)) {
            $link = route('admin.barang-qr-code.show', $this->barangQrCode->id);
        } elseif ($notifiable->hasRole(User::ROLE_OPERATOR)) {
            $link = route('operator.barang-qr-code.show', $this->barangQrCode->id);
        } elseif ($notifiable->hasRole(User::ROLE_GURU)) {
            // Guru dialihkan ke halaman aktivitas personalnya jika ditugaskan
            $link = route('guru.barang-qr-code.show', $this->barangQrCode->id); // <-- PERBAIKAN DI SINI
        }
        // Jika notifiable adalah role lain yang tidak memiliki rute barang-qr-code.show,
        // maka akan tetap ke '#' atau bisa diarahkan ke dashboard
        else {
            $link = route('redirect-dashboard');
        }


        return [
            'barang_qr_code_id' => $this->barangQrCode->id,
            'nama_barang' => $namaBarang,
            'kode_inventaris' => $kodeInventaris,
            'assigned_by_username' => $assignerUsername,
            'message' => $message,
            'link' => $link, // <-- Link dinamis
            'type' => 'barang_assigned_personal',
        ];
    }
}
