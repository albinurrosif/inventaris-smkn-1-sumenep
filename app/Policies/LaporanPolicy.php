<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaporanPolicy
{
    use HandlesAuthorization;

    /**
     * Memberikan hak akses super-user kepada Admin.
     */
    public function before(User $user, string $ability)
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
    }

    /**
     * Menentukan apakah pengguna dapat melihat halaman laporan inventaris.
     */
    public function viewInventaris(User $user)
    {
        // Izinkan Operator untuk mengakses halaman ini.
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * Menentukan apakah pengguna dapat melihat halaman laporan peminjaman.
     */
    public function viewPeminjaman(User $user)
    {
        // Izinkan Operator untuk mengakses halaman ini.
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * PERUBAHAN: Tambahkan method ini untuk laporan pemeliharaan.
     */
    public function viewPemeliharaan(User $user)
    {
        // Izinkan Operator untuk mengakses halaman ini.
        return $user->hasRole(User::ROLE_OPERATOR);
    }
}
