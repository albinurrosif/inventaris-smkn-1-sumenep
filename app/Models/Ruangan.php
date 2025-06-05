<?php

// File: app/Models/Ruangan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Ruangan merepresentasikan lokasi fisik di sekolah.
 */
class Ruangan extends Model
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
    protected $table = 'ruangans';

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
        'nama_ruangan',
        'kode_ruangan',
        'id_operator', // Operator penanggung jawab ruangan
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi HasMany ke model BarangQrCode.
     * Satu ruangan dapat memiliki banyak unit barang fisik (BarangQrCode).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function barangQrCodes(): HasMany
    {
        return $this->hasMany(BarangQrCode::class, 'id_ruangan', 'id');
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User.
     * Satu ruangan dikelola oleh satu operator (pengguna).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_operator');
    }

    /**
     * Mendapatkan jumlah total unit fisik (BarangQrCode) di ruangan ini yang tidak di-soft delete.
     *
     * @return int
     */
    public function getTotalUnitCount(): int
    {
        return $this->barangQrCodes()->whereNull('deleted_at')->count();
    }

    /**
     * Mendapatkan jumlah unit fisik yang tersedia (status 'Tersedia') di ruangan ini
     * yang tidak di-soft delete.
     *
     * @return int
     */
    public function getAvailableUnitCount(): int
    {
        return $this->barangQrCodes()
            ->where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Mendapatkan nilai total (berdasarkan harga perolehan unit) dari semua unit fisik (BarangQrCode)
     * di ruangan ini yang tidak di-soft delete.
     *
     * @return float
     */
    public function getTotalValue(): float
    {
        return (float) $this->barangQrCodes()->whereNull('deleted_at')->sum('harga_perolehan_unit');
    }

    /**
     * Scope query untuk mencari ruangan berdasarkan nama atau kode ruangan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Instance query builder.
     * @param string $term Kata kunci pencarian.
     * @return \Illuminate\Database\Eloquent\Builder
     */

    public function scopeSearch($query, string $term)
    {
        return $query->where('nama_ruangan', 'LIKE', "%{$term}%")
            ->orWhere('kode_ruangan', 'LIKE', "%{$term}%");
    }

    /**
     * Metode boot model untuk mendaftarkan event listener.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Event listener saat ruangan akan di-soft delete.
        // Mencegah penghapusan jika masih ada barang aktif di dalamnya,
        // sesuai dengan constraint ON DELETE RESTRICT di database.
        static::deleting(function ($ruangan) {
            if (!$ruangan->isForceDeleting()) {
                if ($ruangan->barangQrCodes()->whereNull('deleted_at')->count() > 0) {
                    // Melemparkan exception untuk menghentikan proses delete dan memberikan pesan.
                    // Atau, Anda bisa mengatur agar ini mengembalikan false untuk menghentikan delete secara diam-diam,
                    // namun exception lebih informatif.
                    // throw new \Exception("Tidak dapat menghapus ruangan '{$ruangan->nama_ruangan}' karena masih terdapat barang di dalamnya. Pindahkan atau arsipkan barang terlebih dahulu.");
                    // Alternatif: biarkan database constraint yang menangani dan menampilkan error SQL.
                    // Jika ingin tetap soft delete ruangan dan barangnya, logika cascade soft delete bisa ditambahkan di sini.
                }
            }
        });
    }
}
