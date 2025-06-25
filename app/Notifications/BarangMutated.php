<?php

namespace App\Notifications;

use App\Models\MutasiBarang; // Import model MutasiBarang
use App\Models\User; // Import model User
use App\Models\BarangQrCode; // Pastikan ini di-import
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BarangMutated extends Notification
{
    use Queueable;

    protected $mutasi;
    protected $actorUser; // User (Admin/Operator) yang melakukan mutasi

    /**
     * Create a new notification instance.
     */
    public function __construct(MutasiBarang $mutasi, User $actorUser)
    {
        $this->mutasi = $mutasi;
        $this->actorUser = $actorUser;
        // Muat relasi yang mungkin dibutuhkan
        $this->mutasi->load('barangQrCode.barang', 'ruanganAsal', 'ruanganTujuan', 'admin');
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
        $namaBarang = optional($this->mutasi->barangQrCode->barang)->nama_barang ?? 'N/A';
        $kodeInventaris = optional($this->mutasi->barangQrCode)->kode_inventaris_sekolah ?? 'N/A';
        $ruanganAsal = optional($this->mutasi->ruanganAsal)->nama_ruangan ?? 'Tidak Berlokasi (Temuan)';
        $ruanganTujuan = optional($this->mutasi->ruanganTujuan)->nama_ruangan ?? 'Tidak Diketahui';
        $actorUsername = optional($this->actorUser)->username ?? 'Sistem';

        $link = '#';
        $message = '';

        // Tentukan link dan pesan berdasarkan apakah ini mutasi sejati (punya ID) atau penempatan awal (dummy)
        if (is_null($this->mutasi->id)) {
            // Ini adalah objek dummy untuk aksi "Tempatkan di Ruangan"
            $message = "Unit '{$namaBarang}' ({$kodeInventaris}) telah ditempatkan di ruangan {$ruanganTujuan}.";
            // Link akan ke halaman detail unit barangnya, bukan detail mutasi
            $link = route('operator.barang-qr-code.show', $this->mutasi->id_barang_qr_code);
        } else {
            // Ini adalah record MutasiBarang yang sebenarnya
            $message = "Barang '{$namaBarang}' ({$kodeInventaris}) telah dimutasi dari {$ruanganAsal} ke {$ruanganTujuan}.";
            // Link ke halaman detail mutasi untuk Admin/Operator
            $link = route('operator.mutasi-barang.show', $this->mutasi->id);
            // Sesuaikan link untuk Admin
            if ($notifiable->hasRole(User::ROLE_ADMIN)) {
                $link = route('admin.mutasi-barang.show', $this->mutasi->id);
            }
        }

        $message .= " Dilakukan oleh: {$actorUsername}.";

        return [
            'mutasi_id' => $this->mutasi->id,
            'barang_qr_code_id' => optional($this->mutasi->barangQrCode)->id,
            'nama_barang' => $namaBarang,
            'kode_inventaris' => $kodeInventaris,
            'ruangan_asal' => $ruanganAsal,
            'ruangan_tujuan' => $ruanganTujuan,
            'actor_username' => $actorUsername,
            'message' => $message,
            'link' => $link,
            'type' => 'barang_mutated',
        ];
    }
}
