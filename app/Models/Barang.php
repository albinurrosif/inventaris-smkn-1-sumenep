<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = [
        'nama_barang',
        'merk_model',
        'no_seri_pabrik',
        'ukuran',
        'bahan',
        'tahun_pembuatan_pembelian',
        'kode_barang',
        'jumlah_barang',
        'harga_beli',
        'sumber',
        'keadaan_barang',
        'keterangan_mutasi',
        'id_ruangan',
    ];

    /**
     * Relasi ke tabel Ruangan
     */
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }

    /**
     * Relasi ke tabel BarangQRCode (1 kode untuk beberapa barang dengan kode yang sama)
     */
    public function qrCode()
    {
        return $this->hasOne(BarangQRCode::class, 'kode_barang', 'kode_barang');
    }

    /**
     * Relasi ke tabel DetailPeminjaman (karena barang bisa dipinjam)
     */
    public function peminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_barang');
    }

    /**
     * Relasi ke tabel Pemeliharaan (barang bisa menjalani perawatan)
     */
    public function pemeliharaan()
    {
        return $this->hasMany(Pemeliharaan::class, 'id_barang');
    }

    /**
     * Relasi ke tabel DetailStokOpname (barang bisa dicek dalam stok opname)
     */
    public function stokOpname()
    {
        return $this->hasMany(DetailStokOpname::class, 'id_barang');
    }
}
