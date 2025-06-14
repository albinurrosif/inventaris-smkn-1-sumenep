<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Support\Str;

class RuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Membuat data Ruangan yang bervariasi...');

        // Cari operator untuk ditugaskan, jika tidak ada, gunakan admin pertama
        $operators = User::where('role', 'Operator')->get();
        if ($operators->isEmpty()) {
            $this->command->warn('Tidak ditemukan user dengan role Operator. Menetapkan ke user Admin pertama.');
            $operators = User::where('role', 'Admin')->limit(1)->get();
        }

        if ($operators->isEmpty()) {
            $this->command->error('Tidak ada user Operator atau Admin. Ruangan tidak dapat dibuat.');
            return;
        }

        // Kumpulan data ruangan yang lebih realistis dan banyak
        $daftarRuangan = [
            // Daftar Ruang Teori
            'Ruang Teori 01',
            'Ruang Teori 02',
            'Ruang Teori 03',
            'Ruang Teori 04',
            'Ruang Teori 05',
            'Ruang Teori 06',
            'Ruang Teori 07',
            'Ruang Teori 08',
            'Ruang Teori 09',
            'Ruang Teori 10',

            // Daftar Laboratorium Praktek Kejuruan
            'Lab. Rekayasa Perangkat Lunak',
            'Lab. Jaringan Komputer',
            'Lab. Multimedia',
            'Lab. Administrasi Perkantoran',
            'Lab. Akuntansi & Keuangan',
            'Lab. Pemasaran Online',
            'Lab. Perhotelan',
            'Lab. Bahasa',

            // Daftar Ruang Praktik & Bengkel
            'Bengkel Otomotif',
            'Dapur Praktik Tata Boga',
            'Teaching Factory (TEFA)',
            'Studio Fotografi & Broadcasting',
            'Edotel (Educational Hotel)',

            // Daftar Ruang Pimpinan & Staf
            'Ruang Kepala Sekolah',
            'Ruang Wakil Kepala Sekolah',
            'Ruang Tata Usaha',
            'Ruang Guru',
            'Ruang Bimbingan Konseling (BP)',
            'Ruang Hubungan Industri (Hubin)',

            // Fasilitas Umum & Penunjang
            'Perpustakaan',
            'UKS (Unit Kesehatan Sekolah)',
            'Ruang OSIS',
            'Kantin Sekolah',
            'Musholla',
            'Pos Satpam',
            'Gudang Inventaris',
            'Bank Mini Sekolah',
        ];

        foreach ($daftarRuangan as $namaRuangan) {
            Ruangan::firstOrCreate(
                ['nama_ruangan' => $namaRuangan],
                [
                    // Membuat kode ruangan unik dari nama ruangan
                    'kode_ruangan' => Str::upper(Str::slug($namaRuangan, '_')),
                    // Menugaskan operator secara acak
                    'id_operator' => $operators->random()->id
                ]
            );
        }

        $this->command->info(count($daftarRuangan) . ' ruangan berhasil dibuat atau ditemukan.');
    }
}
