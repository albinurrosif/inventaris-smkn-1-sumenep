<?php

namespace App\Notifications;

use App\Models\StokOpname; // Import model StokOpname
use App\Models\User; // Import model User
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon; // Ditambahkan
use Illuminate\Support\Facades\Log;

class StokOpnameFinished extends Notification
{
    use Queueable;

    protected $stokOpname;

    /**
     * Create a new notification instance.
     */
    public function __construct(StokOpname $stokOpname)
    {
        $this->stokOpname = $stokOpname;
        // Muat relasi yang mungkin dibutuhkan
        $this->stokOpname->load('ruangan', 'operator');
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
        $namaRuangan = optional($this->stokOpname->ruangan)->nama_ruangan ?? 'N/A';
        $operatorPelaksana = optional($this->stokOpname->operator)->username ?? 'Sistem';
        $tanggalOpname = Carbon::parse($this->stokOpname->tanggal_opname)->isoFormat('DD MMMM YYYY');

        $message = "Sesi Stok Opname di ruangan '{$namaRuangan}' pada tanggal {$tanggalOpname} telah Selesai difinalisasi.";
        $message .= " Dilaksanakan oleh: {$operatorPelaksana}.";

        $link = route('admin.stok-opname.show', $this->stokOpname->id); // Admin melihat detail SO

        return [
            'stok_opname_id' => $this->stokOpname->id,
            'nama_ruangan' => $namaRuangan,
            'tanggal_opname' => $tanggalOpname,
            'operator_pelaksana' => $operatorPelaksana,
            'message' => $message,
            'link' => $link,
            'type' => 'stok_opname_finished',
        ];
    }
}
