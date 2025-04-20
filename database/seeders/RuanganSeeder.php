<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruangan;

class RuanganSeeder extends Seeder
{
    public function run(): void
    {
        Ruangan::create(['nama_ruangan' => 'Ruang Lab Komputer 1']);
        Ruangan::create(['nama_ruangan' => 'Ruang Guru']);
        Ruangan::create(['nama_ruangan' => 'Perpustakaan']);
    }
}
