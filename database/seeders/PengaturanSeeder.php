<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengaturan;

class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'nama_sekolah',
                'value' => 'SMKN 1 Sumenep',
                'group' => 'Kop Surat',
                'description' => 'Nama lengkap sekolah untuk ditampilkan di kop surat laporan.'
            ],
            [
                'key' => 'alamat_sekolah',
                'value' => 'Jl. Pahlawan No. 28, Sumenep, Jawa Timur',
                'group' => 'Kop Surat',
                'description' => 'Alamat lengkap sekolah.'
            ],
            [
                'key' => 'logo_sekolah',
                'value' => 'logos/logo-default.png',
                'group' => 'Kop Surat',
                'type' => 'image',
                'description' => 'Upload logo untuk kop surat (format: PNG, JPG, maks 1MB).'
            ],
            [
                'key' => 'nama_kepala_sekolah',
                'value' => 'Nama Kepala Sekolah, S.Pd, M.Pd',
                'group' => 'Tanda Tangan Laporan',
                'description' => 'Nama lengkap kepala sekolah untuk tanda tangan di laporan.'
            ],
            [
                'key' => 'nip_kepala_sekolah',
                'value' => '19xxxxxxxx xxxxxxxx x xxx',
                'group' => 'Tanda Tangan Laporan',
                'description' => 'Nomor Induk Pegawai (NIP) kepala sekolah.'
            ],
        ];

        foreach ($settings as $setting) {
            Pengaturan::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
