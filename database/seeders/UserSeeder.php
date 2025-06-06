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
                'username' => 'admin', // Ditambahkan [cite: 16]
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN, // Menggunakan konstanta [cite: 147]
            ]
        );

        User::firstOrCreate(
            ['email'=>  'operator@smkn1sumenep.sch.id'],
            [
                'username' => 'operator1', // Ditambahkan [cite: 16]
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR, // Menggunakan konstanta [cite: 148]
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator2@smkn1sumenep.sch.id'],
            [
                'username' => 'operator2', // Ditambahkan [cite: 16]
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR, // Menggunakan konstanta [cite: 148]
            ]
        );

        User::firstOrCreate(
            ['email' => 'guru@smkn1sumenep.sch.id'],
            [
                'username' => 'guru1', // Ditambahkan [cite: 16]
                'password' => Hash::make('password'),
                'role' => User::ROLE_GURU, // Menggunakan konstanta [cite: 149]
            ]
        );

        User::firstOrCreate(
            ['email' => 'guru2@smkn1sumenep.sch.id'],
            [
                'username' => 'guru2', // Ditambahkan [cite: 16]
                'password' => Hash::make('password'),
                'role' => User::ROLE_GURU, // Menggunakan konstanta [cite: 149]
            ]
        );
    }
}
