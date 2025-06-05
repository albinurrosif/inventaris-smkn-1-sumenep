<?php

// File: app/Models/ArsipBarang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth; // Ditambahkan jika ada interaksi dengan user login di model
use Illuminate\Support\Facades\Log;

/**
 * Model ArsipBarang merepresentasikan data barang yang telah diarsipkan atau dihapusbukukan.
 */
class ArsipBarang extends Model
{
    use HasFactory; // Tidak menggunakan SoftDeletes karena ini adalah tabel arsip itu sendiri.

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'arsip_barangs';

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
     * Sesuai SQL dump, tabel ini memiliki timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Konstanta untuk status_arsip.
     * Ini akan digunakan di seluruh aplikasi untuk merujuk pada status arsip.
     */
    public const STATUS_ARSIP_DIAJUKAN = 'Diajukan';
    public const STATUS_ARSIP_DISETUJUI = 'Disetujui'; // Untuk alur persetujuan sebelum benar-benar diarsipkan/dihapus
    public const STATUS_ARSIP_DITOLAK = 'Ditolak';   // Jika pengajuan arsip ditolak
    public const STATUS_ARSIP_DISETUJUI_PERMANEN = 'Diarsipkan Permanen'; // Jika disetujui dan unit di soft-delete/dihapus permanen
    public const STATUS_ARSIP_DIPULIHKAN = 'Dipulihkan';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_barang_qr_code',        // FK ke unit barang yang diarsipkan (unique)
        'id_user_pengaju',          // User yang mengajukan pengarsipan
        'id_user_penyetuju',        // User yang menyetujui pengarsipan
        'jenis_penghapusan',        // Enum jenis penghapusan (Rusak Berat, Hilang, dll.)
        'alasan_penghapusan',       // Alasan mengapa barang diarsipkan/dihapus
        'berita_acara_path',        // Path ke file dokumen berita acara
        'foto_bukti_path',          // Path ke file foto bukti
        'tanggal_pengajuan_arsip',  // Tanggal pengajuan pengarsipan
        'tanggal_penghapusan_resmi', // Tanggal resmi barang dihapus dari inventaris aktif
        'status_arsip',             // Enum status alur pengarsipan (Diajukan, Disetujui, dll.)
        'dipulihkan_oleh',          // User yang memulihkan barang dari arsip
        'tanggal_dipulihkan',       // Tanggal barang dipulihkan
        'data_unit_snapshot',       // Snapshot data unit barang saat diarsipkan (JSON)
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_pengajuan_arsip' => 'datetime',
        'tanggal_penghapusan_resmi' => 'datetime',
        'tanggal_dipulihkan' => 'datetime',
        'data_unit_snapshot' => 'array', // JSON di database di-cast ke array PHP
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'jenis_penghapusan' => 'string', // Enum
        'status_arsip' => 'string',      // Enum
    ];

    /**
     * Mendefinisikan relasi BelongsTo ke model BarangQrCode.
     * Satu catatan arsip terkait dengan satu unit barang (BarangQrCode).
     * Menggunakan withTrashed() karena BarangQrCode mungkin sudah di-soft-delete saat diarsipkan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function barangQrCode(): BelongsTo
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code')->withTrashed();
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai pengaju pengarsipan).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pengaju');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai penyetuju pengarsipan).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_penyetuju');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (yang memulihkan barang dari arsip).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dipulihkanOlehUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dipulihkan_oleh');
    }

    /**
     * Mendapatkan daftar jenis penghapusan yang valid.
     * Digunakan untuk validasi dan dropdown di form.
     *
     * @return array<string, string>
     */
    public static function getValidJenisPenghapusan(): array
    {
        return [
            'Rusak Berat' => 'Rusak Berat (Tidak Dapat Diperbaiki)',
            'Hilang' => 'Hilang',
            'Dimusnahkan' => 'Dimusnahkan (Sesuai Prosedur)',
            'Dijual' => 'Dijual (Sesuai Prosedur)',
            'Dihibahkan' => 'Dihibahkan',
            'Usang' => 'Usang/Kadaluarsa', // Ditambahkan dari diskusi sebelumnya
            'Lain-lain' => 'Lain-lain (Jelaskan di Alasan Detail)',
        ];
    }

    /**
     * Mendapatkan daftar status arsip yang valid.
     * Digunakan untuk validasi dan referensi.
     *
     * @return array<string>
     */
    public static function getValidStatusArsip(): array
    {
        return [
            self::STATUS_ARSIP_DIAJUKAN,
            self::STATUS_ARSIP_DISETUJUI,
            self::STATUS_ARSIP_DITOLAK,
            self::STATUS_ARSIP_DISETUJUI_PERMANEN,
            self::STATUS_ARSIP_DIPULIHKAN,
        ];
    }


    /**
     * Metode untuk memulihkan barang yang telah diarsipkan.
     * Ini akan me-restore record BarangQrCode yang terkait (jika di-soft-delete),
     * mengupdate statusnya, dan mencatat log pemulihan.
     *
     * @param int $userId ID pengguna yang melakukan pemulihan.
     * @return \App\Models\BarangQrCode|null Unit barang yang berhasil dipulihkan, atau null jika gagal.
     */
    public function restoreBarang(int $userId): ?BarangQrCode
    {
        // Mengambil record BarangQrCode, termasuk yang mungkin sudah trashed (soft-deleted)
        $barangQr = $this->barangQrCode()->first();

        if (!$barangQr) {
            Log::warning("Gagal memulihkan dari Arsip ID: {$this->id}. BarangQrCode terkait tidak ditemukan.");
            return null; // BarangQrCode tidak ditemukan
        }

        // Hanya lanjutkan jika barang memang di-soft-delete atau status arsipnya memungkinkan pemulihan
        $canBeRestored = false;
        if ($barangQr->trashed()) {
            $canBeRestored = true; // Jika unit di-soft-delete, bisa dipulihkan
        } elseif ($this->status_arsip === self::STATUS_ARSIP_DIAJUKAN || $this->status_arsip === self::STATUS_ARSIP_DISETUJUI) {
            // Jika unit belum di-soft-delete tapi status arsipnya masih 'Diajukan' atau 'Disetujui' (belum permanen)
            // Ini berarti proses pengarsipan dibatalkan sebelum unit di-soft-delete.
            $canBeRestored = true;
        }


        if ($canBeRestored) {
            if ($barangQr->trashed()) {
                $barangQr->restore(); // Restore record BarangQrCode jika sebelumnya di-soft-delete
            }

            // Atur kembali status dan kondisi barang setelah dipulihkan
            // Ambil status dan kondisi dari snapshot jika ada, jika tidak, default ke Tersedia & Baik
            $barangQr->status = $this->data_unit_snapshot['status'] ?? BarangQrCode::STATUS_TERSEDIA;
            $barangQr->kondisi = $this->data_unit_snapshot['kondisi'] ?? BarangQrCode::KONDISI_BAIK;

            // Kembalikan ruangan atau pemegang dari snapshot
            // Jika id_ruangan ada di snapshot, gunakan itu. Jika tidak, coba id_pemegang_personal.
            // Jika keduanya tidak ada, biarkan null atau set ke default jika ada.
            $barangQr->id_ruangan = $this->data_unit_snapshot['id_ruangan'] ?? null;
            $barangQr->id_pemegang_personal = $this->data_unit_snapshot['id_pemegang_personal'] ?? null;

            // Pastikan salah satu (ruangan atau pemegang) terisi jika keduanya null di snapshot
            // dan ada aturan bahwa salah satu harus terisi. Ini tergantung logika bisnis Anda.
            // Untuk sekarang, kita ikuti data snapshot.

            $barangQr->save();

            // Update record arsip ini
            $this->dipulihkan_oleh = $userId;
            $this->tanggal_dipulihkan = now();
            $this->status_arsip = self::STATUS_ARSIP_DIPULIHKAN; // Menggunakan konstanta
            $this->save();

            // Opsional: Catat kejadian pemulihan di BarangStatus jika diperlukan
            // Ini bisa dilakukan di controller setelah memanggil metode ini untuk akses ke Request object
            // atau jika Anda ingin mencatatnya langsung di sini:
            if (class_exists(BarangStatus::class)) { // Cek jika model BarangStatus ada
                BarangStatus::create([
                    'id_barang_qr_code' => $barangQr->id,
                    'id_user_pencatat' => $userId, // User yang melakukan pemulihan
                    'tanggal_pencatatan' => now(),
                    'kondisi_sebelumnya' => $this->data_unit_snapshot['kondisi'] ?? BarangQrCode::KONDISI_HILANG, // Kondisi saat diarsip
                    'kondisi_sesudahnya' => $barangQr->kondisi,
                    'status_ketersediaan_sebelumnya' => $this->data_unit_snapshot['status_ketersediaan_sebelumnya'] ?? null, // Status saat diarsip
                    'status_ketersediaan_sesudahnya' => $barangQr->status,
                    'id_ruangan_sebelumnya' => $this->data_unit_snapshot['id_ruangan_sebelumnya'] ?? null, // Ruangan saat diarsip
                    'id_ruangan_sesudahnya' => $barangQr->id_ruangan,
                    'id_pemegang_personal_sebelumnya' => $this->data_unit_snapshot['id_pemegang_personal_sebelumnya'] ?? null, // Pemegang saat diarsip
                    'id_pemegang_personal_sesudahnya' => $barangQr->id_pemegang_personal,
                    'deskripsi_kejadian' => 'Barang dipulihkan dari arsip. Arsip ID: ' . $this->id,
                    'id_arsip_barang_trigger' => $this->id,
                ]);
            }

            return $barangQr;
        }

        Log::info("Gagal memulihkan dari Arsip ID: {$this->id}. BarangQrCode (ID: {$barangQr->id}) tidak dalam kondisi yang dapat dipulihkan (tidak trashed atau status arsip tidak memungkinkan).");
        return null; // Gagal memulihkan
    }
}
