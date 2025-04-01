<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangStatus extends Model
{
    use HasFactory;

    protected $table = 'barang_status'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary Key
    public $timestamps = true;

    protected $fillable = [
        'id_barang',
        'id_operator',
        'tanggal',
        'deskripsi',
        'status',
        'id_ruangan',
        'id_peminjaman',
    ];

    /**
     * Relasi ke model Barang (satu status terkait dengan satu barang).
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke model User (operator yang mencatat status barang).
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator');
    }

    /**
     * Relasi ke model Ruangan (status barang terkait dengan ruangan tertentu, opsional).
     */
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }

    /**
     * Relasi ke model Peminjaman (jika status barang terkait dengan peminjaman, opsional).
     */
    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman');
    }
}
