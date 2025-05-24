<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriBarang extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan model ini
     *
     * @var string
     */
    protected $table = 'kategori_barang';

    /**
     * Atribut yang dapat diisi (mass assignable)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_kategori',
        'deskripsi'
    ];

    /**
     * Atribut yang harus di-casting
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke tabel Barang
     * Satu kategori dapat memiliki banyak barang
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'id_kategori');
    }

    /**
     * Mendapatkan jumlah barang dalam kategori ini
     * 
     * @return int
     */
    public function getItemCount(): int
    {
        return $this->barang()->count();
    }

    /**
     * Mendapatkan semua barang dalam kategori ini
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllItems()
    {
        return $this->barang()->get();
    }

    /**
     * Mendapatkan jumlah total unit fisik barang dalam kategori ini
     * Menghitung jumlah_barang dari semua item
     * 
     * @return int
     */
    public function getTotalUnitCount(): int
    {
        return $this->barang()->sum('jumlah_barang');
    }

    /**
     * Mendapatkan jumlah unit aktif (yang tersedia/tidak rusak) dalam kategori ini
     * 
     * @return int
     */
    public function getActiveUnitCount(): int
    {
        return $this->barang()
            ->whereHas('qrCodes', function ($query) {
                $query->where('kondisi', '!=', 'Rusak Berat')
                    ->where('status', '!=', 'Hilang');
            })
            ->count();
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Untuk membuat slug otomatis dari nama kategori jika diperlukan
        static::creating(function ($kategori) {
            // Kode tambahan jika diperlukan saat membuat kategori baru
        });
    }

    /**
     * Scope untuk mencari kategori berdasarkan nama
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('nama_kategori', 'LIKE', "%{$term}%")
            ->orWhere('deskripsi', 'LIKE', "%{$term}%");
    }

    /**
     * Mengecek apakah kategori memiliki barang
     * 
     * @return bool
     */
    public function hasItems(): bool
    {
        return $this->barang()->exists();
    }

    /**
     * Mendapatkan nilai total seluruh barang dalam kategori ini
     * 
     * @return float
     */
    public function getTotalValue(): float
    {
        return $this->barang()->sum('harga_beli');
    }
}
