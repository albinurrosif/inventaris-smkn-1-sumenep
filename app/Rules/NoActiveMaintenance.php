<?php

namespace App\Rules;

use App\Models\Pemeliharaan;
use Illuminate\Contracts\Validation\Rule;

class NoActiveMaintenance implements Rule
{
    public function passes($attribute, $value)
    {
        // Atribut ini akan lolos jika TIDAK ADA pemeliharaan aktif untuk barang ini.
        // $value adalah id_barang_qr_code yang dipilih dari form.

        $finalStatuses = [
            Pemeliharaan::STATUS_PENGAJUAN_DITOLAK,
            Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
        ];

        return !Pemeliharaan::where('id_barang_qr_code', $value)
            ->whereNotIn('status_pengajuan', [$finalStatuses[0]])
            ->whereNotIn('status_pengerjaan', [$finalStatuses[1]])
            ->exists();
    }

    public function message()
    {
        return 'Barang ini sudah memiliki laporan pemeliharaan yang aktif dan belum selesai.';
    }
}
