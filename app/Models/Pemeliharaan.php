<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth; // Digunakan jika ada interaksi user login default
use Illuminate\Support\Facades\Log; // Untuk logging internal jika diperlukan

class Pemeliharaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $table = 'pemeliharaans';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    // Di dalam file: app/Models/Pemeliharaan.php

    protected $fillable = [
        // Kolom-kolom dari migrasi awal Anda
        'id_barang_qr_code',
        'id_user_pengaju',
        'tanggal_pengajuan',
        'status_pengajuan',
        'catatan_pengajuan',
        'id_user_penyetuju',
        'tanggal_persetujuan',
        'catatan_persetujuan',
        'foto_kerusakan_path',
        'id_operator_pengerjaan',
        'tanggal_mulai_pengerjaan',
        'tanggal_selesai_pengerjaan',
        'deskripsi_pekerjaan',
        'biaya',
        'status_pengerjaan',
        'prioritas',
        'hasil_pemeliharaan',
        'kondisi_barang_setelah_pemeliharaan',
        'catatan_pengerjaan',
        'foto_perbaikan_path',

        // ====================================================
        //      KOLOM-KOLOM BARU YANG WAJIB DITAMBAHKAN
        // ====================================================
        'kondisi_saat_lapor',
        'status_saat_lapor',
        'tanggal_tuntas',
        'foto_tuntas_path',
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
        'prioritas' => 'string', // Enum disimpan sebagai string
        'kondisi_barang_setelah_pemeliharaan' => 'string', // Bisa jadi enum juga jika nilainya terbatas
        'tanggal_tuntas' => 'datetime',
    ];

    // Konstanta untuk Status Terpadu (User-Facing)
    public const STATUS_DIAJUKAN = 'Diajukan';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DITOLAK = 'Ditolak';
    public const STATUS_DALAM_PERBAIKAN = 'Dalam Perbaikan';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_TUNTAS = 'Tuntas'; // Bisa kita aktifkan nanti


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
    public const STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI = 'Tidak Dapat Diperbaiki'; // Anda memiliki ini di model sebelumnya
    public const STATUS_PENGERJAAN_DITUNDA = 'Ditunda';

    // Konstanta Prioritas
    public const PRIORITAS_RENDAH = 'rendah';
    public const PRIORITAS_SEDANG = 'sedang';
    public const PRIORITAS_TINGGI = 'tinggi';


    // --- RELATIONS ---
    public function barangQrCode(): BelongsTo
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code')->withTrashed(); // withTrashed jika barangnya diarsip
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

    // --- ACCESSORS & MUTATORS ---
    /**
     * Accessor untuk mendapatkan "STATUS VIRTUAL" yang terpadu.
     * Ini adalah inti dari solusi kita.
     */
    public function getStatusAttribute(): string
    {
        // PRIORITAS 1: Jika tanggal tuntas sudah ada, maka statusnya final Tuntas.
        if ($this->tanggal_tuntas) {
            return self::STATUS_TUNTAS;
        }

        // PRIORITAS 2: Jika ditolak, statusnya selalu Ditolak.
        if ($this->status_pengajuan === self::STATUS_PENGAJUAN_DITOLAK || $this->status_pengajuan === self::STATUS_PENGAJUAN_DIBATALKAN) {
            return self::STATUS_DITOLAK;
        }

        // PRIORITAS 3: Jika pengerjaan sudah Selesai (tapi belum Tuntas)
        if ($this->status_pengerjaan === self::STATUS_PENGERJAAN_SELESAI || $this->tanggal_selesai_pengerjaan) {
            return self::STATUS_SELESAI;
        }

        // PRIORITAS 4: Jika sedang dikerjakan.
        if ($this->status_pengerjaan === self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN || $this->tanggal_mulai_pengerjaan) {
            return self::STATUS_DALAM_PERBAIKAN;
        }

        // PRIORITAS 5: Jika pengajuan sudah disetujui.
        if ($this->status_pengajuan === self::STATUS_PENGAJUAN_DISETUJUI) {
            return self::STATUS_DISETUJUI;
        }

        // Default: Jika tidak memenuhi semua kondisi di atas, berarti statusnya masih Diajukan.
        return self::STATUS_DIAJUKAN;
    }

    // --- STATIC METHODS FOR ENUM VALUES ---
    /**
     * Mendapatkan daftar status pengajuan yang valid.
     * @param bool $associative Jika true, kembalikan array asosiatif (key => value). Jika false, kembalikan array biasa.
     */
    public static function getValidStatusPengajuan(bool $associative = true): array
    {
        $statuses = [
            self::STATUS_PENGAJUAN_DIAJUKAN,
            self::STATUS_PENGAJUAN_DISETUJUI,
            self::STATUS_PENGAJUAN_DITOLAK,
            self::STATUS_PENGAJUAN_DIBATALKAN,
        ];
        return $associative ? array_combine($statuses, $statuses) : $statuses;
    }

    /**
     * Mendapatkan daftar status pengerjaan yang valid.
     */
    public static function getValidStatusPengerjaan(bool $associative = true): array
    {
        $statuses = [
            self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN,
            self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN,
            self::STATUS_PENGERJAAN_SELESAI,
            self::STATUS_PENGERJAAN_GAGAL,
            self::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI,
            self::STATUS_PENGERJAAN_DITUNDA,
        ];
        return $associative ? array_combine($statuses, $statuses) : $statuses;
    }

    /**
     * Mendapatkan daftar prioritas yang valid.
     */
    public static function getValidPrioritas(bool $associative = true): array
    {
        $priorities = [
            self::PRIORITAS_RENDAH,
            self::PRIORITAS_SEDANG,
            self::PRIORITAS_TINGGI,
        ];
        $labels = [
            self::PRIORITAS_RENDAH => 'Rendah',
            self::PRIORITAS_SEDANG => 'Sedang',
            self::PRIORITAS_TINGGI => 'Tinggi',
        ];
        return $associative ? $labels : $priorities;
    }

    /**
     * Menggabungkan semua status untuk digunakan dalam filter dropdown.
     */
    public static function getStatusListForFilter(): array
    {
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
        return match (strtolower($status)) {
            strtolower(self::STATUS_PENGAJUAN_DIAJUKAN) => 'text-bg-info',
            strtolower(self::STATUS_PENGAJUAN_DISETUJUI) => 'text-bg-primary',
            strtolower(self::STATUS_PENGAJUAN_DITOLAK) => 'text-bg-danger',
            strtolower(self::STATUS_PENGAJUAN_DIBATALKAN) => 'text-bg-secondary',
            strtolower(self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN) => 'text-bg-warning text-dark',
            strtolower(self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN) => 'text-bg-info',
            strtolower(self::STATUS_PENGERJAAN_SELESAI) => 'text-bg-success',
            strtolower(self::STATUS_PENGERJAAN_GAGAL) => 'text-bg-danger',
            strtolower(self::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI) => 'text-bg-dark',
            strtolower(self::STATUS_PENGERJAAN_DITUNDA) => 'text-bg-secondary',
            default => 'text-bg-light text-dark',
        };
    }

    /**
     * Accessor untuk mendapatkan warna badge Bootstrap berdasarkan status virtual.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DIAJUKAN => 'secondary',
            self::STATUS_DISETUJUI => 'info',
            self::STATUS_DALAM_PERBAIKAN => 'warning',
            self::STATUS_SELESAI => 'success',
            self::STATUS_TUNTAS => 'primary', // <-- TAMBAHKAN WARNA UNTUK TUNTAS
            self::STATUS_DITOLAK => 'danger',
            default => 'light',
        };
    }

    /**
     * Memeriksa apakah laporan pemeliharaan sudah dalam status final (terkunci)
     * dan seharusnya tidak bisa diedit lagi.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        // Daftar status pengerjaan yang dianggap final
        $finalPengerjaan = [
            self::STATUS_PENGERJAAN_SELESAI,
            self::STATUS_PENGERJAAN_GAGAL,
            self::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI,
        ];

        // Daftar status pengajuan yang dianggap final
        $finalPengajuan = [
            self::STATUS_PENGAJUAN_DITOLAK,
            self::STATUS_PENGAJUAN_DIBATALKAN,
        ];

        // Laporan dianggap terkunci jika status pengerjaan ATAU status pengajuannya final.
        return in_array($this->status_pengerjaan, $finalPengerjaan) || in_array($this->status_pengajuan, $finalPengajuan);
    }


    // --- MODEL EVENTS (BOOT METHOD) ---
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pemeliharaan) {
            $pemeliharaan->tanggal_pengajuan = $pemeliharaan->tanggal_pengajuan ?? now();
            $pemeliharaan->status_pengajuan = $pemeliharaan->status_pengajuan ?? self::STATUS_PENGAJUAN_DIAJUKAN;
            $pemeliharaan->status_pengerjaan = $pemeliharaan->status_pengerjaan ?? self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN;
            $pemeliharaan->prioritas = $pemeliharaan->prioritas ?? self::PRIORITAS_SEDANG;
            // Jika id_user_pengaju tidak di-set dari controller, set ke user yang sedang login
            if (is_null($pemeliharaan->id_user_pengaju) && Auth::check()) {
                $pemeliharaan->id_user_pengaju = Auth::id();
            }
        });

        // Listener 'saved' dan 'deleted' dari model Anda sebelumnya bisa dipertahankan atau
        // lebih baik dipindahkan ke Controller atau Service class untuk logika yang lebih kompleks
        // terkait update status BarangQrCode dan pembuatan log BarangStatus.
        // Hal ini untuk menjaga Model tetap fokus pada representasi data dan relasi.
        // Namun, jika Anda ingin tetap di sini, pastikan logikanya akurat.

        // Contoh jika tetap ingin di model (harus diuji dengan seksama):
        // static::saved(function ($pemeliharaan) {
        //     $barang = $pemeliharaan->barangQrCode;
        //     if (!$barang) return;

        //     // Ambil nilai original sebelum perubahan di dalam event ini
        //     $originalBarangAttributes = $barang->getOriginal();
        //     $kondisiSebelum = $originalBarangAttributes['kondisi'] ?? $barang->kondisi;
        //     $statusKetersediaanSebelum = $originalBarangAttributes['status'] ?? $barang->status;

        //     $perluSimpanBarang = false;

        //     if (
        //         $pemeliharaan->status_pengajuan === self::STATUS_PENGAJUAN_DISETUJUI &&
        //         in_array($pemeliharaan->status_pengerjaan, [self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN, self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN])
        //     ) {
        //         if ($barang->status !== BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
        //             $barang->status = BarangQrCode::STATUS_DALAM_PEMELIHARAAN;
        //             $perluSimpanBarang = true;
        //         }
        //         if ($barang->kondisi === BarangQrCode::KONDISI_BAIK) { // Hanya ubah jika kondisi BAIK
        //             $barang->kondisi = BarangQrCode::KONDISI_KURANG_BAIK;
        //             $perluSimpanBarang = true;
        //         }
        //     } elseif ($pemeliharaan->status_pengerjaan === self::STATUS_PENGERJAAN_SELESAI) {
        //         $kondisiBaru = $pemeliharaan->kondisi_barang_setelah_pemeliharaan ?? BarangQrCode::KONDISI_BAIK;
        //         if (!in_array($kondisiBaru, BarangQrCode::getValidKondisi())) { // Pastikan valid
        //             $kondisiBaru = BarangQrCode::KONDISI_BAIK; // Fallback jika tidak valid
        //         }
        //         if ($barang->status !== BarangQrCode::STATUS_TERSEDIA || $barang->kondisi !== $kondisiBaru) {
        //             $barang->status = BarangQrCode::STATUS_TERSEDIA;
        //             $barang->kondisi = $kondisiBaru;
        //             $perluSimpanBarang = true;
        //         }
        //     } elseif ($pemeliharaan->status_pengerjaan === self::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI) {
        //         $kondisiBaru = $pemeliharaan->kondisi_barang_setelah_pemeliharaan ?? BarangQrCode::KONDISI_RUSAK_BERAT;
        //         if (!in_array($kondisiBaru, BarangQrCode::getValidKondisi())) {
        //             $kondisiBaru = BarangQrCode::KONDISI_RUSAK_BERAT;
        //         }
        //         if ($barang->status !== BarangQrCode::STATUS_TERSEDIA || $barang->kondisi !== $kondisiBaru) {
        //             $barang->status = BarangQrCode::STATUS_TERSEDIA; // Tetap tersedia untuk keputusan lanjut (misal arsip)
        //             $barang->kondisi = $kondisiBaru;
        //             $perluSimpanBarang = true;
        //         }
        //     } elseif (in_array($pemeliharaan->status_pengajuan, [self::STATUS_PENGAJUAN_DITOLAK, self::STATUS_PENGAJUAN_DIBATALKAN])) {
        //         if ($barang->status === BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
        //             $adaPemeliharaanAktifLain = Pemeliharaan::where('id_barang_qr_code', $barang->id)
        //                 ->where('id', '!=', $pemeliharaan->id)
        //                 ->where('status_pengajuan', self::STATUS_PENGAJUAN_DISETUJUI)
        //                 ->whereIn('status_pengerjaan', [self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN, self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN])
        //                 ->whereNull('deleted_at')
        //                 ->exists();
        //             if (!$adaPemeliharaanAktifLain) {
        //                 $barang->status = BarangQrCode::STATUS_TERSEDIA;
        //                 // Mengambil kondisi dari log status terakhir sebelum pemeliharaan ini
        //                 $logStatusAwal = BarangStatus::where('id_barang_qr_code', $barang->id)
        //                     ->where('id_pemeliharaan_trigger', $pemeliharaan->id)
        //                     ->orderBy('tanggal_pencatatan', 'asc')
        //                     ->first();
        //                 if ($logStatusAwal && $logStatusAwal->kondisi_sebelumnya) {
        //                     $barang->kondisi = $logStatusAwal->kondisi_sebelumnya;
        //                 }
        //                 $perluSimpanBarang = true;
        //             }
        //         }
        //     }

        //     if ($perluSimpanBarang) {
        //         $barang->saveQuietly(); // Gunakan saveQuietly untuk mencegah loop event jika BarangQrCode punya event juga
        //     }

        //     // Cek apakah ada perubahan yang signifikan untuk dicatat di BarangStatus
        //     if ($kondisiSebelum !== $barang->kondisi || $statusKetersediaanSebelum !== $barang->status) {
        //         BarangStatus::create([
        //             'id_barang_qr_code' => $barang->id,
        //             'id_user_pencatat' => Auth::id() ?? $pemeliharaan->id_operator_pengerjaan ?? $pemeliharaan->id_user_penyetuju ?? $pemeliharaan->id_user_pengaju,
        //             'tanggal_pencatatan' => now(),
        //             'kondisi_sebelumnya' => $kondisiSebelum,
        //             'kondisi_sesudahnya' => $barang->kondisi,
        //             'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum,
        //             'status_ketersediaan_sesudahnya' => $barang->status,
        //             'deskripsi_kejadian' => 'Perubahan status/kondisi akibat pemeliharaan ID: ' . $pemeliharaan->id . '. Pengajuan: ' . $pemeliharaan->status_pengajuan . ', Pengerjaan: ' . $pemeliharaan->status_pengerjaan,
        //             'id_pemeliharaan_trigger' => $pemeliharaan->id,
        //         ]);
        //     }
        // });

        static::deleted(function ($pemeliharaan) {
            if (!$pemeliharaan->isForceDeleting() && $pemeliharaan->barangQrCode) {
                $barang = $pemeliharaan->barangQrCode;
                if ($barang->status === BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
                    $adaPemeliharaanAktifLain = Pemeliharaan::where('id_barang_qr_code', $barang->id)
                        ->where('id', '!=', $pemeliharaan->id)
                        ->where('status_pengajuan', self::STATUS_PENGAJUAN_DISETUJUI)
                        ->whereIn('status_pengerjaan', [self::STATUS_PENGERJAAN_BELUM_DIKERJAKAN, self::STATUS_PENGERJAAN_SEDANG_DILAKUKAN])
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$adaPemeliharaanAktifLain) {
                        $statusKetersediaanSebelum = $barang->status;
                        $kondisiSebelum = $barang->kondisi;

                        $barang->status = BarangQrCode::STATUS_TERSEDIA;
                        $logStatusAwal = BarangStatus::where('id_barang_qr_code', $barang->id)
                            ->where('id_pemeliharaan_trigger', $pemeliharaan->id) // Cari log yang dipicu oleh pemeliharaan ini
                            ->orderBy('tanggal_pencatatan', 'asc') // Ambil yang paling awal
                            ->first();
                        if ($logStatusAwal && $logStatusAwal->kondisi_sebelumnya) {
                            $barang->kondisi = $logStatusAwal->kondisi_sebelumnya; // Kembalikan ke kondisi sebelum pemeliharaan ini dimulai
                        }
                        $barang->saveQuietly();

                        BarangStatus::create([
                            'id_barang_qr_code' => $barang->id,
                            'id_user_pencatat' => Auth::id() ?? ($pemeliharaan->deleted_by_user_id ?? $pemeliharaan->id_user_pengaju), // Perlu cara mendapatkan user yang mendelete jika ada
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
