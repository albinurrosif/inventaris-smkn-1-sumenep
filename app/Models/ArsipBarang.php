<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArsipBarang extends Model
{
    use HasFactory;

    protected $table = 'arsip_barang';

    protected $fillable = [

        'no_seri_pabrik',
        'data_unit',
        'dipulihkan_oleh',
        'tanggal_dipulihkan',

        'id_user',
        'alasan',
        'berita_acara_path',
        'foto_bukti_path',
        'tanggal_dihapus',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function dipulihkanOleh()
    {
        return $this->belongsTo(User::class, 'dipulihkan_oleh');
    }
}
