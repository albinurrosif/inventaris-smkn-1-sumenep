<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary Key
    public $timestamps = true; // Mengaktifkan created_at & updated_at

    protected $fillable = [
        'id_peminjam', // ID Peminjam
        'tanggal_pengajuan', // Tanggal Pengajuan
        'status_pengajuan', // Status Pengajuan
        'pengajuan_disetujui_oleh', // Admin/Operator yang menyetujui pengajuan
        'keterangan', // Keterangan Tambahan
        'tanggal_disetujui', // Tanggal Disetujui
    ];

    /**
     * Relasi ke tabel Users (User yang meminjam barang).
     */
    public function peminjam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_peminjam');
    }

    /**
     * Relasi ke tabel Users (Admin/operator yang menyetujui pengajuan).
     */
    public function pengajuanDisetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajuan_disetujui_oleh');
    }

    /**
     * Relasi ke tabel DetailPeminjaman (Barang-barang yang dipinjam dalam transaksi ini).
     */
    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_peminjaman');
    }

    /**
     * Cek apakah ada item peminjaman yang terlambat.
     */
    public function getAdaItemTerlambatAttribute(): bool
    {
        return $this->detailPeminjaman()
            ->where('status', 'dipinjam')
            ->where('tanggal_kembali', '<', now())
            ->exists();
    }

    /**
     * Cek jumlah total barang yang dipinjam dalam peminjaman ini.
     */
    public function getTotalBarangAttribute(): int
    {
        return $this->detailPeminjaman()->sum('jumlah_dipinjam');
    }

    /**
     * Mengubah status pengajuan peminjaman berdasarkan proses approval.
     */
    public function setStatusPengajuan(string $status): void
    {
        $this->status_pengajuan = $status;
        if ($status == 'disetujui') {
            $this->tanggal_disetujui = now();
        }
        $this->save();
    }
}
