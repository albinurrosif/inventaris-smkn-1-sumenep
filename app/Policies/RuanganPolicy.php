<?php

namespace App\Policies;

use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RuanganPolicy
{
    use HandlesAuthorization;

    /**
     * Berikan akses penuh untuk Admin.
     */
    public function before(User $user, string $ability)
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
    }

    /**
     * Izinkan Operator melihat halaman daftar ruangan.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * Izinkan Operator melihat detail ruangan JIKA dia adalah penanggung jawabnya.
     */
    public function view(User $user, Ruangan $ruangan): bool
    {
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            return $ruangan->id_operator === $user->id;
        }
        return false;
    }

    /**
     * Jangan izinkan non-admin untuk membuat, mengedit, atau menghapus.
     */
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, Ruangan $ruangan): bool
    {
        return false;
    }
    public function delete(User $user, Ruangan $ruangan): bool
    {
        return false;
    }
    public function restore(User $user, Ruangan $ruangan): bool
    {
        return false;
    }
    public function forceDelete(User $user, Ruangan $ruangan): bool
    {
        return false;
    }
}
