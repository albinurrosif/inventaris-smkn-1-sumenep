<?php

namespace App\Policies;

use App\Models\ArsipBarang;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArsipBarangPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Hanya Admin yang dapat melihat daftar arsip barang.
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
     * Hanya Admin yang dapat melihat detail entri arsip barang.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ArsipBarang  $arsipBarang
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ArsipBarang $arsipBarang)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can create models.
     * ArsipBarang dibuat secara otomatis oleh sistem, bukan manual oleh user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return false; // Tidak ada pembuatan manual
    }

    /**
     * Determine whether the user can update the model.
     * ArsipBarang tidak seharusnya diupdate.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ArsipBarang  $arsipBarang
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ArsipBarang $arsipBarang)
    {
        return false; // Tidak ada update manual
    }

    /**
     * Determine whether the user can delete the model.
     * ArsipBarang tidak seharusnya dihapus (kecuali mungkin force delete oleh super admin).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ArsipBarang  $arsipBarang
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ArsipBarang $arsipBarang)
    {
        return $user->hasRole(User::ROLE_ADMIN); // Mungkin hanya untuk force delete
    }

    /**
     * Determine whether the user can restore the model.
     * Aksi restore sebenarnya dilakukan pada BarangQrCode, bukan pada ArsipBarang.
     * Namun, kita bisa memiliki policy ini jika ada logika spesifik terkait arsip.
     * Untuk sekarang, kita asumsikan otorisasi restore dilakukan pada BarangQrCodePolicy.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ArsipBarang  $arsipBarang
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ArsipBarang $arsipBarang)
    {
        // Otorisasi untuk memulihkan unit barang terkait akan dicek melalui BarangQrCodePolicy
        // Namun, untuk mengakses fitur restore dari daftar arsip, user harus admin.
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ArsipBarang  $arsipBarang
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ArsipBarang $arsipBarang)
    {
        return $user->hasRole(User::ROLE_ADMIN); // Hanya admin yang boleh hapus permanen
    }
}
