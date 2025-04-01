<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiBarang extends Model
{
    use HasFactory;

    protected $table = 'mutasi_barang';

    protected $fillable = [
        'id_barang',
        'id_ruangan_lama',
        'id_ruangan_baru',
        'tanggal_mutasi'
    ];

    // Relasi ke barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    // Relasi ke ruangan lama
    public function ruanganLama()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_lama');
    }

    // Relasi ke ruangan baru (bisa null)
    public function ruanganBaru()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_baru');
    }
}
