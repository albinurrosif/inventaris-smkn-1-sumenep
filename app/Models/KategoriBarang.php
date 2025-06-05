<?php

// File: app/Models/KategoriBarang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Model KategoriBarang merepresentasikan kategori untuk mengelompokkan barang.
 */
class KategoriBarang extends Model
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
    protected $table = 'kategori_barangs';

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
        'nama_kategori',
        'slug',
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
     * Mendefinisikan relasi HasMany ke model Barang.
     * Satu kategori dapat memiliki banyak jenis barang (induk).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function barangs(): HasMany
    {
        return $this->hasMany(Barang::class, 'id_kategori');
    }

    /**
     * Mendapatkan jumlah jenis barang (induk) dalam kategori ini.
     *
     * @return int
     */
    public function getItemCount(): int
    {
        return $this->barangs()->count();
    }

    /**
     * Mendapatkan semua jenis barang (induk) dalam kategori ini.
     *
     * @return \Illuminate\Database\Eloquent\Collection<\App\Models\Barang>
     */
    public function getAllItems(): Collection
    {
        return $this->barangs()->get();
    }

    /**
     * Mendapatkan jumlah total unit fisik barang (BarangQrCode) dalam kategori ini.
     * Menghitung jumlah BarangQrCode dari semua jenis barang di kategori ini yang tidak di-soft delete.
     *
     * @return int
     */
    public function getTotalUnitCount(): int
    {
        return BarangQrCode::whereHas('barang', function ($query) {
            $query->where('id_kategori', $this->id);
        })
            ->whereNull('deleted_at') // Hanya unit yang tidak di-soft delete
            ->count();
    }

    /**
     * Mendapatkan jumlah unit aktif (Tersedia) dalam kategori ini.
     *
     * @return int
     */
    public function getAvailableUnitCount(): int
    {
        return BarangQrCode::whereHas('barang', function ($query) {
            $query->where('id_kategori', $this->id);
        })
            ->where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Metode boot model untuk mendaftarkan event listener.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Otomatis mengisi slug dari nama_kategori saat membuat record baru, jika slug kosong.
        static::creating(function ($kategori) {
            if (empty($kategori->slug)) {
                $kategori->slug = Str::slug($kategori->nama_kategori);
            }
        });

        // Otomatis mengisi slug dari nama_kategori saat memperbarui record, jika nama_kategori berubah.
        static::updating(function ($kategori) {
            if ($kategori->isDirty('nama_kategori')) {
                $kategori->slug = Str::slug($kategori->nama_kategori);
            }
        });

        // Saat kategori di-soft delete, semua barang (induk) terkait juga di-soft delete.
        static::deleting(function ($kategori) {
            if (!$kategori->isForceDeleting()) { // Hanya berlaku untuk soft delete
                $kategori->barangs()->each(function ($barang) {
                    $barang->delete(); // Soft delete barang terkait
                });
            }
        });

        // Saat kategori di-restore dari soft delete, semua barang (induk) terkait yang ter-soft delete juga di-restore.
        static::restoring(function ($kategori) {
            $kategori->barangs()->onlyTrashed()->each(function ($barang) {
                $barang->restore(); // Restore barang terkait yang ter-soft delete
            });
        });
    }

    /**
     * Scope query untuk mencari kategori berdasarkan nama atau slug.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Instance query builder.
     * @param string $term Kata kunci pencarian.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('nama_kategori', 'LIKE', "%{$term}%")
            ->orWhere('slug', 'LIKE', "%{$term}%");
    }

    /**
     * Memeriksa apakah kategori memiliki jenis barang (induk) yang terkait.
     *
     * @return bool True jika ada barang terkait, false jika tidak.
     */
    public function hasItems(): bool
    {
        return $this->barangs()->exists();
    }

    /**
     * Mendapatkan nilai total (berdasarkan harga perolehan unit) dari semua unit fisik (BarangQrCode)
     * dalam kategori ini yang tidak di-soft delete.
     *
     * @return float
     */
    public function getTotalValue(): float
    {
        $totalValue = BarangQrCode::whereHas('barang', function ($query) {
            $query->where('id_kategori', $this->id);
        })
            ->whereNull('deleted_at')
            ->sum('harga_perolehan_unit');

        return (float) $totalValue;
    }
}
