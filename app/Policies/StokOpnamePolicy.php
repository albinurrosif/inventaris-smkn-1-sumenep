<?php

namespace App\Policies;

use App\Models\StokOpname;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StokOpnamePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Admin bisa lihat semua. Operator bisa lihat SO yang dibuatnya atau untuk ruangannya.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    /**
     * Determine whether the user can view the model.
     * Admin bisa lihat semua. Operator bisa lihat SO yang dibuatnya atau untuk ruangannya.
     */
    public function view(User $user, StokOpname $stokOpname): bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            return $stokOpname->id_operator === $user->id ||
                $user->ruanganYangDiKelola()->where('id', $stokOpname->id_ruangan)->exists();
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Admin dan Operator bisa membuat sesi Stok Opname.
     */
    public function create(User $user): bool
    {
        // Kebijakan sebelumnya mengizinkan Operator, sekarang hanya Admin.
        // Karena sudah di-handle di before(), method ini bahkan bisa tidak ada atau return false.
        // Namun, untuk kejelasan, kita eksplisit mengizinkan Admin.
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can update the model.
     * Admin bisa update. Operator bisa update jika status masih 'Draft' dan dia yang buat.
     */
    public function update(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) && $stokOpname->status === StokOpname::STATUS_DRAFT;
    }



    /**
     * Determine whether the user can process (input details) of the stok opname.
     * Admin bisa proses semua. Operator bisa proses SO yang dibuatnya atau untuk ruangannya jika status 'Draft'.
     */
    public function processDetails(User $user, StokOpname $stokOpname): bool
    {
        

        if ($stokOpname->status !== StokOpname::STATUS_DRAFT) {
            return false; // Tidak bisa proses jika sudah Selesai atau Dibatalkan
        }
        if ($user->hasRole(User::ROLE_ADMIN)) {

            return true;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            return $stokOpname->id_operator === $user->id ||
                $user->ruanganYangDiKelola()->where('id', $stokOpname->id_ruangan)->exists();
        }
        return false;
    }

    /**
     * Determine whether the user can finalize the stok opname.
     * Hanya Admin yang bisa memfinalisasi.
     */
    public function finalize(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) && $stokOpname->status === StokOpname::STATUS_DRAFT;
    }

    /**
     * Determine whether the user can cancel the stok opname.
     * Admin bisa batal semua. Operator bisa batal SO yang dibuatnya jika status 'Draft'.
     */
    public function cancel(User $user, StokOpname $stokOpname): bool
    {
        if ($stokOpname->status !== StokOpname::STATUS_DRAFT) {
            return false; // Tidak bisa batal jika sudah Selesai
        }
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            return $stokOpname->id_operator === $user->id;
        }
        return false;
    }


    /**
     * Determine whether the user can delete the model (soft delete).
     * Hanya Admin yang bisa hapus (arsip) SO, mungkin jika statusnya Draft atau Dibatalkan.
     */
    public function delete(User $user, StokOpname $stokOpname): bool
    {
        // Hanya bisa hapus jika draft atau dibatalkan, untuk mencegah kehilangan histori SO Selesai
        return $user->hasRole(User::ROLE_ADMIN) &&
            in_array($stokOpname->status, [StokOpname::STATUS_DRAFT, StokOpname::STATUS_DIBATALKAN]);
    }

    /**
     * Determine whether the user can restore the model.
     * Hanya Admin.
     */
    public function restore(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Hanya Admin.
     */
    public function forceDelete(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }
}
