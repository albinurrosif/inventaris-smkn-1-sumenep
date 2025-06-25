<?php

namespace App\Policies;

use App\Models\BarangQrCode;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarangQrCodePolicy
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
     * Menentukan apakah pengguna dapat melihat daftar unit barang.
     */
    public function viewAny(User $user): bool
    {
        // Izinkan jika perannya Operator ATAU Guru
        return $user->hasAnyRole([User::ROLE_OPERATOR, User::ROLE_GURU]);
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail unit barang tertentu.
     */
    public function view(User $user, BarangQrCode $barangQrCode): bool
    {
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Kondisi 1: Unit dipegang personal oleh Operator ybs
            if ($barangQrCode->id_pemegang_personal === $user->id) {
                return true;
            }
            // Kondisi 2: Unit berada di ruangan yang dikelola oleh Operator
            if ($barangQrCode->id_ruangan) {
                return $user->ruanganYangDiKelola()->where('ruangans.id', $barangQrCode->id_ruangan)->exists();
            }
            // Kondisi 3: Unit sedang "mengambang" (tidak punya lokasi)
            if (is_null($barangQrCode->id_ruangan) && is_null($barangQrCode->id_pemegang_personal)) {
                return true; // Semua operator bisa melihat barang yang butuh penempatan
            }
        }
        // Guru bisa melihat jika dia pemegang personal
        if ($user->hasRole(User::ROLE_GURU)) {
            return $barangQrCode->id_pemegang_personal === $user->id;
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat membuat unit barang baru.
     */
    public function create(User $user): bool
    {
        // Hanya Operator (Admin sudah di handle before)
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * Menentukan apakah pengguna dapat mengupdate unit barang.
     * HANYA ADMIN YANG BOLEH.
     */
    public function update(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before().
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat melakukan mutasi/penempatan.
     * HANYA ADMIN yang diizinkan.
     */
    public function mutasi(User $user, BarangQrCode $barangQrCode): bool
    {
        // PERUBAHAN: Policy ini sekarang hanya mengizinkan Admin.
        // Cek peran Admin sudah ditangani oleh metode `before()`.
        // Untuk peran lain (termasuk Operator), kita kembalikan false.
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menyerahkan unit ke pemegang personal.
     * HANYA ADMIN yang diizinkan.
     */
    public function assignPersonal(User $user, BarangQrCode $barangQrCode): bool
    {
        // PERUBAHAN: Policy ini sekarang hanya mengizinkan Admin.
        // Cek peran Admin sudah ditangani oleh metode `before()`.
        // Untuk peran lain (termasuk Operator), kita kembalikan false.
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat mengembalikan unit dari pemegang personal.
     * HANYA ADMIN yang dapat melakukannya.
     */
    public function returnPersonal(User $user, BarangQrCode $barangQrCode): bool
    {
        // PERBAIKAN: Aksi ini sekarang hanya diizinkan untuk Admin.
        // Cek peran Admin sudah ditangani oleh metode `before()`.
        // Untuk peran lain (termasuk Operator), kita kembalikan false.
        return false; //
    }

    /**
     * Menentukan apakah pengguna dapat mentransfer unit antar pemegang personal.
     * HANYA ADMIN yang dapat melakukannya.
     */
    public function transferPersonal(User $user, BarangQrCode $barangQrCode): bool
    {
        // PERBAIKAN: Aksi ini sekarang hanya diizinkan untuk Admin.
        // Cek peran Admin sudah ditangani oleh metode `before()`.
        // Untuk peran lain (termasuk Operator), kita kembalikan false.
        return false; //
    }

    /**
     * Menentukan apakah pengguna dapat mengarsipkan unit.
     * Hanya Admin yang boleh mengarsipkan.
     */
    public function archive(User $user, BarangQrCode $barangQrCode): bool
    {
        // PERUBAHAN: Hanya kembalikan true jika user adalah Admin.
        //
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan unit barang. HANYA ADMIN.
     */
    public function restore(User $user, BarangQrCode $barangQrCode): bool
    {
        return false; // Admin sudah true dari before() 
    }

    /**
     * Menentukan apakah pengguna dapat mengunduh QR Code.
     * Hak akses sama dengan hak lihat.
     */
    public function downloadQr(User $user, BarangQrCode $barangQrCode): bool
    {
        return $this->view($user, $barangQrCode);
    }

    /**
     * Menentukan apakah pengguna dapat mencetak QR Code.
     */
    public function printQr(User $user): bool
    {
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor daftar unit.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(User::ROLE_OPERATOR);
    }
}
