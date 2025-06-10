<?php

namespace App\Policies;

use App\Models\Pemeliharaan;
use App\Models\User;
use App\Models\BarangQrCode;
use Illuminate\Auth\Access\HandlesAuthorization;

class PemeliharaanPolicy
{
    use HandlesAuthorization;

    /**
     * TAMBAHAN: Method 'before' untuk memberikan hak akses penuh kepada Admin.
     * Ini menyederhanakan semua method lainnya.
     */
    public function before(User $user, string $ability)
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
    }

    /**
     * PERUBAHAN: Izinkan Guru untuk melihat daftar pemeliharaan.
     * Controller akan memfilter agar Guru hanya melihat laporannya sendiri.
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyRole([User::ROLE_OPERATOR, User::ROLE_GURU]);
    }

    /**
     * PERUBAHAN: Tambahkan logika untuk Guru.
     * Admin sudah di-handle 'before()'.
     * Operator bisa lihat jika terkait ruangannya, pelapor, atau PIC.
     * Guru hanya bisa lihat laporan yang ia buat sendiri.
     */
    public function view(User $user, Pemeliharaan $pemeliharaan)
    {
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Operator adalah pelapor ATAU PIC pengerjaan
            if ($pemeliharaan->id_user_pengaju === $user->id || $pemeliharaan->id_operator_pengerjaan === $user->id) {
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

        if ($user->hasRole(User::ROLE_GURU)) {
            return $pemeliharaan->id_user_pengaju === $user->id;
        }

        return false;
    }

    /**
     * PERUBAHAN: Izinkan Guru untuk bisa membuat laporan kerusakan.
     */
    public function create(User $user)
    {
        return $user->hasAnyRole([User::ROLE_OPERATOR, User::ROLE_GURU]);
    }

    /**
     * PERUBAHAN: Logika update yang lebih detail.
     * Admin bisa selalu update (dari before).
     * Guru/Operator bisa update jika dia adalah pelapor DAN status masih 'Diajukan'.
     * Operator yang ditugaskan sebagai PIC juga bisa update jika laporan sudah disetujui.
     */
    public function update(User $user, Pemeliharaan $pemeliharaan)
    {
        // 1. Jika user adalah pelapor dan status masih Diajukan, dia boleh edit.
        if ($pemeliharaan->id_user_pengaju === $user->id && $pemeliharaan->status_pengajuan === Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN) {
            return true;
        }

        // 2. Jika user adalah Operator yang ditugaskan (PIC) dan status sudah Disetujui, dia boleh update progres.
        if ($user->hasRole(User::ROLE_OPERATOR) && $pemeliharaan->id_operator_pengerjaan === $user->id && $pemeliharaan->status_pengajuan === Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI) {
            return true;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna (Admin) dapat memproses persetujuan/penolakan.
     * (Nama method `process` dipertahankan sesuai kode Anda, 'manage' adalah alternatif yang baik).
     */
    public function process(User $user, Pemeliharaan $pemeliharaan)
    {
        // Hanya Admin yang bisa (sudah di-handle oleh before()).
        // Mengembalikan false untuk semua role lain.
        return false;
    }

    /**
     * Menentukan apakah pengguna (Admin) dapat menghapus (mengarsipkan) laporan.
     */
    public function delete(User $user, Pemeliharaan $pemeliharaan)
    {
        // Hanya Admin
        return false;
    }

    /**
     * Menentukan apakah pengguna (Admin) dapat memulihkan laporan.
     */
    public function restore(User $user, Pemeliharaan $pemeliharaan)
    {
        // Hanya Admin
        return false;
    }

    /**
     * Menentukan apakah pengguna (Admin) dapat menghapus permanen.
     */
    public function forceDelete(User $user, Pemeliharaan $pemeliharaan)
    {
        // Hanya Admin
        return false;
    }
}
