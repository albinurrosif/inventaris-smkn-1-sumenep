<?php

namespace App\Policies;

use App\Models\KategoriBarang;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KategoriBarangPolicy
{
    use HandlesAuthorization;

    /**
     * Berikan akses penuh untuk Admin untuk semua aksi.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        return null;
    }

    /**
     * Izinkan Operator untuk bisa MELIHAT daftar kategori.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * Izinkan Operator untuk bisa MELIHAT detail satu kategori.
     */
    public function view(User $user, KategoriBarang $kategoriBarang): bool
    {
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * JANGAN izinkan non-admin untuk membuat kategori baru.
     */
    public function create(User $user): bool
    {
        return false; // Hanya Admin (sudah di-handle oleh before())
    }

    /**
     * JANGAN izinkan non-admin untuk mengupdate kategori.
     */
    public function update(User $user, KategoriBarang $kategoriBarang): bool
    {
        return false; // Hanya Admin
    }

    /**
     * JANGAN izinkan non-admin untuk menghapus kategori.
     */
    public function delete(User $user, KategoriBarang $kategoriBarang): bool
    {
        // Pengecekan relasi sudah ada di controller, policy ini hanya untuk hak akses.
        return false; // Hanya Admin
    }

    /**
     * JANGAN izinkan non-admin untuk memulihkan kategori.
     */
    public function restore(User $user, KategoriBarang $kategoriBarang): bool
    {
        return false; // Hanya Admin
    }

    /**
     * JANGAN izinkan non-admin untuk menghapus permanen.
     */
    public function forceDelete(User $user, KategoriBarang $kategoriBarang): bool
    {
        return false; // Hanya Admin
    }
}
