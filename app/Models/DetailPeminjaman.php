<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DetailPeminjaman extends Model
{
    use HasFactory;

    protected $table = 'detail_peminjaman';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_peminjaman',
        'id_barang',
        'ruangan_asal',
        'ruangan_tujuan',
        'jumlah_dipinjam',
        'tanggal_pinjam',
        'tanggal_kembali',
        'tanggal_dipinjam',
        'tanggal_pengembalian_aktual',
        'durasi_pinjam',
        'dapat_diperpanjang',
        'diperpanjang',
        'jumlah_terverifikasi',
        'status_persetujuan',
        'status_pengambilan',
        'kondisi_sebelum',
        'kondisi_setelah',
        'status_pengembalian',
        'disetujui_oleh',
        'tanggal_disetujui',
        'ditolak_oleh',
        'tanggal_ditolak',
        'pengambilan_dikonfirmasi_oleh',
        'tanggal_pengambilan_dikonfirmasi',
        'disetujui_oleh_pengembalian',
        'diverifikasi_oleh_pengembalian',
        'catatan',
    ];

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function ruanganAsal(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_asal');
    }

    public function ruanganTujuan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_tujuan');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function ditolakOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditolak_oleh');
    }

    public function pengambilanDikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengambilan_dikonfirmasi_oleh');
    }

    public function disetujuiOlehPengembalian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh_pengembalian');
    }

    public function diverifikasiOlehPengembalian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh_pengembalian');
    }

    public function getTerlambatAttribute(): bool
    {
        return $this->status_pengembalian === 'dipinjam' && Carbon::now()->gt($this->tanggal_kembali);
    }

    public function getJumlahHariTerlambatAttribute(): int
    {
        if (!$this->terlambat) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->tanggal_kembali);
    }

    // Menetapkan status persetujuan item
    public function setujui(int $userId): void
    {
        $this->status_persetujuan = 'disetujui';
        $this->disetujui_oleh = $userId;
        $this->tanggal_disetujui = now();
        $this->status_pengambilan = 'belum_diambil';
        $this->save();

        // Update status peminjaman
        $this->peminjaman->updateStatusPersetujuan();
    }

    // Menolak persetujuan item
    public function tolak(int $userId): void
    {
        $this->status_persetujuan = 'ditolak';
        $this->ditolak_oleh = $userId;
        $this->tanggal_ditolak = now();
        $this->save();

        // Update status peminjaman
        $this->peminjaman->updateStatusPersetujuan();
    }

    // Konfirmasi pengambilan item
    public function konfirmasiPengambilan(int $userId, ?int $jumlahDiambil = null): void
    {
        // Pastikan item sudah disetujui
        if ($this->status_persetujuan !== 'disetujui') {
            return;
        }

        $this->pengambilan_dikonfirmasi_oleh = $userId;
        $this->tanggal_pengambilan_dikonfirmasi = now();
        $this->tanggal_dipinjam = now(); // Tanggal aktual dipinjam

        // Jika jumlah yang diambil diberikan dan kurang dari total
        if ($jumlahDiambil !== null && $jumlahDiambil < $this->jumlah_dipinjam) {
            $this->status_pengambilan = 'sebagian_diambil';
            $this->jumlah_terverifikasi = $jumlahDiambil;
        } else {
            $this->status_pengambilan = 'sudah_diambil';
            $this->jumlah_terverifikasi = $this->jumlah_dipinjam;
        }

        $this->status_pengembalian = 'dipinjam'; // Sekarang item sedang dipinjam
        $this->save();

        // Update status peminjaman
        $this->peminjaman->updateStatusPengambilan();
        $this->peminjaman->updateStatusPengembalian();
    }

    // Ajukan pengembalian item
    public function ajukanPengembalian(?string $kondisi = null): void
    {
        // Pastikan item sudah diambil
        if (!in_array($this->status_pengambilan, ['sudah_diambil', 'sebagian_diambil'])) {
            return;
        }

        $this->status_pengembalian = 'menunggu_verifikasi';
        if ($kondisi) {
            $this->kondisi_setelah = $kondisi;
        }
        $this->save();
    }

    // Verifikasi pengembalian item
    public function verifikasiPengembalian(int $userId, string $status, ?string $kondisi = null): void
    {
        // Pastikan item menunggu verifikasi pengembalian
        if ($this->status_pengembalian !== 'menunggu_verifikasi') {
            return;
        }

        $this->status_pengembalian = $status; // dikembalikan, rusak, hilang, atau ditolak
        $this->diverifikasi_oleh_pengembalian = $userId;
        $this->tanggal_pengembalian_aktual = now();

        if ($kondisi) {
            $this->kondisi_setelah = $kondisi;
        }

        $this->save();

        // Update status peminjaman
        $this->peminjaman->updateStatusPengembalian();
    }
}
