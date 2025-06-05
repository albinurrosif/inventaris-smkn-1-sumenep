<?php

namespace App\Policies;

use App\Models\Pemeliharaan;
use App\Models\User;
use App\Models\BarangQrCode; // Untuk memeriksa kepemilikan ruangan oleh operator
use Illuminate\Auth\Access\HandlesAuthorization;

class PemeliharaanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Admin bisa lihat semua. Operator bisa lihat pemeliharaan terkait barang di ruangannya atau yang dia laporkan.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    /**
     * Determine whether the user can view the model.
     * Admin bisa lihat semua. Operator bisa lihat jika terkait barang di ruangannya atau dia pelapornya.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pemeliharaan  $pemeliharaan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Pemeliharaan $pemeliharaan)
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Operator adalah pelapor
            if ($pemeliharaan->id_user_pelapor === $user->id) {
                return true;
            }
            // Barang terkait ada di ruangan yang dikelola operator
            if ($pemeliharaan->barangQrCode && $pemeliharaan->barangQrCode->id_ruangan) {
                return $user->ruanganYangDiKelola()->where('id', $pemeliharaan->barangQrCode->id_ruangan)->exists();
            }
            // Jika barang dipegang personal oleh operator tersebut
            if ($pemeliharaan->barangQrCode && $pemeliharaan->barangQrCode->id_pemegang_personal === $user->id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Admin bisa buat untuk barang manapun. Operator bisa buat untuk barang di ruangannya.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    /**
     * Determine whether the user can update the model.
     * Admin bisa update semua. Operator bisa update pengajuannya jika status masih 'Diajukan'.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pemeliharaan  $pemeliharaan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Pemeliharaan $pemeliharaan)
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Operator hanya bisa update jika dia pelapor DAN status masih 'Diajukan'
            return $pemeliharaan->id_user_pelapor === $user->id && $pemeliharaan->status_pemeliharaan === Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN;
        }
        return false;
    }

    /**
     * Determine whether the user can process/change status of the maintenance.
     * Hanya Admin yang bisa memproses (menyetujui, menolak, mengerjakan, menyelesaikan).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pemeliharaan  $pemeliharaan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function process(User $user, Pemeliharaan $pemeliharaan)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }


    /**
     * Determine whether the user can delete the model (soft delete).
     * Hanya Admin yang bisa menghapus (mengarsipkan).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pemeliharaan  $pemeliharaan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Pemeliharaan $pemeliharaan)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can restore the model.
     * Hanya Admin yang bisa memulihkan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pemeliharaan  $pemeliharaan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Pemeliharaan $pemeliharaan)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Hanya Admin yang bisa hapus permanen (jika diimplementasikan).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pemeliharaan  $pemeliharaan
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Pemeliharaan $pemeliharaan)
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }
}
