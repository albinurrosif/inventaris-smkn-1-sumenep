<?php

// File: app/Models/MutasiBarang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model MutasiBarang merepresentasikan histori perpindahan unit barang antar ruangan.
 */
class MutasiBarang extends Model
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
    protected $table = 'mutasi_barangs';

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
        'id_barang_qr_code',    // FK ke unit barang yang dimutasi
        'id_ruangan_asal',      // FK ke ruangan asal
        'id_ruangan_tujuan',    // FK ke ruangan tujuan
        'tanggal_mutasi',       // Tanggal terjadinya mutasi
        'alasan_pemindahan',   // Alasan mengapa barang dipindahkan
        'id_user_admin',        // User (admin/operator) yang melakukan mutasi
        'surat_pemindahan_path', // Path ke file dokumen surat pemindahan (jika ada)
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_mutasi' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi BelongsTo ke model BarangQrCode.
     * Satu catatan mutasi terkait dengan satu unit barang.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function barangQrCode(): BelongsTo
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Ruangan (sebagai ruangan asal).
     * Satu catatan mutasi memiliki satu ruangan asal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruanganAsal(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_asal');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Ruangan (sebagai ruangan tujuan).
     * Satu catatan mutasi memiliki satu ruangan tujuan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruanganTujuan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_tujuan');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (admin/operator yang melakukan mutasi).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_admin');
    }

    /**
     * Metode boot model untuk mendaftarkan event listener.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Setelah catatan mutasi baru dibuat, update lokasi (id_ruangan)
        // dan status pemegang personal pada BarangQrCode yang terkait.
        static::created(function ($mutasi) {
            if ($mutasi->barangQrCode) {
                $barangQr = $mutasi->barangQrCode;
                $barangQr->id_ruangan = $mutasi->id_ruangan_tujuan;
                // Jika barang dimutasi ke sebuah ruangan, maka tidak lagi dipegang personal.
                $barangQr->id_pemegang_personal = null;
                $barangQr->save();

                // Mencatat perubahan ini di BarangStatus
                BarangStatus::create([
                    'id_barang_qr_code' => $barangQr->id,
                    'id_user_pencatat' => $mutasi->id_user_admin, // User yang melakukan mutasi
                    'tanggal_pencatatan' => now(),
                    'id_ruangan_sebelumnya' => $mutasi->id_ruangan_asal,
                    'id_ruangan_sesudahnya' => $mutasi->id_ruangan_tujuan,
                    // Jika sebelumnya dipegang personal, catat juga perubahan pemegang personal
                    // 'id_pemegang_personal_sebelumnya' => ID pemegang sebelumnya jika ada,
                    'id_pemegang_personal_sesudahnya' => null,
                    'deskripsi_kejadian' => 'Mutasi barang dari ' . ($mutasi->ruanganAsal->nama_ruangan ?? 'N/A') . ' ke ' . ($mutasi->ruanganTujuan->nama_ruangan ?? 'N/A') . '. Mutasi ID: ' . $mutasi->id,
                    'id_mutasi_barang_trigger' => $mutasi->id,
                ]);
            }
        });
    }
}
