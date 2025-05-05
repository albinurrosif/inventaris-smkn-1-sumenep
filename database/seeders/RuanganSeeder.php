<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruangan;
use App\Models\User; // Import model User

class RuanganSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan user operator ada
        $operator1 = User::where('email', 'operator@smkn1sumenep.sch.id')->first();
        $operator2 = User::where('email', 'operator2@smkn1sumenep.sch.id')->first();

        if (!$operator1 && !$operator2) {
            $this->command->warn('Tidak ada operator yang ditemukan. Pastikan UserSeeder sudah dijalankan.');
            return; // Hentikan seeder jika tidak ada operator
        }

        // Buat ruangan dan tetapkan operatornya
        Ruangan::firstOrCreate(
            ['nama_ruangan' => 'Ruang Lab Komputer 1'],
            ['id_operator' => $operator1->id] // Tetapkan operator
        );
        Ruangan::firstOrCreate(
            ['nama_ruangan' => 'Ruang Guru'],
            ['id_operator' => $operator2->id] // Tetapkan operator
        );
        Ruangan::firstOrCreate(
            ['nama_ruangan' => 'Perpustakaan'],
            ['id_operator' => $operator1->id] // Tetapkan operator
        );
    }
}
