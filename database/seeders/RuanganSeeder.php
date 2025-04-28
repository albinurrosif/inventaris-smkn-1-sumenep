<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruangan;

class RuanganSeeder extends Seeder
{
    public function run(): void
    {
        Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang Lab Komputer 1']);
        Ruangan::firstOrCreate(['nama_ruangan' => 'Ruang Guru']);
        Ruangan::firstOrCreate(['nama_ruangan' => 'Perpustakaan']);
    }
}
