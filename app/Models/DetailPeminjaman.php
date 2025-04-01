<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPeminjaman extends Model
{
    use HasFactory;

    protected $table = 'detail_peminjaman'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary Key
    public $timestamps = true; // Mengaktifkan created_at & updated_at

    protected $fillable = [
        'id_peminjaman',
        'id_barang',
        'jumlah_dipinjam',
        'jumlah_terverifikasi',
        'tanggal_pengembalian',
        'diperpanjang',
        'kondisi_sebelum',
        'kondisi_setelah',
        'status_pengembalian',
        'disetujui_oleh',
        'diverifikasi_oleh',
    ];

    /**
     * Relasi ke tabel Peminjaman (Detail ini milik satu transaksi peminjaman).
     */
    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman');
    }

    /**
     * Relasi ke tabel Barang (Barang yang dipinjam).
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke tabel Users (Operator/Admin yang menyetujui peminjaman).
     */
    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * Relasi ke tabel Users (Operator/Admin yang memverifikasi pengembalian barang).
     */
    public function diverifikasiOleh()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
