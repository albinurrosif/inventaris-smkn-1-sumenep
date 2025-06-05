<?php

namespace App\Policies;

use App\Models\KategoriBarang;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KategoriBarangPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar kategori barang.
     * Admin dan Operator bisa melihat daftar kategori.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) || $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail kategori barang tertentu.
     * Admin dan Operator bisa melihat detail.
     */
    public function view(User $user, KategoriBarang $kategoriBarang): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) || $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Menentukan apakah pengguna dapat membuat kategori barang baru.
     * Hanya Admin yang bisa membuat kategori baru.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); //
    }

    /**
     * Menentukan apakah pengguna dapat mengupdate kategori barang tertentu.
     * Hanya Admin yang bisa mengupdate kategori.
     */
    public function update(User $user, KategoriBarang $kategoriBarang): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); //
    }

    /**
     * Menentukan apakah pengguna dapat menghapus kategori barang tertentu.
     * Hanya Admin yang bisa menghapus.
     */
    public function delete(User $user, KategoriBarang $kategoriBarang): bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) { //
            // Pengecekan apakah kategori masih digunakan oleh barang akan ditangani
            // oleh database constraint (ON DELETE RESTRICT) atau controller jika diperlukan pesan custom.
            // if ($kategoriBarang->barangs()->exists()) {
            // return false;
            // }
            return true; //
        }
        return false; //
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan kategori yang di-soft-delete.
     * Hanya Admin.
     */
    public function restore(User $user, KategoriBarang $kategoriBarang): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); //
    }

    /**
     * Menentukan apakah pengguna dapat menghapus kategori secara permanen.
     * Hanya Admin.
     */
    // public function forceDelete(User $user, KategoriBarang $kategoriBarang): bool
    // {
    // return $user->hasRole(User::ROLE_ADMIN);
    // }
}
