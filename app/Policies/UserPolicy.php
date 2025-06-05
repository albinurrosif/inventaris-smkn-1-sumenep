<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Admin dapat melihat semua daftar pengguna.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Menggunakan konstanta ROLE_ADMIN dari model User
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can view the model.
     * Admin dapat melihat detail pengguna manapun.
     * Pengguna biasa hanya bisa melihat detail dirinya sendiri.
     *
     * @param  \App\Models\User  $user  // Pengguna yang sedang login
     * @param  \App\Models\User  $model // Model User yang ingin dilihat detailnya
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, User $model)
    {
        return $user->hasRole(User::ROLE_ADMIN) || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     * Hanya Admin yang dapat membuat pengguna baru.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can update the model.
     * Admin dapat mengupdate pengguna manapun.
     * Pengguna biasa bisa mengupdate profilnya sendiri (dengan batasan tertentu mungkin).
     *
     * @param  \App\Models\User  $user  // Pengguna yang sedang login
     * @param  \App\Models\User  $model // Model User yang ingin diupdate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, User $model)
    {
        // Admin bisa update siapa saja.
        // Jika Anda ingin user bisa update profil sendiri, tambahkan: || $user->id === $model->id
        // Hati-hati jika user diizinkan mengubah rolenya sendiri.
        return $user->hasRole(User::ROLE_ADMIN) || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     * Hanya Admin yang dapat menghapus pengguna.
     * Admin tidak boleh menghapus dirinya sendiri.
     *
     * @param  \App\Models\User  $user  // Pengguna yang sedang login
     * @param  \App\Models\User  $model // Model User yang ingin dihapus
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, User $model)
    {
        // Admin tidak bisa menghapus dirinya sendiri
        if ($user->id === $model->id && $user->hasRole(User::ROLE_ADMIN)) {
            return false;
        }
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can restore the model.
     * Hanya Admin yang dapat memulihkan pengguna yang di-soft-delete.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, User $model)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Hanya Admin yang dapat menghapus pengguna secara permanen.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        // Admin tidak bisa menghapus dirinya sendiri secara permanen
        if ($user->id === $model->id && $user->hasRole(User::ROLE_ADMIN)) {
            return false;
        }
        return $user->hasRole(User::ROLE_ADMIN);
    }
}
