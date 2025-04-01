<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    use HasFactory;

    protected $table = 'pengaturan';

    protected $fillable = ['key', 'value', 'kategori', 'tipe', 'deskripsi'];

    /**
     * Mengembalikan nilai pengaturan dengan tipe data yang sesuai.
     *
     * @return mixed
     */
    public function getFormattedValueAttribute()
    {
        return match ($this->tipe) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Mengupdate atau membuat pengaturan baru.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $kategori
     * @param string $tipe
     * @param string|null $deskripsi
     * @return self
     */
    public static function set($key, $value, $kategori = null, $tipe = 'string', $deskripsi = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'kategori' => $kategori,
                'tipe' => $tipe,
                'deskripsi' => $deskripsi,
            ]
        );
    }

    /**
     * Mendapatkan nilai pengaturan berdasarkan key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->formatted_value : $default;
    }
}
