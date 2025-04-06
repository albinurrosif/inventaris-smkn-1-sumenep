<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin SMKN 1',
            'email' => 'admin@smkn1sumenep.sch.id',
            'password' => Hash::make('password'), // Ganti saat produksi
            'role' => User::ROLE_ADMIN,
        ]);

        User::create([
            'name' => 'Operator Ruangan',
            'email' => 'operator@smkn1sumenep.sch.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        User::create([
            'name' => 'Guru Pengguna',
            'email' => 'guru@smkn1sumenep.sch.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_GURU,
        ]);
    }
}
