<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiBarang extends Model
{
    use HasFactory;

    protected $table = 'mutasi_barang';

    protected $fillable = [
        'id_barang_qr_code',
        'id_ruangan_asal',
        'id_ruangan_tujuan',
        'tanggal_mutasi',
        'alasan_pemindahan',
        'id_user_admin',
        'surat_pemindahan_path',
        'catatan',
    ];



    public function barangQrCode()
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code');
    }

    public function ruanganAsal()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_asal');
    }

    public function ruanganTujuan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_tujuan');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'id_user_admin');
    }
}
