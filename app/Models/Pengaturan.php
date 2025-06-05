<?php

// File: app/Models/Pengaturan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Model Pengaturan merepresentasikan konfigurasi atau pengaturan umum aplikasi
 * yang disimpan dalam format key-value.
 */
class Pengaturan extends Model
{
    use HasFactory; // Tidak menggunakan SoftDeletes untuk pengaturan.

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'pengaturans';

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
        'key',       // Kunci unik untuk pengaturan
        'value',     // Nilai pengaturan (disimpan sebagai teks)
        'deskripsi', // Deskripsi singkat mengenai pengaturan (opsional)
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Accessor untuk mendapatkan nilai pengaturan (`value`) dengan tipe data yang sesuai.
     * Mencoba mengkonversi nilai string dari database ke boolean, integer, float, atau array (JSON) jika memungkinkan.
     *
     * @return mixed Nilai pengaturan yang sudah diformat.
     */
    public function getFormattedValueAttribute(): mixed
    {
        $value = $this->attributes['value'];

        if (is_null($value)) {
            return null;
        }

        // Coba decode JSON jika value terlihat seperti JSON string
        if (is_string($value) && ((Str::startsWith($value, '{') && Str::endsWith($value, '}')) || (Str::startsWith($value, '[') && Str::endsWith($value, ']')))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded; // Kembalikan sebagai array jika JSON valid
            }
        }

        // Coba konversi ke boolean jika value adalah string 'true', 'false', '1', atau '0'
        if (is_string($value)) {
            $lowerValue = strtolower($value);
            if ($lowerValue === 'true' || $lowerValue === '1') return true;
            if ($lowerValue === 'false' || $lowerValue === '0') return false;
        }

        // Coba konversi ke numerik (integer atau float)
        if (is_string($value) && is_numeric($value)) {
            if (strpos($value, '.') !== false || stripos($value, 'e') !== false) {
                return (float) $value; // Kembalikan sebagai float jika ada desimal atau notasi ilmiah
            }
            return (int) $value; // Kembalikan sebagai integer
        }

        return $value; // Kembalikan sebagai string jika tidak ada konversi yang cocok
    }

    /**
     * Metode statis untuk mengatur (update atau membuat baru) sebuah pengaturan.
     * Nilai array/object akan di-encode ke JSON, boolean akan disimpan sebagai 'true'/'false'.
     *
     * @param string $key Kunci pengaturan.
     * @param mixed $value Nilai pengaturan.
     * @param string|null $deskripsi Deskripsi pengaturan (opsional).
     * @return self Instance Pengaturan yang berhasil disimpan.
     */
    public static function set(string $key, mixed $value, ?string $deskripsi = null): self
    {
        $saveValue = $value;
        if (is_array($value) || is_object($value)) {
            $saveValue = json_encode($value); // Encode array/object ke JSON string
        } elseif (is_bool($value)) {
            $saveValue = $value ? 'true' : 'false'; // Simpan boolean sebagai string 'true' atau 'false'
        }
        // Untuk tipe lain (string, int, float), simpan apa adanya (akan di-cast ke string oleh DB jika perlu)

        return self::updateOrCreate(
            ['key' => $key], // Kondisi untuk mencari record
            [
                'value'     => $saveValue, // Nilai yang akan disimpan/diupdate
                'deskripsi' => $deskripsi,
            ]
        );
    }

    /**
     * Metode statis untuk mendapatkan nilai pengaturan berdasarkan kuncinya.
     * Akan mengembalikan nilai default jika kunci tidak ditemukan.
     * Menggunakan accessor `formatted_value` untuk mendapatkan nilai dengan tipe yang sesuai.
     *
     * @param string $key Kunci pengaturan yang dicari.
     * @param mixed $default Nilai default yang akan dikembalikan jika kunci tidak ditemukan (default: null).
     * @return mixed Nilai pengaturan yang ditemukan atau nilai default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->formatted_value : $default;
    }
}
