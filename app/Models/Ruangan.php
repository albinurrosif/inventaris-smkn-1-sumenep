<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'ruangan'; // Nama tabel di database
    protected $primaryKey = 'id'; // Sesuaikan dengan nama kolom di database
    public $timestamps = true;

    protected $fillable = [
        'nama_ruangan',
    ];

    /**
     * Relasi ke tabel Barang (Satu Ruangan bisa memiliki banyak Barang).
     */
    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_ruangan', 'id');
    }
}
