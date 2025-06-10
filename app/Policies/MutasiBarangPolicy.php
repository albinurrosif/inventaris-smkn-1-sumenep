<?php

namespace App\Policies;

use App\Models\MutasiBarang;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MutasiBarangPolicy
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
     * Izinkan Operator melihat daftar riwayat mutasi.
     * Data akan difilter di controller.
     */
    public function viewAny(User $user)
    {
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * Izinkan Operator melihat detail mutasi jika terkait ruangannya.
     */
    public function view(User $user, MutasiBarang $mutasiBarang)
    {
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganIds = $user->ruanganYangDiKelola()->pluck('id');
            return $ruanganIds->contains($mutasiBarang->id_ruangan_asal) || $ruanganIds->contains($mutasiBarang->id_ruangan_tujuan);
        }
        return false;
    }

    /**
     * Mutasi dibuat dari halaman lain, bukan dari halaman create khusus.
     */
    public function create(User $user)
    {
        return false;
    }
}
