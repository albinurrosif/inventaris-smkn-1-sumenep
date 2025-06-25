<?php

// File: app/Models/StokOpname.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Ditambahkan untuk user pencatat default

/**
 * Model StokOpname merepresentasikan transaksi pemeriksaan fisik barang (stok opname)
 * yang dilakukan pada suatu ruangan oleh seorang operator.
 */
class StokOpname extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at']; //
    protected $table = 'stok_opname'; //
    protected $primaryKey = 'id'; //
    public $incrementing = true; //
    protected $keyType = 'int'; //
    public $timestamps = true; //

    protected $fillable = [
        'id_ruangan',       // FK ke ruangan yang di-stok opname //
        'id_operator',      // FK ke user (operator) yang melakukan //
        'tanggal_opname',   // Tanggal pelaksanaan stok opname //
        'catatan',          // Catatan umum terkait stok opname //
        'status',           // Status stok opname (Draft, Selesai, Dibatalkan) //
        'catatan_pengerjaan',
        'tanggal_mulai_pengerjaan', // Tambahkan ini
        'tanggal_selesai_pengerjaan', // Tambahkan ini
    ];

    protected $casts = [
        'tanggal_opname' => 'date', //
        'status' => 'string', // Enum //
        'tanggal_mulai_pengerjaan' => 'datetime', // Tambahkan ini
        'tanggal_selesai_pengerjaan' => 'datetime', // Tambahkan ini
        'created_at' => 'datetime', //
        'updated_at' => 'datetime', //
        'deleted_at' => 'datetime', //
    ];

    public const STATUS_DRAFT = 'Draft'; //
    public const STATUS_SELESAI = 'Selesai'; //
    public const STATUS_DIBATALKAN = 'Dibatalkan'; //

    /**
     * Helper untuk mendapatkan kelas warna Bootstrap berdasarkan status stok opname.
     */
    public static function statusColor($status): string
    {
        return match (strtolower($status)) {
            self::STATUS_SELESAI => 'text-bg-success',
            self::STATUS_DRAFT => 'text-bg-warning',
            self::STATUS_DIBATALKAN => 'text-bg-secondary',
            default => 'text-bg-light',
        };
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_operator'); //
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan'); //
    }

    public function detailStokOpname(): HasMany
    {
        return $this->hasMany(DetailStokOpname::class, 'id_stok_opname'); //
    }

    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_DRAFT, //
            self::STATUS_SELESAI, //
            self::STATUS_DIBATALKAN, //
        ];
    }

    protected static function boot()
    {
        parent::boot(); //

        static::deleting(function ($stokOpname) { //
            if (!$stokOpname->isForceDeleting()) { //
                $stokOpname->detailStokOpname()->each(function ($detail) { //
                    $detail->delete(); //
                });
            }
        });

        static::restoring(function ($stokOpname) { //
            $stokOpname->detailStokOpname()->onlyTrashed()->each(function ($detail) { //
                $detail->restore(); //
            });
        });

        static::updated(function ($stokOpname) { //
            if ($stokOpname->isDirty('status') && $stokOpname->status === self::STATUS_SELESAI) { //
                $affectedBarangInRuangan = [];

                foreach ($stokOpname->detailStokOpname as $detail) { //
                    $barangQrCode = $detail->barangQrCode()->withTrashed()->first(); //
                    if (!$barangQrCode) continue; //

                    $kondisiSebelum = $barangQrCode->kondisi; //
                    $statusKetersediaanSebelum = $barangQrCode->status; //
                    $isTrashedSebelum = $barangQrCode->trashed(); //
                    $perluSimpanBarangQrCode = false;
                    $deskripsiKejadian = 'Hasil Stok Opname. Opname ID: ' . $stokOpname->id . '. Detail ID: ' . $detail->id . '. Kondisi Fisik: ' . $detail->kondisi_fisik; //

                    if ($detail->kondisi_fisik !== null) { //
                        // Gunakan konstanta dari DetailStokOpname untuk kondisi_fisik
                        if ($barangQrCode->kondisi !== $detail->kondisi_fisik && $detail->kondisi_fisik !== DetailStokOpname::KONDISI_DITEMUKAN) {
                            // Jangan update kondisi barang jika 'Ditemukan', karena 'Ditemukan' bukan kondisi barang tapi status penemuan
                            if (in_array($detail->kondisi_fisik, BarangQrCode::getValidKondisi())) { // Pastikan kondisi fisik valid untuk BarangQrCode
                                $barangQrCode->kondisi = $detail->kondisi_fisik; //
                                $perluSimpanBarangQrCode = true; //
                            }
                        }

                        if ($detail->kondisi_fisik === DetailStokOpname::KONDISI_HILANG) { //
                            if (!$barangQrCode->trashed()) { //
                                $barangQrCode->delete(); // Soft delete //
                                $perluSimpanBarangQrCode = false; // Event deleting akan handle decrement //
                                $deskripsiKejadian .= ' Barang dinyatakan hilang dan di-soft-delete.'; //

                                // Ganti blok if di atas dengan kode ini
                                ArsipBarang::updateOrCreate(
                                    [
                                        'id_barang_qr_code' => $barangQrCode->id, // Kunci unik untuk mencari
                                    ],
                                    [
                                        'id_user_pengaju' => $stokOpname->id_operator,
                                        'id_user_penyetuju' => $stokOpname->id_operator, // Langsung disetujui oleh sistem
                                        'jenis_penghapusan' => 'Hilang',
                                        'alasan_penghapusan' => $deskripsiKejadian,
                                        'tanggal_pengajuan_arsip' => now(),
                                        'tanggal_penghapusan_resmi' => now(),
                                        'status_arsip' => ArsipBarang::STATUS_ARSIP_DISETUJUI_PERMANEN, // Langsung set permanen
                                        'data_unit_snapshot' => $barangQrCode->toArray(),
                                        'dipulihkan_oleh' => null,      // Reset data pemulihan jika ada
                                        'tanggal_dipulihkan' => null,   // Reset data pemulihan jika ada
                                    ]
                                );
                            }
                        } elseif ($detail->kondisi_fisik === DetailStokOpname::KONDISI_RUSAK_BERAT) { //
                            if ($barangQrCode->status !== BarangQrCode::STATUS_DALAM_PEMELIHARAAN) { //
                                $barangQrCode->status = BarangQrCode::STATUS_DALAM_PEMELIHARAAN; //
                                $perluSimpanBarangQrCode = true; //
                            }
                            if ($barangQrCode->trashed()) { //
                                $barangQrCode->restore(); // Pulihkan dulu jika trashed //
                                $perluSimpanBarangQrCode = true; // save() akan diperlukan
                            }


                            if (!Pemeliharaan::where('id_barang_qr_code', $barangQrCode->id)->whereIn('status_pengerjaan', [Pemeliharaan::STATUS_PENGERJAAN_BELUM_DIKERJAKAN, Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN])->where('status_pengajuan', Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI)->exists()) { //
                                Pemeliharaan::create([ //
                                    'id_barang_qr_code' => $barangQrCode->id, //
                                    'id_user_pengaju' => $stokOpname->id_operator, //
                                    'tanggal_pengajuan' => now(), //
                                    'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN, //
                                    'catatan_pengajuan' => 'Ditemukan Rusak Berat saat Stok Opname ID: ' . $stokOpname->id, //
                                ]);
                            }
                            $deskripsiKejadian .= ' Barang rusak berat, status diubah ke Dalam Pemeliharaan.'; //
                        } elseif (in_array($detail->kondisi_fisik, [DetailStokOpname::KONDISI_BAIK, DetailStokOpname::KONDISI_KURANG_BAIK, DetailStokOpname::KONDISI_DITEMUKAN])) { //
                            if ($barangQrCode->trashed()) { // Jika sebelumnya hilang/trashed, tapi ditemukan lagi //
                                $barangQrCode->restore(); //
                                $perluSimpanBarangQrCode = true; // save() akan diperlukan
                                $deskripsiKejadian .= ' Barang yang sebelumnya hilang/diarsipkan, ditemukan kembali.'; //
                                $arsip = ArsipBarang::where('id_barang_qr_code', $barangQrCode->id)->where('status_arsip', '!=', ArsipBarang::STATUS_ARSIP_DIPULIHKAN)->first(); //
                                if ($arsip) { //
                                    $arsip->status_arsip = ArsipBarang::STATUS_ARSIP_DIPULIHKAN; //
                                    $arsip->tanggal_dipulihkan = now(); //
                                    $arsip->dipulihkan_oleh = $stokOpname->id_operator; //
                                    $arsip->save(); //
                                }
                            }
                            // Jika kondisi fisik adalah DITEMUKAN, kondisi barang di set ke BAIK (default untuk barang baru ditemukan)
                            if ($detail->kondisi_fisik === DetailStokOpname::KONDISI_DITEMUKAN && $barangQrCode->kondisi !== BarangQrCode::KONDISI_BAIK) {
                                $barangQrCode->kondisi = BarangQrCode::KONDISI_BAIK;
                                $perluSimpanBarangQrCode = true;
                            }


                            if ($barangQrCode->status !== BarangQrCode::STATUS_TERSEDIA && $barangQrCode->status !== BarangQrCode::STATUS_DIPINJAM) { //
                                // Hanya ubah ke Tersedia jika tidak sedang dipinjam
                                // Jika Dalam Pemeliharaan dan hasil SO baik/kurang baik, set jadi Tersedia
                                if ($barangQrCode->status === BarangQrCode::STATUS_DALAM_PEMELIHARAAN && in_array($detail->kondisi_fisik, [DetailStokOpname::KONDISI_BAIK, DetailStokOpname::KONDISI_KURANG_BAIK])) {
                                    $barangQrCode->status = BarangQrCode::STATUS_TERSEDIA;
                                    $perluSimpanBarangQrCode = true;
                                } else if ($barangQrCode->status !== BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
                                    // Jika bukan dari Dalam Pemeliharaan dan tidak Dipinjam, set Tersedia
                                    $barangQrCode->status = BarangQrCode::STATUS_TERSEDIA;
                                    $perluSimpanBarangQrCode = true;
                                }
                            }
                            $deskripsiKejadian .= ' Kondisi barang: ' . $detail->kondisi_fisik; //
                        }

                        if ($perluSimpanBarangQrCode) { //
                            $barangQrCode->save(); //
                        }

                        $statusKetersediaanSesudah = $barangQrCode->trashed() ? BarangQrCode::STATUS_DIARSIPKAN : $barangQrCode->status; //
                        if ($isTrashedSebelum !== $barangQrCode->trashed() || $kondisiSebelum !== $barangQrCode->kondisi || $statusKetersediaanSebelum !== $statusKetersediaanSesudah) { //
                            BarangStatus::create([ //
                                'id_barang_qr_code' => $barangQrCode->id, //
                                'id_user_pencatat' => $stokOpname->id_operator ?? Auth::id(), //
                                'tanggal_pencatatan' => now(), //
                                'kondisi_sebelumnya' => $kondisiSebelum, //
                                'kondisi_sesudahnya' => $barangQrCode->kondisi, //
                                'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum, //
                                'status_ketersediaan_sesudahnya' => $statusKetersediaanSesudah, //
                                'deskripsi_kejadian' => $deskripsiKejadian, //
                                'id_detail_stok_opname_trigger' => $detail->id, //
                            ]);
                        }
                    }
                    $affectedBarangInRuangan[$barangQrCode->id_barang] = true; //
                }

                $tanggalOpnameCarbon = Carbon::parse($stokOpname->tanggal_opname); //
                if (!$tanggalOpnameCarbon) { //
                    Log::warning("Tanggal opname tidak valid untuk StokOpname ID: {$stokOpname->id}. Menggunakan tanggal hari ini untuk rekap.");
                    $tanggalOpnameCarbon = Carbon::now(); //
                }
                $periodeRekapString = $tanggalOpnameCarbon->toDateString(); //
                foreach (array_keys($affectedBarangInRuangan) as $barangIdToRekap) {
                    RekapStok::updateOrCreateRekapForCompletedSO(
                        $barangIdToRekap, //
                        $stokOpname->id_ruangan, //
                        $periodeRekapString, //
                        $stokOpname->id //
                    );
                }
            }
        });
    }
}
