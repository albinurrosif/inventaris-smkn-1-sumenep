<?php

namespace App\Policies;

use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RuanganPolicy
{
    use HandlesAuthorization;

    /**
     * Memberikan hak akses super-user kepada Admin.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        return null;
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar ruangan.
     * Operator juga bisa.
     */
    public function viewAny(User $user): bool
    {
        // Admin sudah ditangani oleh before()
        return $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail ruangan tertentu.
     * Operator hanya bisa melihat detail ruangan yang dia kelola.
     */
    public function view(User $user, Ruangan $ruangan): bool
    {
        // Admin sudah ditangani oleh before()
        if ($user->hasRole(User::ROLE_OPERATOR)) { //
            return $user->ruanganYangDiKelola()->where('ruangans.id', $ruangan->id)->exists(); //
        }
        return false; //
    }

    /**
     * Menentukan apakah pengguna dapat membuat ruangan baru.
     * Hanya Admin (sudah ditangani before(), Operator tidak akan sampai sini).
     */
    public function create(User $user): bool
    {
        // Admin sudah ditangani oleh before()
        return false; // Operator tidak bisa
    }

    /**
     * Menentukan apakah pengguna dapat mengupdate ruangan tertentu.
     * Operator hanya bisa mengupdate ruangan yang dia kelola.
     */
    public function update(User $user, Ruangan $ruangan): bool
    {
        // Admin sudah ditangani oleh before()
        if ($user->hasRole(User::ROLE_OPERATOR)) { //
            return $user->ruanganYangDiKelola()->where('ruangans.id', $ruangan->id)->exists(); //
        }
        return false; //
    }

    /**
     * Menentukan apakah pengguna dapat menghapus ruangan tertentu.
     * Hanya Admin (sudah ditangani before(), Operator tidak akan sampai sini).
     */
    public function delete(User $user, Ruangan $ruangan): bool
    {
        // Admin sudah ditangani oleh before()
        // if ($ruangan->barangQrCodes()->exists()) {
        // return false;
        // }
        return false; // Operator tidak bisa
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan ruangan yang di-soft-delete.
     * Hanya Admin (sudah ditangani before(), Operator tidak akan sampai sini).
     */
    public function restore(User $user, Ruangan $ruangan): bool
    {
        // Admin sudah ditangani oleh before()
        return false; // Operator tidak bisa
    }

    /**
     * Menentukan apakah pengguna dapat menghapus ruangan secara permanen.
     * Hanya Admin.
     */
    // public function forceDelete(User $user, Ruangan $ruangan): bool
    // {
    // // Admin sudah ditangani oleh before()
    // return false; // Operator tidak bisa
    // }
}
