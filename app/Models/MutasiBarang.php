<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Model MutasiBarang merepresentasikan histori perpindahan unit barang.
 */
class MutasiBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mutasi_barangs';
    protected $dates = ['deleted_at'];

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Ini adalah "daftar izin" kolom yang boleh diisi melalui create() atau update().
     */
    protected $fillable = [
        'id_barang_qr_code',
        'jenis_mutasi',
        'id_ruangan_asal',
        'id_pemegang_asal',      // <-- Kolom baru
        'id_ruangan_tujuan',
        'id_pemegang_tujuan',    // <-- Kolom baru
        'tanggal_mutasi',
        'alasan_pemindahan',
        'id_user_admin',         // <-- Menggunakan nama kolom asli Anda
        'surat_pemindahan_path',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     */
    protected $casts = [
        'tanggal_mutasi' => 'datetime',
    ];

    // --- Method Relasi ---

    public function barangQrCode(): BelongsTo
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code')->withTrashed();
    }

    public function ruanganAsal(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_asal');
    }

    public function ruanganTujuan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_tujuan');
    }

    /**
     * Mendapatkan data pemegang personal asal mutasi.
     */
    public function pemegangAsal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pemegang_asal');
    }

    /**
     * Mendapatkan data pemegang personal tujuan mutasi.
     */
    public function pemegangTujuan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pemegang_tujuan');
    }

    /**
     * Mendefinisikan relasi ke User yang melakukan mutasi.
     * Menggunakan foreign key 'id_user_admin' sesuai tabel asli Anda.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_admin');
    }

    /**
     * Scope untuk memfilter riwayat mutasi berdasarkan request.
     */
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        return $query->when($request->input('search'), function ($q, $term) {
            $q->where(function ($q2) use ($term) {
                $q2->where('alasan_pemindahan', 'like', '%' . $term . '%')
                    ->orWhereHas('barangQrCode.barang', function ($qBarang) use ($term) {
                        $qBarang->where('nama_barang', 'like', '%' . $term . '%');
                    })
                    ->orWhereHas('barangQrCode', function ($qKode) use ($term) {
                        $qKode->where('kode_inventaris_sekolah', 'like', '%' . $term . '%');
                    });
            });
        })
            ->when($request->input('jenis_mutasi'), function ($q, $jenis) {
                $q->where('jenis_mutasi', $jenis);
            })
            ->when($request->input('id_user_pencatat'), function ($q, $userId) {
                // Menggunakan foreign key yang benar
                $q->where('id_user_admin', $userId);
            })
            ->when($request->input('tanggal_mulai'), function ($q, $tanggal) {
                $q->whereDate('tanggal_mutasi', '>=', $tanggal);
            })
            ->when($request->input('tanggal_selesai'), function ($q, $tanggal) {
                $q->whereDate('tanggal_mutasi', '<=', $tanggal);
            });
    }

    /**
     * Metode boot model.
     * Kita nonaktifkan event 'created' agar semua logika terpusat di Controller.
     */
    protected static function boot()
    {
        parent::boot();
    }
}
