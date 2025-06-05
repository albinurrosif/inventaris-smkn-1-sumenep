<?php

// File: app/Notifications/PeminjamanBaruNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Peminjaman; // Import model Peminjaman Anda
use App\Models\User;       // Import model User Anda

class PeminjamanBaruNotification extends Notification implements ShouldQueue // Implementasi ShouldQueue jika ingin notifikasi di-queue
{
    use Queueable;

    public Peminjaman $peminjaman;
    public User $pengaju;

    /**
     * Membuat instance notifikasi baru.
     *
     * @param \App\Models\Peminjaman $peminjaman Instance peminjaman yang baru dibuat.
     * @param \App\Models\User $pengaju User yang mengajukan peminjaman.
     * @return void
     */
    public function __construct(Peminjaman $peminjaman, User $pengaju)
    {
        $this->peminjaman = $peminjaman;
        $this->pengaju = $pengaju;
    }

    /**
     * Mendapatkan channel pengiriman notifikasi.
     * Dalam kasus ini, kita menggunakan channel 'database'.
     *
     * @param  mixed  $notifiable User yang akan menerima notifikasi (misalnya, admin/operator).
     * @return array<int, string>|string
     */
    public function via(object $notifiable): array|string
    {
        return ['database']; // Bisa juga ['database', 'mail'] jika ingin kirim email juga
    }

    /**
     * Mendapatkan representasi database dari notifikasi.
     * Data yang dikembalikan di sini akan disimpan dalam kolom 'data' (JSON) di tabel 'notifications'.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'peminjaman_id' => $this->peminjaman->id,
            'judul' => 'Pengajuan Peminjaman Baru',
            'pesan' => 'Ada pengajuan peminjaman baru dari ' . $this->pengaju->username . ' untuk tujuan: ' . $this->peminjaman->tujuan_peminjaman,
            'url_detail' => route('peminjaman.show', $this->peminjaman->id), // Contoh URL ke detail peminjaman
            'pengaju_username' => $this->pengaju->username,
            'tanggal_pengajuan' => $this->peminjaman->tanggal_pengajuan->toFormattedDateString(), // Format tanggal agar mudah dibaca
        ];
    }

    /**
     * (Opsional) Mendapatkan representasi array dari notifikasi.
     * Ini bisa digunakan jika Anda ingin mengirim notifikasi melalui channel lain seperti broadcast (Pusher, dll).
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    // public function toArray(object $notifiable): array
    // {
    //     return [
    //         'peminjaman_id' => $this->peminjaman->id,
    //         'message' => 'Pengajuan peminjaman baru dari ' . $this->pengaju->username,
    //     ];
    // }

    /**
     * (Opsional) Mendapatkan representasi email dari notifikasi.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     $url = route('peminjaman.show', $this->peminjaman->id); // URL ke detail peminjaman

    //     return (new MailMessage)
    //                 ->subject('Pengajuan Peminjaman Baru')
    //                 ->greeting('Halo ' . $notifiable->username . ',')
    //                 ->line('Ada pengajuan peminjaman baru dari ' . $this->pengaju->username . '.')
    //                 ->line('Tujuan Peminjaman: ' . $this->peminjaman->tujuan_peminjaman)
    //                 ->action('Lihat Detail Peminjaman', $url)
    //                 ->line('Terima kasih telah menggunakan aplikasi kami!');
    // }
}


// **Cara Menggunakan Kelas Notifikasi di Atas:**

// Anda akan mengirim notifikasi ini (misalnya dari Controller atau Event Listener) kepada pengguna yang relevan (misalnya, semua Admin atau Operator).

// php
// <?php

// // Contoh di dalam PeminjamanController atau sebuah Event Listener

// use App\Models\Peminjaman;
// use App\Models\User;
// use App\Notifications\PeminjamanBaruNotification;
// use Illuminate\Support\Facades\Notification; // Fassad Notifikasi

// // ...

// /**
//  * Menyimpan pengajuan peminjaman baru.
//  */
// public function store(Request $request)
// {
//     // ... (Validasi dan logika penyimpanan peminjaman)
//     $peminjaman = new Peminjaman($request->validated());
//     $peminjaman->id_guru = auth()->id(); // Atau user yang mengajukan
//     // ... set field lain
//     $peminjaman->save();

//     // Dapatkan user yang mengajukan
//     $pengaju = auth()->user(); // atau User::find($peminjaman->id_guru);

//     // Kirim notifikasi ke semua Admin dan Operator
//     $penerimaNotifikasi = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->get();

//     if ($penerimaNotifikasi->isNotEmpty()) {
//         Notification::send($penerimaNotifikasi, new PeminjamanBaruNotification($peminjaman, $pengaju));
//         // Atau jika hanya satu penerima:
//         // $admin->notify(new PeminjamanBaruNotification($peminjaman, $pengaju));
//     }

//     // ... (Redirect atau response lainnya)
// }
