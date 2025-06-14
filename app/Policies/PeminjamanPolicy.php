<?php

namespace App\Policies;

use App\Models\Peminjaman;
use App\Models\User;
use App\Models\DetailPeminjaman;
use Illuminate\Auth\Access\HandlesAuthorization;

class PeminjamanPolicy
{
    use HandlesAuthorization;

    /**
     * Memberikan hak akses super-user kepada Admin untuk semua aksi.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Pengecualian: jangan berikan akses 'update' otomatis untuk Admin
        if ($ability === 'update') {
            return null; // Biarkan metode update() yang memutuskan
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        return null; // Biarkan metode policy lain yang memutuskan
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar peminjaman.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_OPERATOR, User::ROLE_GURU]);
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail peminjaman.
     */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true dari before()
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // PENYESUAIAN: Tambahkan pengecekan apakah operator ini yang memproses
            if ($peminjaman->disetujui_oleh === $user->id || $peminjaman->ditolak_oleh === $user->id) {
                return true;
            }

            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            if ($peminjaman->id_ruangan_tujuan_peminjaman && $ruanganOperatorIds->contains($peminjaman->id_ruangan_tujuan_peminjaman)) {
                return true;
            }

            return $peminjaman->detailPeminjaman()
                ->whereHas('barangQrCode', function ($query) use ($ruanganOperatorIds) {
                    $query->whereIn('id_ruangan', $ruanganOperatorIds);
                })->exists();
        }

        if ($user->hasRole(User::ROLE_GURU)) {
            return $peminjaman->id_guru === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengajuan peminjaman.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_GURU);
    }

    /**
     * Menentukan apakah pengguna dapat mengupdate peminjaman.
     */
    public function update(User $user, Peminjaman $peminjaman): bool
    {
        // Admin selalu bisa (via `before()` method).

        // Hak akses update HANYA diberikan kepada Guru pemilik pengajuan,
        // dan hanya jika statusnya masih 'Menunggu Persetujuan'.
        if ($user->hasRole(User::ROLE_GURU)) {
            return $peminjaman->id_guru === $user->id &&
                $peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN;
        }

        // Operator dan peran lain tidak memiliki hak 'update' umum.
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus (soft delete/archive) peminjaman.
     */
    public function delete(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before().
        // Hanya peminjaman yang sudah final (Selesai, Ditolak, Dibatalkan) yang boleh diarsipkan.
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan peminjaman yang diarsipkan.
     */
    public function restore(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before(). Operator/Guru tidak bisa.
        return false;
    }

    /**
     * Menentukan apakah pengguna (Admin/Operator) dapat me-manage (approve/reject) peminjaman.
     */
    public function manage(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before()
        if (!in_array($peminjaman->status, [Peminjaman::STATUS_MENUNGGU_PERSETUJUAN])) {
            return false;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            return $this->isOperatorRelatedToPeminjaman($user, $peminjaman);
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat memfinalisasi proses persetujuan.
     */
    public function finalize(User $user, Peminjaman $peminjaman): bool
    {
        // Admin selalu bisa via before()
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Operator bisa finalisasi jika status masih menunggu dan dia terkait dengan peminjaman
            return $peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN &&
                $this->isOperatorRelatedToPeminjaman($user, $peminjaman);
        }
        return false;
    }

    // --- METHOD DI BAWAH INI DIHAPUS ---
    // public function processHandover(User $user, Peminjaman $peminjaman): bool
    // { ... }
    // public function processReturn(User $user, Peminjaman $peminjaman): bool
    // { ... }
    // --- AKHIR METHOD YANG DIHAPUS ---

    /**
     * Menentukan apakah pengguna (Guru atau Admin) dapat membatalkan pengajuannya.
     */
    public function cancelByUser(User $user, Peminjaman $peminjaman): bool
    {
        // Admin selalu bisa (via `before()` method)

        // Cek apakah pengguna adalah Guru yang membuat peminjaman
        if ($user->id === $peminjaman->id_guru) {

            // 1. Izinkan pembatalan jika status masih menunggu persetujuan.
            if ($peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN) {
                return true;
            }

            // 2. PENYESUAIAN: Izinkan juga pembatalan jika status sudah disetujui,
            //    TETAPI belum ada satupun barang yang statusnya 'Diambil'.
            if ($peminjaman->status === Peminjaman::STATUS_DISETUJUI) {
                return !$peminjaman->detailPeminjaman()
                    ->where('status_unit', DetailPeminjaman::STATUS_ITEM_DIAMBIL)
                    ->exists();
            }
        }

        return false;
    }

    /**
     * Helper untuk cek apakah operator terkait dengan item-item dalam peminjaman.
     */
    private function isOperatorRelatedToPeminjaman(User $operator, Peminjaman $peminjaman): bool
    {
        $ruanganOperatorIds = $operator->ruanganYangDiKelola()->pluck('id');

        // Jika peminjaman ditujukan ke ruangan yang dikelola operator
        if ($peminjaman->id_ruangan_tujuan_peminjaman && $ruanganOperatorIds->contains($peminjaman->id_ruangan_tujuan_peminjaman)) {
            return true;
        }

        // Jika ada detail item, cek apakah salah satu item berada di ruangan yang dikelola operator
        if ($peminjaman->detailPeminjaman()->count() > 0) {
            return $peminjaman->detailPeminjaman()
                ->whereHas('barangQrCode', function ($query) use ($ruanganOperatorIds) {
                    $query->whereIn('id_ruangan', $ruanganOperatorIds);
                })->exists();
        }

        return false;
    }
}
