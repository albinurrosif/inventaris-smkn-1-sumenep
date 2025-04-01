<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary Key
    public $timestamps = true; // Mengaktifkan created_at & updated_at

    protected $fillable = [
        'id_peminjam',
        'id_ruangan',
        'tanggal_pinjam',
        'tanggal_kembali',
        'durasi_pinjam',
        'dapat_diperpanjang',
        'diproses_oleh',
        'status',
        'keterangan',
    ];

    /**
     * Relasi ke tabel Users (User yang meminjam barang).
     */
    public function peminjam()
    {
        return $this->belongsTo(User::class, 'id_peminjam');
    }

    /**
     * Relasi ke tabel Users (Admin/operator yang memproses peminjaman).
     */
    public function diprosesOleh()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    /**
     * Relasi ke tabel Ruangan (Ruangan tempat barang yang dipinjam berada).
     */
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }

    /**
     * Relasi ke tabel DetailPeminjaman (Barang-barang yang dipinjam dalam transaksi ini).
     */
    public function detailPeminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_peminjaman');
    }
}
