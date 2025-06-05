<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pemeliharaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $table = 'pemeliharaans';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_barang_qr_code',
        'id_user_pengaju',
        'tanggal_pengajuan',
        'deskripsi_kerusakan', // Pastikan field ini ada di tabel Anda jika digunakan
        'prioritas',
        'status_pengajuan',
        'catatan_pengajuan',
        'id_user_penyetuju',
        'tanggal_persetujuan',
        'catatan_persetujuan',
        'id_operator_pengerjaan',
        'tanggal_mulai_pengerjaan',
        'tanggal_selesai_pengerjaan',
        'deskripsi_pekerjaan',
        'biaya',
        'status_pengerjaan',
        'hasil_pemeliharaan',
        'catatan_pengerjaan',
        'kondisi_barang_setelah_pemeliharaan',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'datetime',
        'tanggal_persetujuan' => 'datetime',
        'tanggal_mulai_pengerjaan' => 'datetime',
        'tanggal_selesai_pengerjaan' => 'datetime',
        'biaya' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'status_pengajuan' => 'string',
        'status_pengerjaan' => 'string',
        'prioritas' => 'string',
    ];

    // Konstanta Status Pengajuan
    public const STATUS_PENGAJUAN_DIAJUKAN = 'Diajukan';
    public const STATUS_PENGAJUAN_DISETUJUI = 'Disetujui';
    public const STATUS_PENGAJUAN_DITOLAK = 'Ditolak';
    public const STATUS_PENGAJUAN_DIBATALKAN = 'Dibatalkan';

    // Konstanta Status Pengerjaan
    public const STATUS_PENGERJAAN_BELUM_DIKERJAKAN = 'Belum Dikerjakan';
    public const STATUS_PENGERJAAN_SEDANG_DILAKUKAN = 'Sedang Dilakukan';
    public const STATUS_PENGERJAAN_SELESAI = 'Selesai';
    public const STATUS_PENGERJAAN_GAGAL = 'Gagal';
    public const STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI = 'Tidak Dapat Diperbaiki';
    public const STATUS_PENGERJAAN_DITUNDA = 'Ditunda';

    // Konstanta Prioritas
    public const PRIORITAS_RENDAH = 'rendah';
    public const PRIORITAS_SEDANG = 'sedang';
    public const PRIORITAS_TINGGI = 'tinggi';

    public function barangQrCode(): BelongsTo
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pengaju')->withTrashed();
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_penyetuju')->withTrashed();
    }

    public function operatorPengerjaan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_operator_pengerjaan')->withTrashed();
    }

    public function getStatusPemeliharaanAttribute(): string
    {
        if ($this->trashed()) {
            return 'Diarsipkan';
        }
        if ($this->status_pengajuan === self::STATUS_PENGAJUAN_DISETUJUI && $this->status_pengerjaan) {
            return $this->status_pengerjaan;
        }
        return $this->status_pengajuan;
    }

    /**
     * Mendapatkan daftar status pengajuan yang valid sebagai array asosiatif.
     */
    public static function getValidStatusPengajuan($associative = true): array
    {
        $statuses = [
            self::STATUS_PENGAJUAN_DIAJUKAN,
            self::STATUS_PENGAJUAN_DISETUJUI,
            self::STATUS_PENGAJUAN_DITOLAK,
            self::STATUS_PENGAJUAN_DIBATALKAN,
        ];
        if ($associative) {
            return array_combine($statuses, $statuses); // ['Diajukan' => 'Diajukan', ...]
        }
        return $statuses;
    }

    /**
     * Mendapatkan daftar status pengerjaan yang valid sebagai array asosiatif.
     */
    public static function getValidStatusPengerjaan($associative = true): array
    {
        $statuses = [
            self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN,
            self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN,
            self::STATUS_PENGERJAAN_SELESAI,
            self::STATUS_PENGERJAAN_GAGAL,
            self::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI,
            self::STATUS_PENGERJAAN_DITUNDA,
        ];
        if ($associative) {
            return array_combine($statuses, $statuses); // ['Belum Dikerjakan' => 'Belum Dikerjakan', ...]
        }
        return $statuses;
    }

    /**
     * Mendapatkan daftar prioritas yang valid sebagai array asosiatif.
     */
    public static function getValidPrioritas(): array
    {
        return [
            self::PRIORITAS_RENDAH => 'Rendah',
            self::PRIORITAS_SEDANG => 'Sedang',
            self::PRIORITAS_TINGGI => 'Tinggi',
        ];
    }

    /**
     * Menggabungkan semua status untuk digunakan dalam filter dropdown.
     */
    public static function getStatusListForFilter(): array
    {
        // Pastikan key yang dihasilkan unik atau value tidak duplikat
        return array_merge(self::getValidStatusPengajuan(), self::getValidStatusPengerjaan());
    }

    /**
     * Menentukan kelas warna badge Bootstrap berdasarkan status.
     */
    public static function statusColor(string $status): string
    {
        if (strtolower($status) === 'diarsipkan') {
            return 'dark';
        }
        // Menggunakan strtolower pada value konstanta untuk perbandingan yang aman
        return match (strtolower($status)) {
            strtolower(self::STATUS_PENGAJUAN_DIAJUKAN) => 'info',
            strtolower(self::STATUS_PENGAJUAN_DISETUJUI) => 'primary',
            strtolower(self::STATUS_PENGAJUAN_DITOLAK) => 'danger',
            strtolower(self::STATUS_PENGAJUAN_DIBATALKAN) => 'secondary',
            strtolower(self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN) => 'warning text-dark',
            strtolower(self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN) => 'info',
            strtolower(self::STATUS_PENGERJAAN_SELESAI) => 'success',
            strtolower(self::STATUS_PENGERJAAN_GAGAL) => 'danger',
            strtolower(self::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI) => 'dark',
            strtolower(self::STATUS_PENGERJAAN_DITUNDA) => 'secondary',
            default => 'light text-dark',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pemeliharaan) {
            $pemeliharaan->tanggal_pengajuan = $pemeliharaan->tanggal_pengajuan ?? now();
            $pemeliharaan->status_pengajuan = $pemeliharaan->status_pengajuan ?? self::STATUS_PENGAJUAN_DIAJUKAN;
            $pemeliharaan->status_pengerjaan = $pemeliharaan->status_pengerjaan ?? self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN;
            $pemeliharaan->prioritas = $pemeliharaan->prioritas ?? self::PRIORITAS_SEDANG;
        });

        // Event listener 'saved' dan 'deleted' dari kode Anda sebelumnya.
        // Pastikan logika ini sesuai dan tidak konflik dengan apa yang dilakukan di controller.
        // Jika controller sudah menangani update status BarangQrCode dan BarangStatus,
        // event listener ini mungkin perlu disesuaikan atau dinonaktifkan untuk menghindari duplikasi.
        static::saved(function ($pemeliharaan) {
            $barang = $pemeliharaan->barangQrCode;
            if (!$barang) return;

            $kondisiSebelum = $barang->getOriginal('kondisi'); // Ambil kondisi sebelum save barang jika ada
            $statusKetersediaanSebelum = $barang->getOriginal('status'); // Ambil status sebelum save barang jika ada

            // Jika tidak ada nilai original (misal barang baru), gunakan nilai saat ini sebelum potensi perubahan
            if (is_null($kondisiSebelum)) $kondisiSebelum = $barang->kondisi;
            if (is_null($statusKetersediaanSebelum)) $statusKetersediaanSebelum = $barang->status;

            $perluSimpanBarang = false;
            $perluCatatDiBarangStatus = false;

            if (
                $pemeliharaan->status_pengajuan === self::STATUS_PENGAJUAN_DISETUJUI &&
                ($pemeliharaan->status_pengerjaan === self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN || $pemeliharaan->status_pengerjaan === self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN)
            ) {
                if ($barang->status !== BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
                    $barang->status = BarangQrCode::STATUS_DALAM_PEMELIHARAAN;
                    $perluSimpanBarang = true;
                }
                if ($barang->kondisi === BarangQrCode::KONDISI_BAIK) { // Hanya ubah jika KONDISI_BAIK
                    $barang->kondisi = BarangQrCode::KONDISI_KURANG_BAIK;
                    $perluSimpanBarang = true;
                }
            } elseif ($pemeliharaan->status_pengerjaan === self::STATUS_PENGERJAAN_SELESAI) {
                // Gunakan kondisi dari field 'kondisi_barang_setelah_pemeliharaan' jika ada, jika tidak default ke BAIK
                $kondisiBaru = $pemeliharaan->kondisi_barang_setelah_pemeliharaan ?? BarangQrCode::KONDISI_BAIK;

                if ($barang->status !== BarangQrCode::STATUS_TERSEDIA || $barang->kondisi !== $kondisiBaru) {
                    $barang->status = BarangQrCode::STATUS_TERSEDIA;
                    $barang->kondisi = $kondisiBaru;
                    $perluSimpanBarang = true;
                }
            } elseif ($pemeliharaan->status_pengerjaan === self::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI) {
                $kondisiBaru = $pemeliharaan->kondisi_barang_setelah_pemeliharaan ?? BarangQrCode::KONDISI_RUSAK_BERAT; // Default ke Rusak Berat
                if ($barang->status !== BarangQrCode::STATUS_TERSEDIA || $barang->kondisi !== $kondisiBaru) {
                    $barang->status = BarangQrCode::STATUS_TERSEDIA; // Barang tetap tersedia meskipun rusak berat (untuk keputusan lanjut)
                    $barang->kondisi = $kondisiBaru;
                    $perluSimpanBarang = true;
                }
            } elseif ($pemeliharaan->status_pengajuan === self::STATUS_PENGAJUAN_DITOLAK || $pemeliharaan->status_pengajuan === self::STATUS_PENGAJUAN_DIBATALKAN) {
                if ($barang->status === BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
                    $adaPemeliharaanAktifLain = Pemeliharaan::where('id_barang_qr_code', $barang->id)
                        ->where('id', '!=', $pemeliharaan->id)
                        ->where('status_pengajuan', self::STATUS_PENGAJUAN_DISETUJUI)
                        ->whereIn('status_pengerjaan', [self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN, self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN])
                        ->whereNull('deleted_at') // Hanya yang tidak diarsipkan
                        ->exists();
                    if (!$adaPemeliharaanAktifLain) {
                        $barang->status = BarangQrCode::STATUS_TERSEDIA;
                        // Kembalikan kondisi barang ke kondisi sebelum pemeliharaan ini jika memungkinkan
                        $logStatusAwal = BarangStatus::where('id_barang_qr_code', $barang->id)
                            ->where('id_pemeliharaan_trigger', $pemeliharaan->id)
                            ->orderBy('tanggal_pencatatan', 'asc')
                            ->first();
                        if ($logStatusAwal && $logStatusAwal->kondisi_sebelumnya) {
                            $barang->kondisi = $logStatusAwal->kondisi_sebelumnya;
                        }
                        $perluSimpanBarang = true;
                    }
                }
            }

            if ($perluSimpanBarang) {
                $barang->saveQuietly(); // Gunakan saveQuietly untuk mencegah trigger event lain jika ada
                $perluCatatDiBarangStatus = true;
            }

            // Selalu catat di BarangStatus jika ada perubahan kondisi atau status pada barang,
            // atau jika $perluCatatDiBarangStatus di-set true karena perubahan status pemeliharaan yang signifikan.
            if ($perluCatatDiBarangStatus || $kondisiSebelum !== $barang->kondisi || $statusKetersediaanSebelum !== $barang->status) {
                BarangStatus::create([
                    'id_barang_qr_code' => $barang->id,
                    'id_user_pencatat' => Auth::id() ?? $pemeliharaan->id_operator_pengerjaan ?? $pemeliharaan->id_user_penyetuju ?? $pemeliharaan->id_user_pengaju,
                    'tanggal_pencatatan' => now(),
                    'kondisi_sebelumnya' => $kondisiSebelum,
                    'kondisi_sesudahnya' => $barang->kondisi,
                    'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum,
                    'status_ketersediaan_sesudahnya' => $barang->status,
                    'deskripsi_kejadian' => 'Perubahan status/kondisi akibat pemeliharaan ID: ' . $pemeliharaan->id . '. Pengajuan: ' . $pemeliharaan->status_pengajuan . ', Pengerjaan: ' . $pemeliharaan->status_pengerjaan,
                    'id_pemeliharaan_trigger' => $pemeliharaan->id,
                ]);
            }
        });

        static::deleted(function ($pemeliharaan) {
            if (!$pemeliharaan->isForceDeleting() && $pemeliharaan->barangQrCode) {
                $barang = $pemeliharaan->barangQrCode;
                if ($barang->status === BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
                    $adaPemeliharaanAktifLain = Pemeliharaan::where('id_barang_qr_code', $barang->id)
                        ->where('status_pengajuan', self::STATUS_PENGAJUAN_DISETUJUI)
                        ->whereIn('status_pengerjaan', [self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN, self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN])
                        ->whereNull('deleted_at') // Pastikan hanya menghitung yang belum di-soft delete
                        ->exists();

                    if (!$adaPemeliharaanAktifLain) {
                        $statusKetersediaanSebelum = $barang->status;
                        $kondisiSebelum = $barang->kondisi;

                        $barang->status = BarangQrCode::STATUS_TERSEDIA;
                        // Kembalikan kondisi barang
                        $logStatusAwal = BarangStatus::where('id_barang_qr_code', $barang->id)
                            ->where('id_pemeliharaan_trigger', $pemeliharaan->id)
                            ->orderBy('tanggal_pencatatan', 'asc')
                            ->first();
                        if ($logStatusAwal && $logStatusAwal->kondisi_sebelumnya) {
                            $barang->kondisi = $logStatusAwal->kondisi_sebelumnya;
                        }

                        $barang->saveQuietly();

                        BarangStatus::create([
                            'id_barang_qr_code' => $barang->id,
                            'id_user_pencatat' => Auth::id() ?? $pemeliharaan->id_user_pengaju,
                            'tanggal_pencatatan' => now(),
                            'kondisi_sebelumnya' => $kondisiSebelum,
                            'kondisi_sesudahnya' => $barang->kondisi,
                            'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum,
                            'status_ketersediaan_sesudahnya' => $barang->status,
                            'deskripsi_kejadian' => 'Pemeliharaan ID: ' . $pemeliharaan->id . ' diarsipkan/dibatalkan, barang kembali tersedia.',
                            'id_pemeliharaan_trigger' => $pemeliharaan->id,
                        ]);
                    }
                }
            }
        });
    }
}
