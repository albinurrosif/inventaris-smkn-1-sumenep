<?php

namespace App\Policies;

use App\Models\RekapStok;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RekapStokPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Hanya Admin yang dapat melihat semua data rekap stok.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can view the model.
     * Hanya Admin yang dapat melihat detail spesifik rekap stok.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RekapStok  $rekapStok
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, RekapStok $rekapStok)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can create models.
     * Rekap stok dibuat oleh sistem, bukan manual.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can update the model.
     * Rekap stok tidak seharusnya diupdate manual.
     */
    public function update(User $user, RekapStok $rekapStok): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Rekap stok tidak seharusnya dihapus manual.
     */
    public function delete(User $user, RekapStok $rekapStok): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); // Mungkin hanya untuk kasus khusus oleh Admin
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RekapStok $rekapStok): bool
    {
        return false; // Rekap stok tidak menggunakan soft delete
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RekapStok $rekapStok): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); // Mungkin hanya untuk kasus khusus oleh Admin
    }
}
