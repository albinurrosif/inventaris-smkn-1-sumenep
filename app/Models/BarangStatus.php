<?php

// File: app/Models/BarangStatus.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model BarangStatus merepresentasikan log atau histori perubahan kondisi dan status ketersediaan
 * sebuah unit barang (BarangQrCode), serta informasi terkait pemicu perubahan tersebut.
 */
class BarangStatus extends Model
{
    use HasFactory, SoftDeletes; // Menggunakan SoftDeletes sesuai SQL dump // [cite: 254]

    /**
     * Atribut tanggal yang harus diperlakukan sebagai instance Carbon.
     * Digunakan untuk SoftDeletes.
     *
     * @var array
     */
    protected $dates = ['deleted_at']; // [cite: 255]
    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'barang_statuses'; // [cite: 257]
    /**
     * Kunci utama tabel.
     *
     * @var string
     */
    protected $primaryKey = 'id'; // [cite: 258]
    /**
     * Menunjukkan apakah ID model otomatis bertambah (incrementing).
     *
     * @var bool
     */
    public $incrementing = true; // [cite: 260]
    /**
     * Tipe data dari kunci utama.
     *
     * @var string
     */
    protected $keyType = 'int'; // [cite: 262]
    /**
     * Menunjukkan apakah model harus menggunakan timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = true; // [cite: 264]
    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Sesuai dengan kolom-kolom di tabel `barang_statuses` dari SQL dump.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_barang_qr_code',                // FK ke unit barang // [cite: 267]
        'id_user_pencatat',                 // User yang mencatat perubahan // [cite: 267]
        'tanggal_pencatatan',               // Tanggal perubahan dicatat // [cite: 267]
        'kondisi_sebelumnya',               // Kondisi barang sebelum perubahan // [cite: 268]
        'kondisi_sesudahnya',               // Kondisi barang setelah perubahan // [cite: 268]
        'status_ketersediaan_sebelumnya',   // Status ketersediaan sebelum perubahan // [cite: 268]
        'status_ketersediaan_sesudahnya',   // Status ketersediaan setelah perubahan // [cite: 268]
        'id_ruangan_sebelumnya',            // Ruangan sebelum perubahan (jika relevan) // [cite: 268]
        'id_ruangan_sesudahnya',            // Ruangan setelah perubahan (jika relevan) // [cite: 269]
        'id_pemegang_personal_sebelumnya',  // Pemegang personal sebelum (jika relevan) // [cite: 269]
        'id_pemegang_personal_sesudahnya',  // Pemegang personal setelah (jika relevan) // [cite: 269]
        'deskripsi_kejadian',               // Deskripsi atau alasan perubahan // [cite: 269]
        'id_detail_peminjaman_trigger',     // FK ke detail peminjaman yang memicu (opsional) // [cite: 270]
        'id_mutasi_barang_trigger',         // FK ke mutasi barang yang memicu (opsional) // [cite: 270]
        'id_pemeliharaan_trigger',          // FK ke pemeliharaan yang memicu (opsional) // [cite: 270]
        'id_detail_stok_opname_trigger',    // FK ke detail stok opname yang memicu (opsional) // [cite: 270]
        'id_arsip_barang_trigger',          // FK ke arsip barang yang memicu (opsional) // [cite: 270]
    ];
    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_pencatatan' => 'datetime', // [cite: 273]
        'created_at' => 'datetime', // [cite: 273]
        'updated_at' => 'datetime', // [cite: 273]
        'deleted_at' => 'datetime', // [cite: 273]
        'kondisi_sebelumnya' => 'string',               // Enum // [cite: 273]
        'kondisi_sesudahnya' => 'string',               // Enum // [cite: 273]
        'status_ketersediaan_sebelumnya' => 'string',   // Enum // [cite: 274]
        'status_ketersediaan_sesudahnya' => 'string',   // Enum // [cite: 274]
    ];
    /**
     * Mendefinisikan relasi BelongsTo ke model BarangQrCode.
     * Satu catatan status barang terkait dengan satu unit barang.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function barangQrCode(): BelongsTo
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code'); // [cite: 277]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai pencatat perubahan).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userPencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pencatat'); // [cite: 279]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Ruangan (sebagai ruangan sebelumnya).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruanganSebelumnya(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_sebelumnya'); // [cite: 281]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Ruangan (sebagai ruangan sesudahnya).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruanganSesudahnya(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_sesudahnya'); // [cite: 283]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai pemegang personal sebelumnya).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pemegangPersonalSebelumnya(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pemegang_personal_sebelumnya'); // [cite: 285]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (sebagai pemegang personal sesudahnya).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pemegangPersonalSesudahnya(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pemegang_personal_sesudahnya'); // [cite: 287]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model DetailPeminjaman (sebagai pemicu).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function detailPeminjamanTrigger(): BelongsTo
    {
        return $this->belongsTo(DetailPeminjaman::class, 'id_detail_peminjaman_trigger'); // [cite: 289]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model MutasiBarang (sebagai pemicu).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mutasiBarangTrigger(): BelongsTo
    {
        return $this->belongsTo(MutasiBarang::class, 'id_mutasi_barang_trigger'); // [cite: 291]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Pemeliharaan (sebagai pemicu).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pemeliharaanTrigger(): BelongsTo
    {
        return $this->belongsTo(Pemeliharaan::class, 'id_pemeliharaan_trigger'); // [cite: 293]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model DetailStokOpname (sebagai pemicu).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function detailStokOpnameTrigger(): BelongsTo
    {
        return $this->belongsTo(DetailStokOpname::class, 'id_detail_stok_opname_trigger'); // [cite: 295]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model ArsipBarang (sebagai pemicu).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function arsipBarangTrigger(): BelongsTo
    {
        return $this->belongsTo(ArsipBarang::class, 'id_arsip_barang_trigger'); // [cite: 297]
    }
}
