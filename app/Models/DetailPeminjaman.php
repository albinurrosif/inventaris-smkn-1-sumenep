<?php

// File: app/Models/DetailPeminjaman.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Model DetailPeminjaman merepresentasikan setiap item barang yang dipinjam dalam sebuah transaksi Peminjaman.
 */
class DetailPeminjaman extends Model
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
    protected $table = 'detail_peminjaman';

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
        'id_peminjaman',        // FK ke tabel peminjamen (induk transaksi peminjaman)
        'id_barang_qr_code',    // FK ke tabel barang_qr_codes (unit barang yang dipinjam)
        'kondisi_sebelum',      // Kondisi barang saat diambil
        'kondisi_setelah',      // Kondisi barang saat dikembalikan
        'tanggal_diambil',      // Tanggal barang diambil oleh peminjam
        'tanggal_dikembalikan', // Tanggal barang dikembalikan
        'catatan_unit',         // Catatan spesifik untuk unit barang ini dalam peminjaman
        'status_unit',          // Status item peminjaman ini (Diajukan, Disetujui, Diambil, dll.)
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_diambil' => 'datetime',
        'tanggal_dikembalikan' => 'datetime',
        'status_unit' => 'string', // Enum
        'kondisi_sebelum' => 'string', // Enum
        'kondisi_setelah' => 'string', // Enum
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Konstanta untuk nilai enum 'status_unit' (sesuai SQL dump)
    public const STATUS_ITEM_DIAJUKAN = 'Diajukan';
    public const STATUS_ITEM_DISETUJUI = 'Disetujui';
    public const STATUS_ITEM_DITOLAK = 'Ditolak';
    public const STATUS_ITEM_DIAMBIL = 'Diambil';
    public const STATUS_ITEM_DIKEMBALIKAN = 'Dikembalikan';
    public const STATUS_ITEM_RUSAK_SAAT_DIPINJAM = 'Rusak Saat Dipinjam';
    public const STATUS_ITEM_HILANG_SAAT_DIPINJAM = 'Hilang Saat Dipinjam';

    /**
     * Mendefinisikan relasi BelongsTo ke model Peminjaman (induk).
     * Satu detail peminjaman dimiliki oleh satu transaksi peminjaman.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model BarangQrCode (unit fisik yang dipinjam).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function barangQrCode(): BelongsTo
    {
        // PENYESUAIAN: Tambahkan withTrashed()
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code')->withTrashed();
    }

    /**
     * Accessor untuk memeriksa apakah item peminjaman ini terlambat dikembalikan.
     *
     * @return bool True jika terlambat, false jika tidak.
     */
    public function getTerlambatAttribute(): bool
    {
        // Item dianggap terlambat jika statusnya 'Diambil' atau 'Rusak Saat Dipinjam' (belum 'Dikembalikan')
        // DAN tanggal saat ini melewati tanggal_harus_kembali dari peminjaman induk.
        return in_array($this->status_unit, [self::STATUS_ITEM_DIAMBIL, self::STATUS_ITEM_RUSAK_SAAT_DIPINJAM]) &&
            $this->peminjaman && // Pastikan relasi peminjaman ada
            $this->peminjaman->tanggal_harus_kembali &&
            Carbon::now()->gt(Carbon::parse($this->peminjaman->tanggal_harus_kembali));
    }

    /**
     * Accessor untuk mendapatkan jumlah hari keterlambatan pengembalian item ini.
     *
     * @return int Jumlah hari keterlambatan.
     */
    public function getJumlahHariTerlambatAttribute(): int
    {
        if (!$this->terlambat || !$this->peminjaman) {
            return 0;
        }
        // Hitung selisih hari dari tanggal_harus_kembali peminjaman induk ke tanggal saat ini.
        return Carbon::parse($this->peminjaman->tanggal_harus_kembali)->diffInDays(Carbon::now());
    }

    /**
     * Menyetujui item peminjaman ini.
     *
     * @param int $userId ID pengguna yang melakukan aksi (untuk logging atau catatan).
     * @return void
     * @throws \Exception Jika item tidak dalam status 'Diajukan'.
     */
    public function setujui(int $userId): void
    {
        if ($this->status_unit !== self::STATUS_ITEM_DIAJUKAN) {
            throw new \Exception("Item tidak dalam status 'Diajukan' untuk disetujui.");
        }
        $this->status_unit = self::STATUS_ITEM_DISETUJUI;
        $this->save();

        // Setelah status item diubah, update status peminjaman induknya.
        if ($this->peminjaman) {
            $this->peminjaman->updateStatusPeminjaman();
        }
    }

    /**
     * Menolak item peminjaman ini.
     * Logika penolakan mungkin perlu disesuaikan (misal, soft delete item atau set status khusus 'Ditolak').
     *
     * @param int $userId ID pengguna yang melakukan aksi.
     * @return void
     * @throws \Exception Jika item tidak dalam status yang valid untuk ditolak.
     */
    public function tolak(int $userId): void
    {
        if ($this->status_unit !== self::STATUS_ITEM_DIAJUKAN && $this->status_unit !== self::STATUS_ITEM_DISETUJUI) {
            throw new \Exception("Item tidak dapat ditolak dari status saat ini.");
        }
        // Logika penolakan: misalnya, jika item ditolak, ia tidak akan diproses lebih lanjut.
        // Anda bisa menambahkan status 'Ditolak' ke enum status_unit jika diperlukan.
        // Untuk saat ini, kita tidak mengubah status_unit secara spesifik menjadi 'Ditolak'
        // karena tidak ada di enum DB, tapi Peminjaman induk akan diupdate.
        // $this->status_unit = 'Ditolak'; // Jika ada enum 'Ditolak'
        // $this->save();

        if ($this->peminjaman) {
            $this->peminjaman->updateStatusPeminjaman();
        }
    }

    /**
     * Mengonfirmasi pengambilan unit barang oleh peminjam.
     * Mengubah status item menjadi 'Diambil' dan status unit barang (BarangQrCode) menjadi 'Dipinjam'.
     *
     * @param int $userId ID pengguna yang melakukan konfirmasi.
     * @return void
     * @throws \Exception Jika item tidak dalam status 'Disetujui' atau barang tidak ditemukan.
     */
    public function konfirmasiPengambilan(int $userId): void
    {
        if ($this->status_unit !== self::STATUS_ITEM_DISETUJUI) {
            throw new \Exception("Item tidak dalam status 'Disetujui' untuk diambil.");
        }

        $barang = $this->barangQrCode;
        if (!$barang) {
            throw new \Exception("Barang tidak ditemukan untuk detail peminjaman ini.");
        }

        $statusKetersediaanSebelum = $barang->status; // Simpan status sebelum diubah

        $this->tanggal_diambil = now();
        $this->status_unit = self::STATUS_ITEM_DIAMBIL;
        $this->kondisi_sebelum = $barang->kondisi; // Catat kondisi barang saat diambil
        $this->save();

        // Update status BarangQrCode
        $barang->status = BarangQrCode::STATUS_DIPINJAM;
        $barang->save();

        // Catat perubahan status di BarangStatus
        BarangStatus::create([
            'id_barang_qr_code' => $barang->id,
            'id_user_pencatat' => $userId,
            'tanggal_pencatatan' => now(),
            'kondisi_sebelumnya' => $this->kondisi_sebelum, // Kondisi sebelum diambil
            'kondisi_sesudahnya' => $barang->kondisi,   // Kondisi tidak berubah saat diambil
            'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum, // Biasanya 'Tersedia'
            'status_ketersediaan_sesudahnya' => BarangQrCode::STATUS_DIPINJAM,
            'deskripsi_kejadian' => 'Barang diambil untuk peminjaman. Peminjaman ID: ' . $this->id_peminjaman . ', Detail ID: ' . $this->id,
            'id_detail_peminjaman_trigger' => $this->id,
        ]);

        if ($this->peminjaman) {
            $this->peminjaman->updateStatusPeminjaman();
            // Cek apakah semua item dalam peminjaman ini sudah diambil
            $semuaDiambil = $this->peminjaman->detailPeminjaman()->where('status_unit', '!=', self::STATUS_ITEM_DIAMBIL)->doesntExist();
            if ($semuaDiambil && !$this->peminjaman->tanggal_semua_diambil) {
                $this->peminjaman->tanggal_semua_diambil = now();
                $this->peminjaman->save();
            }
        }
    }

    /**
     * Memverifikasi pengembalian unit barang.
     * Mengubah status item dan status unit barang (BarangQrCode) berdasarkan kondisi aktual.
     *
     * @param int $userId ID pengguna yang melakukan verifikasi.
     * @param string $kondisiAktual Kondisi aktual barang saat dikembalikan (dari enum BarangQrCode::KONDISI_*).
     * @param string|null $catatan Catatan tambahan terkait pengembalian.
     * @return void
     * @throws \Exception Jika item tidak dalam status yang valid untuk diverifikasi atau barang tidak ditemukan.
     * @throws \InvalidArgumentException Jika kondisi aktual tidak valid.
     */
    public function verifikasiPengembalian(int $userId, string $kondisiAktual, ?string $catatan = null): void
    {
        if (!in_array($this->status_unit, [self::STATUS_ITEM_DIAMBIL, self::STATUS_ITEM_RUSAK_SAAT_DIPINJAM])) {
            throw new \Exception("Item tidak dalam status untuk diverifikasi pengembalian.");
        }
        if (!in_array($kondisiAktual, BarangQrCode::getValidKondisi())) {
            throw new \InvalidArgumentException("Kondisi aktual tidak valid: {$kondisiAktual}");
        }

        $barang = $this->barangQrCode;
        if (!$barang) {
            throw new \Exception("Barang tidak ditemukan untuk detail peminjaman ini.");
        }

        $kondisiSebelumPengembalian = $barang->kondisi;
        $statusKetersediaanSebelumPengembalian = $barang->status;

        $this->tanggal_dikembalikan = now();
        $this->kondisi_setelah = $kondisiAktual;
        $this->catatan_unit = $catatan;

        if ($kondisiAktual === BarangQrCode::KONDISI_BAIK || $kondisiAktual === BarangQrCode::KONDISI_KURANG_BAIK) {
            $this->status_unit = self::STATUS_ITEM_DIKEMBALIKAN;
            $barang->status = BarangQrCode::STATUS_TERSEDIA;
            $barang->kondisi = $kondisiAktual;
        } elseif ($kondisiAktual === BarangQrCode::KONDISI_RUSAK_BERAT) {
            $this->status_unit = self::STATUS_ITEM_RUSAK_SAAT_DIPINJAM;
            $barang->status = BarangQrCode::STATUS_DALAM_PEMELIHARAAN;
            $barang->kondisi = BarangQrCode::KONDISI_RUSAK_BERAT;
            // Opsional: Buat entri Pemeliharaan baru secara otomatis
            Pemeliharaan::create([
                'id_barang_qr_code' => $barang->id,
                'id_user_pengaju' => $userId,
                'tanggal_pengajuan' => now(),
                'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN,
                'catatan_pengajuan' => 'Ditemukan rusak saat pengembalian peminjaman ID: ' . $this->id_peminjaman . ', Detail ID: ' . $this->id,
                'deskripsi_pekerjaan' => 'Perbaikan kerusakan akibat peminjaman.',
            ]);
        } elseif ($kondisiAktual === BarangQrCode::KONDISI_HILANG) {
            $this->status_unit = self::STATUS_ITEM_HILANG_SAAT_DIPINJAM;
            $barang->kondisi = BarangQrCode::KONDISI_HILANG;
            $barang->status = BarangQrCode::STATUS_DIARSIPKAN;

            // $barang->status = 'Hilang'; // Tidak ada status 'Hilang' di enum BarangQrCode, barang akan di soft delete
            $barang->save(); // Simpan kondisi hilang
            $barang->delete(); // Soft delete barangnya

            // Buat entri ArsipBarang
            ArsipBarang::create([
                'id_barang_qr_code' => $barang->id,
                'id_user_pengaju' => $userId,
                'jenis_penghapusan' => 'Hilang',
                'alasan_penghapusan' => 'Hilang saat peminjaman ID: ' . $this->id_peminjaman . ', Detail ID: ' . $this->id,
                'tanggal_pengajuan_arsip' => now(),
                'status_arsip' => 'Diajukan', // Atau langsung 'Diarsipkan Permanen'
                'data_unit_snapshot' => $barang->toArray(),
            ]);
        }
        $barang->save(); // Simpan perubahan pada barang
        $this->save();   // Simpan perubahan pada detail peminjaman

        // Catat perubahan status di BarangStatus
        BarangStatus::create([
            'id_barang_qr_code' => $barang->id,
            'id_user_pencatat' => $userId,
            'tanggal_pencatatan' => now(),
            'kondisi_sebelumnya' => $kondisiSebelumPengembalian,
            'kondisi_sesudahnya' => $barang->kondisi,
            'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelumPengembalian,
            'status_ketersediaan_sesudahnya' => $barang->trashed() ? null : $barang->status, // Jika trashed, status ketersediaan tidak relevan
            'deskripsi_kejadian' => 'Barang dikembalikan dari peminjaman. Peminjaman ID: ' . $this->id_peminjaman . ', Detail ID: ' . $this->id . '. Kondisi Setelah: ' . $kondisiAktual . '. Catatan: ' . ($catatan ?? '-'),
            'id_detail_peminjaman_trigger' => $this->id,
        ]);

        if ($this->peminjaman) {
            $this->peminjaman->updateStatusPeminjaman();
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

        // Saat DetailPeminjaman baru dibuat, set status_unit default jika belum ada.
        static::creating(function ($detailPeminjaman) {
            $detailPeminjaman->status_unit = $detailPeminjaman->status_unit ?? self::STATUS_ITEM_DIAJUKAN;
        });

        // Saat DetailPeminjaman disimpan (baik create maupun update), update status Peminjaman induk.
        // static::saved(function ($detailPeminjaman) {
        //     if ($detailPeminjaman->peminjaman) {
        //         $detailPeminjaman->peminjaman->updateStatusPeminjaman();
        //     }
        // });

        // Saat DetailPeminjaman di-soft delete, update status Peminjaman induk.
        static::deleted(function ($detailPeminjaman) {
            if ($detailPeminjaman->peminjaman) {
                $detailPeminjaman->peminjaman->updateStatusPeminjaman();
            }
        });

        // Saat DetailPeminjaman di-restore, update status Peminjaman induk.
        static::restored(function ($detailPeminjaman) {
            if ($detailPeminjaman->peminjaman) {
                $detailPeminjaman->peminjaman->updateStatusPeminjaman();
            }
        });
    }

    /**
     * Mendapatkan daftar nilai enum 'status_unit' yang valid.
     *
     * @return array<string>
     */
    public static function getValidStatusUnit(): array
    {
        return [
            self::STATUS_ITEM_DIAJUKAN,
            self::STATUS_ITEM_DISETUJUI,
            self::STATUS_ITEM_DITOLAK,
            self::STATUS_ITEM_DIAMBIL,
            self::STATUS_ITEM_DIKEMBALIKAN,
            self::STATUS_ITEM_RUSAK_SAAT_DIPINJAM,
            self::STATUS_ITEM_HILANG_SAAT_DIPINJAM,
        ];
    }
    /**
     * Helper untuk mendapatkan kelas warna Bootstrap berdasarkan status unit peminjaman.
     *
     * @param string $status
     * @return string
     */
    public static function statusColor(string $status): string
    {
        return match (strtolower($status)) {
            strtolower(self::STATUS_ITEM_DIAJUKAN) => 'text-bg-info',
            strtolower(self::STATUS_ITEM_DISETUJUI) => 'text-bg-primary',
            strtolower(self::STATUS_ITEM_DITOLAK) => 'text-bg-danger',
            strtolower(self::STATUS_ITEM_DIAMBIL) => 'text-bg-primary',
            strtolower(self::STATUS_ITEM_DIKEMBALIKAN) => 'text-bg-success',
            strtolower(self::STATUS_ITEM_RUSAK_SAAT_DIPINJAM) => 'text-bg-warning text-dark',
            strtolower(self::STATUS_ITEM_HILANG_SAAT_DIPINJAM) => 'text-bg-dark',
            default => 'text-bg-secondary',
        };
    }
}
