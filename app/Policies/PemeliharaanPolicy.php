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
        return null;
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

    public function create(User $user): bool
    {
        // Sesuai aturan: Admin, Operator, dan Guru bisa membuat laporan.
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_GURU]);
    }

    // Ubah nama method ini dari processApproval agar lebih jelas
    public function approveOrReject(User $user, Pemeliharaan $pemeliharaan): bool
    {
        // Syarat: Status harus 'Diajukan'
        if ($pemeliharaan->status !== Pemeliharaan::STATUS_DIAJUKAN) {
            return false;
        }
        // Syarat: HANYA Admin yang bisa
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function startWork(User $user, Pemeliharaan $pemeliharaan): bool
    {
        // Syarat: Status harus 'Disetujui'
        if ($pemeliharaan->status !== Pemeliharaan::STATUS_DISETUJUI) {
            return false;
        }
        // Syarat: User adalah Admin atau Operator yang ditugaskan (PIC)
        return $user->hasRole(User::ROLE_ADMIN) || $user->id === $pemeliharaan->id_operator_pengerjaan;
    }

    public function completeWork(User $user, Pemeliharaan $pemeliharaan): bool
    {
        // Syarat: Status harus 'Dalam Perbaikan'
        if ($pemeliharaan->status !== Pemeliharaan::STATUS_DALAM_PERBAIKAN) {
            return false;
        }
        // Syarat: User adalah Admin atau Operator yang ditugaskan (PIC)
        return $user->hasRole(User::ROLE_ADMIN) || $user->id === $pemeliharaan->id_operator_pengerjaan;
    }

    public function confirmHandover(User $user, Pemeliharaan $pemeliharaan): bool
    {
        // Syarat: Status harus 'Selesai'
        if ($pemeliharaan->status !== Pemeliharaan::STATUS_SELESAI) {
            return false;
        }

        // Syarat: User adalah Admin atau Operator yang ditugaskan (PIC)
        return $user->hasRole(User::ROLE_ADMIN) || $user->id === $pemeliharaan->id_operator_pengerjaan;
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

        // Daftar status final yang mengunci record dari pengeditan.
        $finalStatuses = [
            \App\Models\Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
            \App\Models\Pemeliharaan::STATUS_PENGERJAAN_GAGAL,
            \App\Models\Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI,
            \App\Models\Pemeliharaan::STATUS_PENGAJUAN_DITOLAK,
            \App\Models\Pemeliharaan::STATUS_PENGAJUAN_DIBATALKAN, // Jika ada
        ];

        // Jika status saat ini ada di dalam daftar final, langsung tolak izin update.
        if (in_array($pemeliharaan->status_pengerjaan, $finalStatuses) || in_array($pemeliharaan->status_pengajuan, $finalStatuses)) {
            return false;
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
