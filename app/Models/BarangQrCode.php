<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangQrCode extends Model
{
    use HasFactory;

    protected $table = 'barang_qr_codes'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary key tabel
    public $timestamps = true;

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'jumlah',
        'deskripsi',
        'qr_code',
    ];

    /**
     * Relasi ke tabel Barang (Setiap kode_barang hanya memiliki satu QR Code).
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }
}
