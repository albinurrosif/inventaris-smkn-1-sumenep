<?php

namespace App\Notifications;

use App\Models\StokOpname;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class StokOpnameCanceled extends Notification
{
    use Queueable;

    protected $stokOpname;
    protected $canceledByUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(StokOpname $stokOpname, User $canceledByUser)
    {
        $this->stokOpname = $stokOpname;
        $this->canceledByUser = $canceledByUser;
        $this->stokOpname->load('ruangan'); // Muat relasi
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
        $tanggalOpname = Carbon::parse($this->stokOpname->tanggal_opname)->isoFormat('DD MMMM FF');
        $canceledUsername = optional($this->canceledByUser)->username ?? 'Sistem';

        $message = "Sesi Stok Opname di ruangan '{$namaRuangan}' ({$tanggalOpname}) telah dibatalkan.";
        $message .= " Dibatalkan oleh: {$canceledUsername}.";

        // Link ke halaman detail SO yang sudah dibatalkan
        $link = route('operator.stok-opname.show', $this->stokOpname->id);

        return [
            'stok_opname_id' => $this->stokOpname->id,
            'nama_ruangan' => $namaRuangan,
            'tanggal_opname' => $tanggalOpname,
            'canceled_by_user' => $canceledUsername,
            'message' => $message,
            'link' => $link,
            'type' => 'stok_opname_canceled',
        ];
    }
}
