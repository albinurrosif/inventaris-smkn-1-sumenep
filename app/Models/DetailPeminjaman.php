<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DetailPeminjaman extends Model
{
    use HasFactory;

    protected $table = 'detail_peminjaman'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary Key
    public $timestamps = true; // Mengaktifkan created_at & updated_at

    protected $fillable = [
        'id_peminjaman',
        'id_barang',
        'ruangan_asal',
        'ruangan_tujuan',
        'jumlah_dipinjam',
        'tanggal_pinjam',
        'tanggal_kembali',
        'durasi_pinjam',
        'dapat_diperpanjang',
        'diperpanjang',
        'jumlah_terverifikasi',
        'tanggal_pengembalian_aktual',
        'kondisi_sebelum',
        'kondisi_setelah',
        'status_pengembalian', // Menggunakan status_pengembalian sesuai dengan migrasi
        'disetujui_oleh_pengembalian', // Menggunakan disetujui_oleh_pengembalian
        'diverifikasi_oleh_pengembalian', // Menggunakan diverifikasi_oleh_pengembalian
    ];

    /**
     * Relasi ke tabel Peminjaman (Detail ini milik satu transaksi peminjaman).
     */
    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman');
    }

    /**
     * Relasi ke tabel Barang (Barang yang dipinjam).
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke tabel Ruangan (Ruangan asal barang).
     */
    public function ruanganAsal(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_asal');
    }

    /**
     * Relasi ke tabel Ruangan (Ruangan tujuan peminjaman).
     */
    public function ruanganTujuan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_tujuan');
    }

    /**
     * Relasi ke tabel Users (Operator/Admin yang menyetujui peminjaman).
     */
    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * Relasi ke tabel Users (Operator/Admin yang memverifikasi pengembalian barang).
     */
    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /**
     * Cek apakah detail peminjaman terlambat.
     */
    public function getTerlambatAttribute(): bool
    {
        return $this->status_pengembalian === 'dipinjam' && Carbon::now()->gt($this->tanggal_kembali);
    }

    /**
     * Hitung berapa hari terlambat.
     */
    public function getJumlahHariTerlambatAttribute(): int
    {
        if (!$this->terlambat) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->tanggal_kembali);
    }
}
