<?php

namespace App\Policies;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LogAktivitasPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LogAktivitas $logAktivitas): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    // Metode create, update, delete, restore, forceDelete untuk LogAktivitas
    // biasanya mengembalikan false karena log tidak seharusnya dimodifikasi atau dihapus.
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LogAktivitas $logAktivitas): bool
    {
        return false;
    }

    public function delete(User $user, LogAktivitas $logAktivitas): bool
    {
        return false; // Atau $user->hasRole(User::ROLE_SUPER_ADMIN) jika ada kebutuhan khusus
    }

    public function restore(User $user, LogAktivitas $logAktivitas): bool
    {
        return false;
    }

    public function forceDelete(User $user, LogAktivitas $logAktivitas): bool
    {
        return false; // Atau $user->hasRole(User::ROLE_SUPER_ADMIN)
    }
}
