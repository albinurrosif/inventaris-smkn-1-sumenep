<?php

namespace App\Policies;

use App\Models\User;

class PengaturanPolicy
{
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

    // Biarkan semua method di bawah ini return false karena hanya Admin yang boleh
    public function viewAny(User $user)
    {
        return false;
    }
    public function update(User $user)
    {
        return false;
    }
}
