<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_peminjam',
        'tanggal_pengajuan',
        'status_persetujuan',
        'status_pengambilan',
        'status_pengembalian',
        'tanggal_disetujui',
        'tanggal_semua_diambil',
        'tanggal_selesai',
        'pengajuan_disetujui_oleh',
        'pengajuan_ditolak_oleh',
        'keterangan',
    ];

    public function peminjam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_peminjam');
    }

    public function pengajuanDisetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajuan_disetujui_oleh');
    }

    public function pengajuanDitolakOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajuan_ditolak_oleh');
    }

    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_peminjaman');
    }

    public function getAdaItemTerlambatAttribute(): bool
    {
        return $this->detailPeminjaman()
            ->where('status_pengembalian', 'dipinjam')
            ->where('tanggal_kembali', '<', now())
            ->exists();
    }

    public function getTotalBarangAttribute(): int
    {
        return $this->detailPeminjaman()->sum('jumlah_dipinjam');
    }

    // Update status persetujuan berdasarkan detail-detail peminjaman
    public function updateStatusPersetujuan(): void
    {
        $details = $this->detailPeminjaman;

        $totalItems = $details->count();
        $disetujui = $details->where('status_persetujuan', 'disetujui')->count();
        $ditolak = $details->where('status_persetujuan', 'ditolak')->count();

        if ($disetujui === $totalItems) {
            $this->status_persetujuan = 'disetujui';
            $this->tanggal_disetujui = now();
        } elseif ($ditolak === $totalItems) {
            $this->status_persetujuan = 'ditolak';
        } elseif ($disetujui > 0 || $ditolak > 0) {
            if ($disetujui > 0 && $ditolak > 0) {
                $this->status_persetujuan = 'sebagian_disetujui';
            } else {
                $this->status_persetujuan = 'diproses';
            }
        } else {
            $this->status_persetujuan = 'menunggu_verifikasi';
        }

        $this->save();
    }

    // Update status pengambilan berdasarkan detail-detail peminjaman
    public function updateStatusPengambilan(): void
    {
        $details = $this->detailPeminjaman()
            ->where('status_persetujuan', 'disetujui')
            ->get();

        if ($details->isEmpty()) {
            return;
        }

        $totalItems = $details->count();
        $sudahDiambil = $details->where('status_pengambilan', 'sudah_diambil')->count();
        $sebagianDiambil = $details->where('status_pengambilan', 'sebagian_diambil')->count();

        if ($sudahDiambil === $totalItems) {
            $this->status_pengambilan = 'sudah_diambil';
            $this->tanggal_semua_diambil = now();
        } elseif ($sudahDiambil > 0 || $sebagianDiambil > 0) {
            $this->status_pengambilan = 'sebagian_diambil';
        } else {
            $this->status_pengambilan = 'belum_diambil';
        }

        $this->save();
    }

    // Update status pengembalian berdasarkan detail-detail peminjaman
    public function updateStatusPengembalian(): void
    {
        $details = $this->detailPeminjaman()
            ->whereIn('status_pengambilan', ['sudah_diambil', 'sebagian_diambil'])
            ->get();

        if ($details->isEmpty()) {
            return;
        }

        $totalItems = $details->count();
        $dikembalikan = $details->whereIn('status_pengembalian', ['dikembalikan', 'rusak', 'hilang'])->count();

        if ($dikembalikan === $totalItems) {
            $this->status_pengembalian = 'sudah_dikembalikan';
            $this->tanggal_selesai = now();
        } elseif ($dikembalikan > 0) {
            $this->status_pengembalian = 'sebagian_dikembalikan';
        } else {
            $this->status_pengembalian = 'belum_dikembalikan';
        }

        $this->save();
    }
}
