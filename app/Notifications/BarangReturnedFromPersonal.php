<?php

namespace App\Notifications;

use App\Models\BarangQrCode; // Import model BarangQrCode
use App\Models\User; // Import model User
use App\Models\Ruangan; // Import model Ruangan
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BarangReturnedFromPersonal extends Notification
{
    use Queueable;

    protected $barangQrCode;
    protected $ruanganTujuan;
    protected $pemegangLama; // User yang mengembalikan barang

    /**
     * Create a new notification instance.
     */
    public function __construct(BarangQrCode $barangQrCode, Ruangan $ruanganTujuan, User $pemegangLama)
    {
        $this->barangQrCode = $barangQrCode;
        $this->ruanganTujuan = $ruanganTujuan;
        $this->pemegangLama = $pemegangLama;
        // Muat relasi yang mungkin dibutuhkan untuk notifikasi
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
        $pemegangLamaUsername = optional($this->pemegangLama)->username ?? 'Tidak Diketahui';
        $namaRuanganTujuan = optional($this->ruanganTujuan)->nama_ruangan ?? 'N/A';

        $message = "Barang '{$namaBarang}' ({$kodeInventaris}) telah dikembalikan dari {$pemegangLamaUsername} ke ruangan Anda ({$namaRuanganTujuan}).";

        // Link ke detail barangQrCode untuk Operator
        $link = route('operator.barang-qr-code.show', $this->barangQrCode->id);

        return [
            'barang_qr_code_id' => $this->barangQrCode->id,
            'ruangan_tujuan_id' => $this->ruanganTujuan->id,
            'nama_barang' => $namaBarang,
            'kode_inventaris' => $kodeInventaris,
            'pemegang_lama_username' => $pemegangLamaUsername,
            'nama_ruangan_tujuan' => $namaRuanganTujuan,
            'message' => $message,
            'link' => $link,
            'type' => 'barang_returned_personal',
        ];
    }
}
