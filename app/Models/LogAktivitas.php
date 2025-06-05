<?php

// File: app/Models/LogAktivitas.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Model LogAktivitas merepresentasikan catatan (log) dari berbagai aktivitas
 * yang terjadi dalam sistem, seperti penambahan data, perubahan, atau penghapusan.
 */
class LogAktivitas extends Model
{
    use HasFactory; // Tidak menggunakan SoftDeletes untuk log.

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'log_aktivitas';

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
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_user',              // User yang melakukan aktivitas (opsional)
        'aktivitas',            // Deskripsi singkat aktivitas
        'model_terkait',        // Nama kelas model yang terkait dengan aktivitas (misal: 'App\Models\BarangQrCode')
        'id_model_terkait',     // ID dari record model yang terkait
        'data_lama',            // Snapshot data sebelum perubahan (JSON)
        'data_baru',            // Snapshot data setelah perubahan (JSON)
        'ip_address',           // Alamat IP pengguna saat melakukan aktivitas
        'user_agent',           // User agent browser pengguna
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_lama' => 'array', // JSON di database di-cast ke array PHP
        'data_baru' => 'array', // JSON di database di-cast ke array PHP
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi BelongsTo ke model User.
     * Satu log aktivitas dapat dilakukan oleh satu pengguna (jika id_user diisi).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Mendefinisikan relasi polimorfik MorphTo.
     * Memungkinkan log aktivitas untuk berelasi dengan berbagai jenis model lain
     * (misalnya, BarangQrCode, Peminjaman, dll.) berdasarkan kolom `model_terkait` dan `id_model_terkait`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo|null
     */
    public function modelTerkait(): ?MorphTo
    {
        // Pastikan model_terkait dan id_model_terkait ada sebelum mencoba memuat relasi
        if ($this->model_terkait && $this->id_model_terkait) {
            // Nama fungsi relasi (argumen pertama) bisa sama dengan nama metode ini
            return $this->morphTo(__FUNCTION__, 'model_terkait', 'id_model_terkait');
        }
        return null;
    }
}
