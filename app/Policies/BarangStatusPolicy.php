<?php

namespace App\Policies;

use App\Models\BarangStatus;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarangStatusPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Hanya Admin yang dapat melihat semua riwayat status barang.
     * Operator mungkin bisa melihat riwayat barang dalam lingkupnya (implementasi lebih lanjut jika diperlukan).
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // return $user->hasRole(User::ROLE_ADMIN);
        // // Pertimbangkan:
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    /**
     * Determine whether the user can view the model.
     * Hanya Admin yang dapat melihat detail spesifik riwayat status.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BarangStatus  $barangStatus
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, BarangStatus $barangStatus)
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        // Jika Operator diizinkan, perlu logika untuk memeriksa apakah $barangStatus terkait dengan lingkup Operator.
        // Misalnya, melalui $barangStatus->barangQrCode->id_ruangan
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Riwayat status dibuat secara otomatis oleh sistem.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     * Riwayat status tidak seharusnya diubah.
     */
    public function update(User $user, BarangStatus $barangStatus): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Riwayat status tidak seharusnya dihapus.
     */
    public function delete(User $user, BarangStatus $barangStatus): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BarangStatus $barangStatus): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BarangStatus $barangStatus): bool
    {
        return false;
    }
}
