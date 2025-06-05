<?php

// File: app/Models/Barang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Barang merepresentasikan data induk atau jenis barang.
 * Setiap jenis barang dapat memiliki banyak unit fisik (BarangQrCode).
 */
class Barang extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'barangs';

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
     * Atribut tanggal yang harus diperlakukan sebagai instance Carbon.
     * Digunakan untuk SoftDeletes.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_kategori',
        'nama_barang',
        'kode_barang',
        'merk_model',
        'ukuran',
        'bahan',
        'tahun_pembuatan',
        'harga_perolehan_induk',
        'sumber_perolehan_induk',
        'total_jumlah_unit', // Dikelola otomatis oleh BarangQrCode model events
        'menggunakan_nomor_seri',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'menggunakan_nomor_seri' => 'boolean',
        'tahun_pembuatan' => 'integer',
        'harga_perolehan_induk' => 'decimal:2',
        'total_jumlah_unit' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi BelongsTo ke model KategoriBarang.
     * Satu jenis barang (induk) dimiliki oleh satu kategori.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'id_kategori');
    }

    /**
     * Mendefinisikan relasi HasMany ke model BarangQrCode.
     * Satu jenis barang (induk) dapat memiliki banyak unit fisik (BarangQrCode).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(BarangQrCode::class, 'id_barang');
    }

    /**
     * Memeriksa apakah jenis barang (induk) ini dapat di-soft delete.
     * Dapat di-soft delete jika tidak ada unit fisik (BarangQrCode) aktif yang terkait.
     *
     * @return bool True jika dapat di-soft delete, false jika tidak.
     */
    public function canSoftDelete(): bool
    {
        return $this->qrCodes()->whereNull('deleted_at')->count() === 0;
    }

    /**
     * Memeriksa apakah data jenis barang (induk) ini belum lengkap.
     * Contoh: jika menggunakan nomor seri tapi belum ada unit fisik yang ditambahkan.
     *
     * @return bool True jika data belum lengkap, false jika sudah.
     */
    public function isIncomplete(): bool
    {
        if ($this->menggunakan_nomor_seri && $this->qrCodes()->whereNull('deleted_at')->count() == 0) {
            return true;
        }
        // Tambahkan kriteria lain jika perlu
        return false;
    }

    /**
     * Scope query untuk memfilter jenis barang berdasarkan ID kategori.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Instance query builder.
     * @param int $categoryId ID kategori yang akan difilter.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('id_kategori', $categoryId);
    }

    /**
     * Accessor untuk mendapatkan jumlah unit BarangQrCode yang aktif (tidak di-soft delete) secara dinamis.
     * Berguna sebagai alternatif atau pengecekan terhadap kolom `total_jumlah_unit`.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function jumlahBarang(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->qrCodes()->whereNull('deleted_at')->count(),
        );
    }

    /**
     * Metode boot model untuk mendaftarkan event listener.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Saat jenis barang (induk) di-soft delete, semua unit fisik (BarangQrCode) terkait juga di-soft delete.
        static::deleting(function ($barang) {
            if (!$barang->isForceDeleting()) { // Hanya untuk soft delete
                $barang->qrCodes()->each(function ($qrCode) {
                    $qrCode->delete(); // Soft delete unit terkait
                });
            }
            // Jika force deleting, constraint ON DELETE CASCADE di database akan menangani penghapusan qrCodes.
        });

        // Saat jenis barang (induk) di-restore dari soft delete,
        // semua unit fisik (BarangQrCode) terkait yang ter-soft delete bersamanya juga di-restore.
        static::restoring(function ($barang) {
            $barang->qrCodes()->onlyTrashed()->each(function ($qrCode) use ($barang) {
                // Pastikan qrCode terhapus setelah atau bersamaan dengan barang induknya
                if ($qrCode->deleted_at && $barang->deleted_at && $qrCode->deleted_at >= $barang->deleted_at) {
                    $qrCode->restore(); // Restore unit terkait
                }
            });
        });
    }
}
