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
     *
     * @param \App\Models\User $user
     * @param string $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        return null; // Biarkan metode policy lain yang memutuskan
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar peminjaman.
     * Admin sudah true dari before(). Operator dan Guru bisa melihat (data difilter di controller).
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_OPERATOR, User::ROLE_GURU]);
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail peminjaman.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true dari before()
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Operator bisa lihat jika ada item dalam peminjaman yang terkait ruangannya
            // atau jika peminjaman ditujukan ke ruangannya (untuk pengajuan baru yang belum ada itemnya)
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            if ($peminjaman->id_ruangan_tujuan_peminjaman && $ruanganOperatorIds->contains($peminjaman->id_ruangan_tujuan_peminjaman)) {
                return true;
            }
            // Jika tidak ada detail item, dan bukan ruangan tujuan operator, maka operator tidak bisa lihat.
            if ($peminjaman->detailPeminjaman()->count() === 0 && !($peminjaman->id_ruangan_tujuan_peminjaman && $ruanganOperatorIds->contains($peminjaman->id_ruangan_tujuan_peminjaman))) {
                return false;
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
     * Hanya Guru yang bisa. Admin bisa via fitur lain jika diperlukan.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_GURU);
    }

    /**
     * Menentukan apakah pengguna dapat mengupdate peminjaman.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    public function update(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true dari before()
        if ($user->hasRole(User::ROLE_GURU)) {
            return $peminjaman->id_guru === $user->id &&
                $peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Operator diizinkan update jika peminjaman masih menunggu atau sudah disetujui (misalnya untuk menambah catatan)
            // dan Operator terkait dengan peminjaman tersebut.
            if (in_array($peminjaman->status, [Peminjaman::STATUS_MENUNGGU_PERSETUJUAN, Peminjaman::STATUS_DISETUJUI])) {
                return $this->isOperatorRelatedToPeminjaman($user, $peminjaman);
            }
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus (soft delete/archive) peminjaman.
     * Admin bisa (via before).
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    public function delete(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before().
        // Hanya peminjaman yang sudah final (Selesai, Ditolak, Dibatalkan) yang boleh diarsipkan.
        return in_array($peminjaman->status, [
            Peminjaman::STATUS_SELESAI,
            Peminjaman::STATUS_DITOLAK,
            Peminjaman::STATUS_DIBATALKAN
        ]);
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan peminjaman yang diarsipkan.
     * Admin bisa (via before).
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    public function restore(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before(). Operator/Guru tidak bisa.
        return false;
    }

    /**
     * Menentukan apakah pengguna (Admin/Operator) dapat me-manage (approve/reject) peminjaman.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
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
     * Menentukan apakah pengguna (Admin/Operator) dapat memproses penyerahan (handover) item.
     * Dipanggil dengan Peminjaman induk, validasi spesifik item ada di DetailPeminjamanPolicy.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    public function processHandover(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before()
        if ($peminjaman->status !== Peminjaman::STATUS_DISETUJUI) {
            return false;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Operator harus terkait dengan setidaknya satu item dalam peminjaman ini
            return $this->isOperatorRelatedToPeminjaman($user, $peminjaman);
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna (Admin/Operator) dapat memproses pengembalian (return) item.
     * Dipanggil dengan Peminjaman induk, validasi spesifik item ada di DetailPeminjamanPolicy.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    public function processReturn(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before()
        if (!in_array($peminjaman->status, [Peminjaman::STATUS_SEDANG_DIPINJAM, Peminjaman::STATUS_TERLAMBAT])) {
            return false;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            return $this->isOperatorRelatedToPeminjaman($user, $peminjaman);
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna (Guru atau Admin) dapat membatalkan pengajuannya.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    public function cancelByUser(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah true via before()
        if ($user->id === $peminjaman->id_guru) {
            // Guru bisa batal jika masih Menunggu Persetujuan ATAU Disetujui TAPI belum ada barang yang diambil
            if ($peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN) {
                return true;
            }
            if ($peminjaman->status === Peminjaman::STATUS_DISETUJUI) {
                // Cek apakah ada item yang sudah diambil
                return !$peminjaman->detailPeminjaman()->where('status_unit', DetailPeminjaman::STATUS_ITEM_DIAMBIL)->exists();
            }
        }
        return false;
    }

    /**
     * Helper untuk cek apakah operator terkait dengan item-item dalam peminjaman.
     * Operator dianggap terkait jika setidaknya satu item barang dalam peminjaman
     * berada di ruangan yang dikelolanya, ATAU jika peminjaman diajukan
     * ke salah satu ruangannya (untuk pengajuan baru yang mungkin belum ada itemnya saat dicek).
     *
     * @param \App\Models\User $operator
     * @param \App\Models\Peminjaman $peminjaman
     * @return bool
     */
    private function isOperatorRelatedToPeminjaman(User $operator, Peminjaman $peminjaman): bool
    {
        $ruanganOperatorIds = $operator->ruanganYangDiKelola()->pluck('id');

        // Jika peminjaman ditujukan ke ruangan yang dikelola operator (penting untuk pengajuan baru)
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

        // Jika tidak ada detail item dan ruangan tujuan juga bukan milik operator, maka tidak terkait
        return false;
    }
}
