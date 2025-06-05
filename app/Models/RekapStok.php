<?php

namespace App\Models;
// File: app/Models/RekapStok.php



use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log; // Tambahkan ini jika ingin logging

// Tidak ada SoftDeletes di tabel rekap_stoks menurut SQL dump. [cite: 740]

/**
 * Model RekapStok merepresentasikan rekapitulasi jumlah stok barang
 * per jenis barang per ruangan pada periode tertentu.
 */
class RekapStok extends Model
{
    use HasFactory; // [cite: 742]

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'rekap_stoks'; // [cite: 743]

    /**
     * Kunci utama tabel.
     *
     * @var string
     */
    protected $primaryKey = 'id'; // [cite: 744]

    /**
     * Menunjukkan apakah ID model otomatis bertambah (incrementing).
     *
     * @var bool
     */
    public $incrementing = true; // [cite: 745]

    /**
     * Tipe data dari kunci utama.
     *
     * @var string
     */
    protected $keyType = 'int'; // [cite: 747]

    /**
     * Menunjukkan apakah model harus menggunakan timestamps (created_at, updated_at).
     * Sesuai SQL dump, tabel ini memiliki timestamps.
     *
     * @var bool
     */
    public $timestamps = true; // [cite: 749]

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_barang',                // FK ke tabel barangs (jenis barang induk) [cite: 752]
        'id_ruangan',               // FK ke tabel ruangans [cite: 752]
        'jumlah_tercatat_sistem',   // Jumlah unit yang tercatat di sistem pada periode rekap [cite: 752]
        'jumlah_fisik_terakhir',    // Jumlah unit fisik terakhir yang tercatat (biasanya dari stok opname) [cite: 753]
        'periode_rekap',            // Tanggal periode rekapitulasi [cite: 753]
        'catatan',                  // Catatan tambahan [cite: 753]
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jumlah_tercatat_sistem' => 'integer', // [cite: 754]
        'jumlah_fisik_terakhir' => 'integer', // [cite: 754]
        'periode_rekap' => 'date', // Sesuai tipe 'date' di SQL dump [cite: 754]
        'created_at' => 'datetime', // [cite: 754]
        'updated_at' => 'datetime', // [cite: 754]
    ];

    /**
     * Mendefinisikan relasi BelongsTo ke model Barang (induk).
     * Satu catatan rekap stok terkait dengan satu jenis barang.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang'); // [cite: 758]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Ruangan.
     * Satu catatan rekap stok terkait dengan satu ruangan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan'); // [cite: 760]
    }

    /**
     * Memperbarui atau membuat data rekap stok setelah sebuah sesi StokOpname selesai.
     * Metode ini menghitung jumlah fisik terakhir berdasarkan semua detail unit
     * dari jenis barang tertentu dalam sesi StokOpname yang diberikan.
     *
     * @param int $barangId ID jenis barang (dari tabel `barangs`).
     * @param int $ruanganId ID ruangan.
     * @param string $periodeRekap Tanggal periode rekap (format YYYY-MM-DD).
     * @param int $stokOpnameId ID dari record StokOpname yang telah selesai.
     * @return self Instance RekapStok yang diperbarui atau dibuat.
     */
    public static function updateOrCreateRekapForCompletedSO(int $barangId, int $ruanganId, string $periodeRekap, int $stokOpnameId): self
    {
        // 1. Hitung jumlah unit sistem saat ini (setelah penyesuaian dari Stok Opname)
        // Ini adalah jumlah BarangQrCode yang aktif (tidak soft-deleted)
        // untuk jenis barang ($barangId) dan ruangan ($ruanganId) ini.
        $jumlahTercatatSistem = BarangQrCode::where('id_barang', $barangId)
            ->where('id_ruangan', $ruanganId) // Pastikan unit memang berlokasi di ruangan ini
            ->whereNull('deleted_at') // Hanya unit yang aktif
            ->count();

        // 2. Hitung jumlah fisik terakhir berdasarkan semua DetailStokOpname
        // dari $stokOpnameId untuk $barangId (master) tersebut.
        // Ini adalah jumlah unit dari jenis barang $barangId yang secara fisik
        // ditemukan (tidak 'Hilang') selama $stokOpnameId.
        $jumlahFisikTerakhir = DetailStokOpname::where('id_stok_opname', $stokOpnameId)
            ->whereHas('barangQrCode', function ($query) use ($barangId) {
                // Pastikan detail ini adalah untuk unit dari jenis barang yang benar
                $query->where('id_barang', $barangId);
            })
            // Hitung semua unit yang kondisi fisiknya BUKAN 'Hilang'
            ->where('kondisi_fisik', '!=', DetailStokOpname::KONDISI_HILANG) // Menggunakan konstanta dari DetailStokOpname
            ->count();

        // Update atau buat record rekap stok
        $rekap = self::updateOrCreate(
            [
                'id_barang'     => $barangId,
                'id_ruangan'    => $ruanganId,
                'periode_rekap' => $periodeRekap,
            ],
            [
                'jumlah_tercatat_sistem' => $jumlahTercatatSistem,
                'jumlah_fisik_terakhir'  => $jumlahFisikTerakhir,
                'catatan' => ($stokOpnameId ? 'Diperbarui dari Stok Opname ID: ' . $stokOpnameId : 'Rekap dibuat/diperbarui') . ' pada ' . now()->toDateTimeString(),
            ]
        );

        return $rekap;
    }


    /**
     * Memperbarui atau membuat data rekap stok untuk jenis barang dan ruangan tertentu pada periode tertentu.
     *
     * @param int $barangId ID jenis barang (dari tabel `barangs`).
     * @param int $ruanganId ID ruangan.
     * @param string $periodeRekap Tanggal periode rekap (format YYYY-MM-DD).
     * @param \App\Models\DetailStokOpname|null $detailStokOpname Opsional, detail stok opname terakhir untuk barang ini di ruangan ini,
     * digunakan untuk menentukan `jumlah_fisik_terakhir`.
     * @return self Instance RekapStok yang diperbarui atau dibuat.
     * @deprecated Gunakan updateOrCreateRekapForCompletedSO untuk akurasi yang lebih baik setelah StokOpname selesai.
     */
    public static function updateStokRekap(int $barangId, int $ruanganId, string $periodeRekap, ?DetailStokOpname $detailStokOpname = null): self
    {
        Log::warning("Metode RekapStok::updateStokRekap() yang lama dipanggil. Pertimbangkan untuk beralih ke updateOrCreateRekapForCompletedSO(). Stack trace: " . json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)));

        // Hitung jumlah unit (BarangQrCode) yang aktif (tidak soft-deleted)
        // untuk jenis barang ($barangId) dan ruangan ($ruanganId) ini.
        $currentStokSistem = BarangQrCode::where('id_barang', $barangId) // [cite: 766]
            ->where('id_ruangan', $ruanganId) // [cite: 766]
            ->whereNull('deleted_at') // [cite: 766]
            ->count(); // [cite: 766]

        $jumlahFisikTerakhir = null;
        if ($detailStokOpname && $detailStokOpname->barangQrCode && $detailStokOpname->barangQrCode->id_barang == $barangId) { // [cite: 767]
            // Jika detail stok opname diberikan dan relevan dengan jenis barang ini
            if ($detailStokOpname->kondisi_fisik !== BarangQrCode::KONDISI_HILANG) { // [cite: 767]
                // Ini adalah logika yang kurang tepat untuk rekap per jenis barang, karena $detailStokOpname adalah per unit.
                // Seharusnya, ini dihitung dari agregasi semua unit yang relevan dalam StokOpname yang sama.
                // Untuk sementara, jika metode ini masih dipanggil, kita akan mencoba mendapatkan jumlah fisik dari StokOpname terkait.
                if ($detailStokOpname->id_stok_opname) {
                    $jumlahFisikTerakhir = DetailStokOpname::where('id_stok_opname', $detailStokOpname->id_stok_opname)
                        ->whereHas('barangQrCode', fn($q) => $q->where('id_barang', $barangId))
                        ->where('kondisi_fisik', '!=', DetailStokOpname::KONDISI_HILANG)
                        ->count();
                } else {
                    $jumlahFisikTerakhir = ($detailStokOpname->kondisi_fisik !== DetailStokOpname::KONDISI_HILANG) ? 1 : 0; // Fallback jika id_stok_opname tidak ada
                }
            } else {
                $jumlahFisikTerakhir = 0; // Jika hilang [cite: 768]
            }
        } else {
            // Jika tidak ada info dari $detailStokOpname yang diberikan, coba cari dari histori StokOpname terakhir.
            $latestCompletedStokOpname = StokOpname::where('id_ruangan', $ruanganId)
                ->where('status', StokOpname::STATUS_SELESAI)
                ->whereDate('tanggal_opname', '<=', $periodeRekap)
                ->orderByDesc('tanggal_opname')
                ->orderByDesc('created_at')
                ->first();

            if ($latestCompletedStokOpname) {
                $jumlahFisikTerakhir = DetailStokOpname::where('id_stok_opname', $latestCompletedStokOpname->id)
                    ->whereHas('barangQrCode', fn($q) => $q->where('id_barang', $barangId))
                    ->where('kondisi_fisik', '!=', DetailStokOpname::KONDISI_HILANG)
                    ->count();
            }
        }

        // Update atau buat record rekap stok
        $rekap = self::firstOrNew( // [cite: 781]
            [
                'id_barang' => $barangId, // [cite: 781]
                'id_ruangan' => $ruanganId, // [cite: 781]
                'periode_rekap' => $periodeRekap, // [cite: 782]
            ]
        );

        $rekap->jumlah_tercatat_sistem = $currentStokSistem; // [cite: 783]
        // Jika jumlah fisik tidak bisa ditentukan, gunakan jumlah sistem sebagai fallback.
        $rekap->jumlah_fisik_terakhir = $jumlahFisikTerakhir ?? $currentStokSistem; // [cite: 784]

        if ($rekap->isDirty() || !$rekap->exists) { // [cite: 784]
            $rekap->catatan = $rekap->exists ? ($rekap->catatan . ' | Diperbarui pada ' . now()->toDateTimeString()) : ('Rekap stok dibuat pada ' . now()->toDateTimeString()); // [cite: 785]
        }
        $rekap->save(); // [cite: 786]

        return $rekap;
    }
}
