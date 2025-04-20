<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
        ['email' => 'admin@smkn1sumenep.sch.id'],
        [
            'name' => 'Admin SMKN 1',
            'password' => Hash::make('password'), // sesuaikan
            'role' => 'Admin',
        ]
    );

        User::firstOrCreate(
        ['email' => 'admin@smkn1sumenep.sch.id'],
        [
            'name' => 'Admin SMKN 1',
            'password' => Hash::make('password'), // sesuaikan
            'role' => 'Admin',
        ]
    );

        User::firstOrCreate(
        ['email' => 'admin@smkn1sumenep.sch.id'],
        [
            'name' => 'Admin SMKN 1',
            'password' => Hash::make('password'), // sesuaikan
            'role' => 'Admin',
        ]
    );
    }
}
