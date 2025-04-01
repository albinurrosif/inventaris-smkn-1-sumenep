<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailStokOpname extends Model
{
    use HasFactory;

    protected $table = 'detail_stok_opname';

    protected $fillable = [
        'id_stok_opname',
        'id_barang',
        'jumlah_tercatat',
        'jumlah_fisik',
        'kondisi',
        'id_barang_status',
        'id_pemeliharaan',
    ];

    /**
     * Relasi ke StokOpname
     */
    public function stokOpname()
    {
        return $this->belongsTo(StokOpname::class, 'id_stok_opname');
    }

    /**
     * Relasi ke Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke BarangStatus (jika ada barang rusak/hilang)
     */
    public function barangStatus()
    {
        return $this->belongsTo(BarangStatus::class, 'id_barang_status');
    }

    /**
     * Relasi ke Pemeliharaan (jika barang diajukan untuk diperbaiki)
     */
    public function pemeliharaan()
    {
        return $this->belongsTo(Pemeliharaan::class, 'id_pemeliharaan');
    }
}
