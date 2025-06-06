<?php

// File: app/Models/Peminjaman.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Model Peminjaman merepresentasikan transaksi peminjaman barang oleh pengguna (guru).
 */
class Peminjaman extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atribut tanggal yang harus diperlakukan sebagai instance Carbon.
     * Digunakan untuk SoftDeletes.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'peminjamen'; // Menggunakan 'peminjamen' sesuai konvensi Laravel untuk tabel jamak

    /**
     * Kunci utama tabel.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Menunjukkan apakah ID model otomatis bertambah (incrementing).
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipe data dari kunci utama.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Menunjukkan apakah model harus menggunakan timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_guru',                      // Pengguna (guru) yang meminjam
        'tujuan_peminjaman',            // Tujuan dari peminjaman
        'tanggal_pengajuan',            // Tanggal pengajuan peminjaman
        'tanggal_disetujui',            // Tanggal peminjaman disetujui
        'tanggal_ditolak',              // Tanggal peminjaman ditolak
        'tanggal_semua_diambil',        // Tanggal semua barang dalam peminjaman ini diambil
        'tanggal_selesai',              // Tanggal semua barang dalam peminjaman ini dikembalikan
        'tanggal_harus_kembali',        // Batas waktu pengembalian
        'tanggal_rencana_pinjam',       // Rencana tanggal mulai meminjam
        'tanggal_rencana_kembali',      // Rencana tanggal pengembalian
        'tanggal_proses',               // Tanggal operator mulai memproses permintaan (opsional)
        'dapat_diperpanjang',           // Apakah peminjaman ini dapat diperpanjang
        'diperpanjang',                 // Apakah peminjaman ini sudah diperpanjang
        'catatan_operator',             // Catatan dari operator terkait peminjaman
        'catatan_peminjam',             // Catatan dari peminjam saat mengajukan
        'status',                       // Status peminjaman (enum)
        'id_ruangan_tujuan_peminjaman', // Ruangan tujuan penggunaan barang (jika relevan)
        'disetujui_oleh',               // Pengguna (admin/operator) yang menyetujui
        'ditolak_oleh',                 // Pengguna (admin/operator) yang menolak
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_pengajuan' => 'datetime',
        'tanggal_disetujui' => 'datetime',
        'tanggal_ditolak' => 'datetime',
        'tanggal_semua_diambil' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'tanggal_harus_kembali' => 'datetime',
        'tanggal_rencana_pinjam' => 'date', // Sesuai SQL dump
        'tanggal_rencana_kembali' => 'date', // Sesuai SQL dump
        'tanggal_proses' => 'datetime',
        'dapat_diperpanjang' => 'boolean',
        'diperpanjang' => 'boolean',
        'status' => 'string', // Enum
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Konstanta untuk nilai enum 'status' (sesuai SQL dump)
    public const STATUS_MENUNGGU_PERSETUJUAN = 'Menunggu Persetujuan';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DITOLAK = 'Ditolak';
    public const STATUS_SEDANG_DIPINJAM = 'Sedang Dipinjam';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_TERLAMBAT = 'Terlambat';
    public const STATUS_DIBATALKAN = 'Dibatalkan';
    public const STATUS_MENUNGGU_VERIFIKASI_KEMBALI = 'Menunggu Verifikasi Kembali';
    public const STATUS_SEBAGIAN_DIAJUKAN_KEMBALI = 'Sebagian Diajukan Kembali';

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai guru yang meminjam).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_guru');
    }

    /**
     * Alias untuk relasi `guru()`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function peminjam(): BelongsTo
    {
        return $this->guru();
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai pengguna yang menyetujui).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disetujuiOlehUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai pengguna yang menolak).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ditolakOlehUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditolak_oleh');
    }

    /**
     * Mendapatkan pengguna (operator/admin) yang memproses peminjaman (menyetujui atau menolak).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function operatorProses(): ?BelongsTo
    {
        // Prioritaskan yang menyetujui, jika tidak ada, fallback ke yang menolak.
        if ($this->disetujui_oleh) {
            return $this->disetujuiOlehUser();
        } elseif ($this->ditolak_oleh) {
            return $this->ditolakOlehUser();
        }
        return null; // Tidak ada yang memproses
    }


    /**
     * Mendefinisikan relasi BelongsTo ke model Ruangan (sebagai ruangan tujuan peminjaman).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruanganTujuanPeminjaman(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_tujuan_peminjaman');
    }

    /**
     * Mendefinisikan relasi HasMany ke model DetailPeminjaman.
     * Satu peminjaman dapat memiliki banyak detail item barang yang dipinjam.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_peminjaman');
    }

    /**
     * Accessor untuk mendapatkan status peminjaman.
     * Digunakan untuk kompatibilitas jika ada kode lama yang menggunakan `status_peminjaman`.
     *
     * @return string
     */
    public function getStatusPeminjamanAttribute(): string
    {
        return $this->status;
    }

    /**
     * Mutator untuk mengatur status peminjaman.
     * Digunakan untuk kompatibilitas jika ada kode lama yang menggunakan `status_peminjaman`.
     *
     * @param string $value Nilai status yang akan diatur.
     * @return void
     */
    public function setStatusPeminjamanAttribute($value): void
    {
        $this->attributes['status'] = $value;
    }

    /**
     * Accessor untuk memeriksa apakah ada item dalam peminjaman ini yang terlambat dikembalikan.
     *
     * @return bool True jika ada item terlambat, false jika tidak.
     */
    public function getAdaItemTerlambatAttribute(): bool
    {
        // Peminjaman dianggap terlambat jika statusnya 'Sedang Dipinjam'
        // dan tanggal saat ini melewati tanggal_harus_kembali.
        return $this->status === self::STATUS_SEDANG_DIPINJAM &&
            $this->tanggal_harus_kembali &&
            Carbon::now()->gt(Carbon::parse($this->tanggal_harus_kembali));
    }

    /**
     * Accessor untuk mendapatkan jumlah total unit barang yang terkait dengan peminjaman ini.
     *
     * @return int
     */
    public function getTotalBarangAttribute(): int
    {
        return $this->detailPeminjaman()->count();
    }

    /**
     * Memperbarui status peminjaman induk berdasarkan status agregat dari detail-detailnya.
     * Metode ini harus dipanggil setiap kali status sebuah DetailPeminjaman berubah.
     *
     * @return void
     */
    public function updateStatusPeminjaman(): void
    {
        $details = $this->detailPeminjaman()->get(); // Ambil semua detail terkait
        $totalDetails = $details->count();

        // Jika tidak ada detail sama sekali dan status belum dibatalkan, batalkan peminjaman.
        if ($totalDetails === 0 && $this->status !== self::STATUS_DIBATALKAN) {
            $this->status = self::STATUS_DIBATALKAN;
            $this->save();
            return;
        }
        // Jika tidak ada detail, tidak ada yang perlu diupdate lebih lanjut.
        if ($totalDetails === 0) return;

        // Hitung jumlah detail berdasarkan status unitnya
        $countDiambil = $details->where('status_unit', DetailPeminjaman::STATUS_ITEM_DIAMBIL)->count();
        $countDikembalikan = $details->where('status_unit', DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN)->count();
        $countRusakHilang = $details->whereIn('status_unit', [DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM, DetailPeminjaman::STATUS_ITEM_HILANG_SAAT_DIPINJAM])->count();
        $countDisetujui = $details->where('status_unit', DetailPeminjaman::STATUS_ITEM_DISETUJUI)->count();
        $countDiajukan = $details->where('status_unit', DetailPeminjaman::STATUS_ITEM_DIAJUKAN)->count();

        // Tentukan apakah semua item sudah selesai (dikembalikan atau rusak/hilang)
        $semuaSelesai = ($countDikembalikan + $countRusakHilang) === $totalDetails;

        $newStatus = $this->status; // Default ke status saat ini untuk menghindari perubahan yang tidak perlu

        if ($semuaSelesai) {
            $newStatus = self::STATUS_SELESAI;
            if (!$this->tanggal_selesai) { // Set tanggal selesai jika belum ada
                $this->tanggal_selesai = now();
            }
        } elseif ($countDiambil > 0 || $countDikembalikan > 0 || $countRusakHilang > 0) {
            // Jika ada item yang sudah diambil, atau sedang dalam proses pengembalian/rusak/hilang
            $newStatus = self::STATUS_SEDANG_DIPINJAM;
            if ($this->getAdaItemTerlambatAttribute()) { // Cek apakah terlambat
                $newStatus = self::STATUS_TERLAMBAT;
            }
        } elseif ($countDisetujui === $totalDetails && $countDiajukan === 0) {
            // Jika semua item sudah disetujui dan tidak ada lagi yang diajukan (menunggu diambil)
            $newStatus = self::STATUS_DISETUJUI;
        } elseif ($countDiajukan > 0) {
            // Jika masih ada item yang berstatus 'Diajukan'
            $newStatus = self::STATUS_MENUNGGU_PERSETUJUAN;
        }
        // Tambahkan logika lain jika diperlukan untuk status DITOLAK, SEBAGIAN_DIAJUKAN_KEMBALI, dll.
        // Misalnya, jika semua item ditolak, status Peminjaman menjadi DITOLAK.

        // Hanya simpan jika status berubah
        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->save();
        }
    }

    /**
     * Metode boot model untuk mendaftarkan event listener.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Saat Peminjaman baru dibuat, set tanggal pengajuan dan status default jika belum ada.
        static::creating(function ($peminjaman) {
            $peminjaman->tanggal_pengajuan = $peminjaman->tanggal_pengajuan ?? now();
            $peminjaman->status = $peminjaman->status ?? self::STATUS_MENUNGGU_PERSETUJUAN;
        });

        // Saat Peminjaman di-soft delete, semua DetailPeminjaman terkait juga di-soft delete.
        static::deleting(function ($peminjaman) {
            if (!$peminjaman->isForceDeleting()) {
                $peminjaman->detailPeminjaman()->each(function ($detail) {
                    $detail->delete();
                });
            }
        });

        // Saat Peminjaman di-restore dari soft delete, semua DetailPeminjaman terkait juga di-restore.
        static::restoring(function ($peminjaman) {
            $peminjaman->detailPeminjaman()->onlyTrashed()->each(function ($detail) {
                $detail->restore();
            });
        });
    }

    /**
     * Mendapatkan daftar nilai enum 'status' peminjaman yang valid.
     *
     * @return array<string>
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_MENUNGGU_PERSETUJUAN,
            self::STATUS_DISETUJUI,
            self::STATUS_DITOLAK,
            self::STATUS_SEDANG_DIPINJAM,
            self::STATUS_SELESAI,
            self::STATUS_TERLAMBAT,
            self::STATUS_DIBATALKAN,
            self::STATUS_MENUNGGU_VERIFIKASI_KEMBALI,
            self::STATUS_SEBAGIAN_DIAJUKAN_KEMBALI,
        ];
    }

    /**
     * Alias untuk `getValidStatuses()`.
     *
     * @return array<string>
     */
    public static function getPossibleStatuses(): array
    {
        return self::getValidStatuses();
    }

    // app/Models/Peminjaman.php
    public static function statusColor(string $status): string
    {
        return match (strtolower($status)) {
            strtolower(self::STATUS_MENUNGGU_PERSETUJUAN) => 'warning text-dark',
            strtolower(self::STATUS_DISETUJUI) => 'info',
            strtolower(self::STATUS_SEDANG_DIPINJAM) => 'primary',
            strtolower(self::STATUS_SELESAI) => 'success',
            strtolower(self::STATUS_DITOLAK) => 'danger',
            strtolower(self::STATUS_TERLAMBAT) => 'danger',
            strtolower(self::STATUS_DIBATALKAN) => 'secondary',
            strtolower(self::STATUS_MENUNGGU_VERIFIKASI_KEMBALI) => 'info',
            strtolower(self::STATUS_SEBAGIAN_DIAJUKAN_KEMBALI) => 'primary',
            default => 'light text-dark',
        };
    }
}
