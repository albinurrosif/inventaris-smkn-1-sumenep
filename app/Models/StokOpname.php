<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    use HasFactory;

    protected $table = 'stok_opname';
    
    protected $fillable = [
        'id_operator',
        'id_ruangan',
        'tanggal_opname',
        'status',
        'keterangan'
    ];

    // Relasi ke User (Operator yang melakukan opname)
    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator');
    }

    // Relasi ke Ruangan
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }

    // Relasi ke Detail Stok Opname
    public function detailStokOpname()
    {
        return $this->hasMany(DetailStokOpname::class, 'id_stok_opname');
    }
}
