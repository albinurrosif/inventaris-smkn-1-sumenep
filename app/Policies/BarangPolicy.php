<?php

namespace App\Policies;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarangPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Admin dan Operator bisa melihat daftar jenis barang.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) || $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Determine whether the user can view the model.
     * Admin bisa melihat semua detail jenis barang.
     * Operator hanya bisa melihat detail jenis barang jika ada unit yang dikelolanya terkait dengan jenis barang tersebut.
     */
    public function view(User $user, Barang $barang): bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) { //
            return true;
        }

        if ($user->hasRole(User::ROLE_OPERATOR)) { //
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id'); //
            return $barang->qrCodes()->whereIn('id_ruangan', $ruanganOperatorIds)->exists(); //
        }

        // --- TAMBAHKAN LOGIKA INI UNTUK GURU ---
        if ($user->hasRole(User::ROLE_GURU)) {
            // Izinkan Guru melihat detail barang induk jika salah satu unitnya
            // dipegang personal oleh Guru tersebut
            return $barang->qrCodes()->where('id_pemegang_personal', $user->id)->exists();
        }
        // --- AKHIR TAMBAHAN ---

        return false; //
    }

    /**
     * Determine whether the user can create models.
     * Admin dan Operator bisa membuat jenis barang baru.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) || $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Determine whether the user can update the model.
     * Hanya Admin yang bisa update data master jenis barang.
     */
    public function update(User $user, Barang $barang): bool
    {
        // Aturan yang lebih ketat: Hanya Admin yang boleh update data master Barang.
        // Jika Operator diizinkan dengan kondisi tertentu (misal, jika Barang belum punya unit QR),
        // logika bisa ditambahkan di sini.
        return $user->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can delete the model.
     * Hanya Admin yang boleh menghapus jenis barang (beserta semua unitnya).
     */
    public function delete(User $user, Barang $barang): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); //
    }

    /**
     * Determine whether the user can restore the model.
     * Hanya Admin.
     */
    public function restore(User $user, Barang $barang): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); //
    }

    /**
     * Determine whether the user can permanently delete the model.
     * (Jika Anda mengimplementasikan soft delete dan ingin ada force delete)
     * Hanya Admin.
     */
    // public function forceDelete(User $user, Barang $barang): bool
    // {
    //     return $user->hasRole(User::ROLE_ADMIN);
    // }

    /**
     * Menentukan apakah pengguna dapat menjalankan wizard input nomor seri.
     * Admin bisa. Operator bisa jika dia memiliki hak create dan barang ini terkait dengan sesi pembuatan yang valid.
     * Logika ini lebih banyak ditangani oleh alur controller (session check).
     * Policy ini memastikan pengguna setidaknya memiliki hak dasar untuk membuat/mengelola barang.
     */
    public function inputSerial(User $user, Barang $barang): bool
    {
        // Jika user punya hak create, dia boleh melanjutkan ke input serial untuk barang baru.
        // Controller akan melakukan validasi lebih lanjut (misal, cek session `incomplete_barang_id`)
        return $this->create($user);
    }

    /**
     * Menentukan apakah pengguna dapat mengimpor data barang.
     * Hanya Admin.
     */
    public function import(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN); //
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data barang.
     * Admin dan Operator.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) || $user->hasRole(User::ROLE_OPERATOR); //
    }
}
