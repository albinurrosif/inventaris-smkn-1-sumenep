<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapStok extends Model
{
    use HasFactory;

    protected $table = 'rekap_stok';
    protected $fillable = ['id_barang', 'id_ruangan', 'semester', 'tahun', 'stok'];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }
}

