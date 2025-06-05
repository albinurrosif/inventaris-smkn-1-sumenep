<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruangan;
use App\Models\User;

class RuanganSeeder extends Seeder
{
    public function run(): void
    {
        $operator1 = User::where('role', User::ROLE_OPERATOR)->skip(0)->first();
        $operator2 = User::where('role', User::ROLE_OPERATOR)->skip(1)->first();

        if (!$operator1) {
            $this->command->warn('Tidak ada user dengan role Operator. Jalankan UserSeeder terlebih dahulu.');
            return;
        }

        $ruangan = [
            ['nama' => 'Laboratorium RPL 1', 'kode' => 'LAB-RPL-1', 'operator' => $operator1->id],
            ['nama' => 'Laboratorium TKJ 1', 'kode' => 'LAB-TKJ-1', 'operator' => $operator1->id],
            ['nama' => 'Ruang Guru Umum', 'kode' => 'RG-UMUM', 'operator' => $operator2->id ?? $operator1->id],
            ['nama' => 'Perpustakaan', 'kode' => 'PERPUS', 'operator' => $operator2->id ?? $operator1->id],
            ['nama' => 'Ruang Kepala Sekolah', 'kode' => 'KS-01', 'operator' => $operator2->id ?? $operator1->id],
        ];

        foreach ($ruangan as $data) {
            Ruangan::firstOrCreate(
                ['kode_ruangan' => $data['kode']], // Menggunakan kode_ruangan sebagai unique key [cite: 26]
                [
                    'nama_ruangan' => $data['nama'], // [cite: 26]
                    'id_operator' => $data['operator'], // [cite: 26]
                ]
            );
        }
    }
}
