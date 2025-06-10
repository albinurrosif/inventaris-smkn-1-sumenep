<?php

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_setting')) {
    /**
     * Mengambil nilai pengaturan dari database dengan caching.
     *
     * @param string $key
     * @return string|null
     */
    function get_setting(string $key)
    {
        // Ambil dari cache jika ada untuk performa
        return Cache::rememberForever('pengaturan.' . $key, function () use ($key) {
            $setting = Pengaturan::where('key', $key)->first();
            return $setting ? $setting->value : null;
        });
    }
}
