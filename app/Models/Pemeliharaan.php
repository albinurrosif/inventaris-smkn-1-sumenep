<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemeliharaan extends Model
{
    use HasFactory;

    protected $table = 'pemeliharaan';

    protected $fillable = [
        'id_barang',
        'id_operator',
        'id_ruangan',
        'tanggal_pemeliharaan',
        'deskripsi',
        'biaya',
        'status',
        'hasil_pemeliharaan'
    ];

    /**
     * Relasi ke model Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke model User (Operator)
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator');
    }

    /**
     * Relasi ke model Ruangan
     */
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }
}
