<?php

namespace App\Notifications;

use App\Models\StokOpname; // Import model StokOpname
use App\Models\User; // Import model User
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon; // Ditambahkan
use Illuminate\Support\Facades\Auth;

class StokOpnameAssigned extends Notification
{
    use Queueable;

    protected $stokOpname;

    /**
     * Create a new notification instance.
     */
    public function __construct(StokOpname $stokOpname)
    {
        $this->stokOpname = $stokOpname;
        // Muat relasi yang mungkin dibutuhkan untuk data notifikasi
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
        $tanggalOpname = Carbon::parse($this->stokOpname->tanggal_opname)->isoFormat('DD MMMM YYYY');

        $message = 'Anda telah ditugaskan untuk melakukan Stok Opname di ruangan ' . $namaRuangan . '.';
        $message .= ' Tanggal target: ' . $tanggalOpname . '.';
        $message .= ' Status: ' . $this->stokOpname->status . '.';

        $link = route('operator.stok-opname.show', $this->stokOpname->id); // Link ke detail Stok Opname untuk Operator

        return [
            'stok_opname_id' => $this->stokOpname->id,
            'nama_ruangan' => $namaRuangan,
            'tanggal_opname' => $tanggalOpname,
            'status_stok_opname' => $this->stokOpname->status,
            'message' => $message,
            'link' => $link,
            'assigned_by_user' => optional(Auth::user())->username ?? 'Sistem', // Siapa yang menugaskan
        ];
    }
}
