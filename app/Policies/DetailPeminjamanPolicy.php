<?php

namespace App\Policies;

use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DetailPeminjamanPolicy
{
    use HandlesAuthorization;

    /**
     * Memberikan hak akses super-user kepada Admin untuk semua aksi.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        return null;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail peminjaman spesifik.
     * (Logika Anda sudah bagus, tidak ada perubahan)
     */
    public function view(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        // Guru bisa lihat jika itu adalah bagian dari peminjamannya.
        if ($user->hasRole(User::ROLE_GURU)) {
            return $detailPeminjaman->peminjaman->id_guru === $user->id;
        }

        // Operator bisa lihat jika dia terkait dengan ruangan barang pada detail ini.
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            return $this->isOperatorRelatedToItem($user, $detailPeminjaman);
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus item dari peminjaman.
     * (Logika Anda sudah bagus, tidak ada perubahan)
     */
    public function delete(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        // Hanya Guru pemilik peminjaman yang bisa menghapus item,
        // dan hanya jika status peminjaman masih 'Menunggu Persetujuan'.
        if ($user->hasRole(User::ROLE_GURU)) {
            return $detailPeminjaman->peminjaman->id_guru === $user->id &&
                $detailPeminjaman->peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN;
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna (Operator) dapat memproses penyerahan (handover) item ini.
     * (PENYESUAIAN LOGIKA)
     */
    public function processHandover(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        // 1. Aksi ini hanya untuk Operator
        if (!$user->hasRole(User::ROLE_OPERATOR)) {
            return false;
        }

        // 2. Aksi hanya bisa dilakukan jika status item adalah 'Disetujui'.
        if ($detailPeminjaman->status_unit !== DetailPeminjaman::STATUS_ITEM_DISETUJUI) {
            return false;
        }

        // 3. PENYESUAIAN: Peminjaman induk harus dalam status aktif.
        // Bisa jadi statusnya sudah 'Sedang Dipinjam' jika item lain sudah diambil lebih dulu.
        if (!in_array($detailPeminjaman->peminjaman->status, [
            Peminjaman::STATUS_DISETUJUI,
            Peminjaman::STATUS_SEDANG_DIPINJAM
        ])) {
            return false;
        }

        // 4. Operator harus terkait dengan item tersebut.
        return $this->isOperatorRelatedToItem($user, $detailPeminjaman);
    }

    /**
     * Menentukan apakah pengguna (Operator) dapat memproses pengembalian (return) item ini.
     * (PENYESUAIAN LOGIKA)
     */
    public function processReturn(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        // 1. Aksi ini hanya untuk Operator
        if (!$user->hasRole(User::ROLE_OPERATOR)) {
            return false;
        }

        // 2. Item harus dalam status 'Diambil'.
        // Status 'Rusak Saat Dipinjam' juga merupakan sub-status dari 'Diambil'.
        if (!in_array($detailPeminjaman->status_unit, [
            DetailPeminjaman::STATUS_ITEM_DIAMBIL,
            DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM
        ])) {
            return false;
        }

        // 3. Peminjaman induk harus dalam status aktif ('Sedang Dipinjam' atau 'Terlambat').
        if (!in_array($detailPeminjaman->peminjaman->status, [
            Peminjaman::STATUS_SEDANG_DIPINJAM,
            Peminjaman::STATUS_TERLAMBAT
        ])) {
            return false;
        }

        // 4. Operator harus terkait dengan item tersebut.
        return $this->isOperatorRelatedToItem($user, $detailPeminjaman);
    }

    /**
     * Helper untuk memeriksa apakah Operator terkait dengan item.
     * (PENAMBAHAN HELPER METHOD)
     */
    private function isOperatorRelatedToItem(User $operator, DetailPeminjaman $detailPeminjaman): bool
    {
        $barangQr = $detailPeminjaman->barangQrCode;

        // Jika barang tidak ditemukan, maka tidak ada keterkaitan.
        if (!$barangQr) {
            return false;
        }

        // Jika barang berasal dari ruangan, cek apakah operator mengelola ruangan tersebut.
        if ($barangQr->id_ruangan) {
            return $operator->ruanganYangDiKelola()->where('id', $barangQr->id_ruangan)->exists();
        }

        // Jika barang dipegang personal, cek apakah operator adalah pemegang personalnya.
        // (Skenario ini jarang untuk peminjaman, tapi baik untuk dicakup)
        if ($barangQr->id_pemegang_personal) {
            return $operator->id === $barangQr->id_pemegang_personal;
        }

        // Jika barang tidak berlokasi (mengambang), anggap tidak ada operator yang bertanggung jawab.
        return false;
    }
}
