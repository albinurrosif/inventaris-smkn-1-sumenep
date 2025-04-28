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
                'name' => 'Admin',
                'password' => Hash::make('password'), // sesuaikan
                'role' => 'Admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator@smkn1sumenep.sch.id'],
            [
                'name' => 'Operator 1',
                'password' => Hash::make('password'), // sesuaikan
                'role' => 'Operator',
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator2@smkn1sumenep.sch.id'],
            [
                'name' => 'Operator 2',
                'password' => Hash::make('password'), // sesuaikan
                'role' => 'Operator',
            ]
        );

        User::firstOrCreate(
            ['email' => 'guru@smkn1sumenep.sch.id'],
            [
                'name' => 'Guru 1',
                'password' => Hash::make('password'), // sesuaikan
                'role' => 'Guru',
            ]
        );

        User::firstOrCreate(
            ['email' => 'guru2@smkn1sumenep.sch.id'],
            [
                'name' => 'Guru 2',
                'password' => Hash::make('password'), // sesuaikan
                'role' => 'Guru',
            ]
        );
    }
}
