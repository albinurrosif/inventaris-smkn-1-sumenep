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
     * Memberikan hak akses super-user kepada Admin untuk semua aksi terkait DetailPeminjaman.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }
        return null;
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar detail peminjaman (jarang digunakan secara langsung).
     * Biasanya detail dilihat dalam konteks Peminjaman induk.
     */
    public function viewAny(User $user): bool
    {
        // Admin sudah true. Mungkin Operator untuk debugging atau laporan tertentu.
        return $user->hasRole(User::ROLE_OPERATOR);
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail peminjaman spesifik.
     */
    public function view(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        // Admin sudah true.
        // Operator bisa lihat jika dia terkait dengan ruangan barang pada detail ini.
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $barangQr = $detailPeminjaman->barangQrCode;
            if ($barangQr && $barangQr->id_ruangan) {
                return $user->ruanganYangDiKelola()->where('id', $barangQr->id_ruangan)->exists();
            }
            // Jika barang dipegang personal oleh operator saat pengajuan (kasus jarang untuk detail peminjaman)
            if ($barangQr && $barangQr->id_pemegang_personal === $user->id) {
                return true;
            }
        }
        // Guru bisa lihat jika itu adalah bagian dari peminjamannya.
        if ($user->hasRole(User::ROLE_GURU)) {
            return $detailPeminjaman->peminjaman->id_guru === $user->id;
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menambahkan item ke peminjaman.
     * Dilakukan saat Peminjaman dibuat atau diedit (jika status memungkinkan).
     * Otorisasi utama ada di PeminjamanPolicy::create atau PeminjamanPolicy::update.
     * Policy ini bisa digunakan untuk validasi tambahan jika diperlukan.
     */
    public function create(User $user, Peminjaman $peminjaman): bool // Terima Peminjaman induk
    {
        if ($user->hasRole(User::ROLE_GURU)) {
            return $peminjaman->id_guru === $user->id &&
                   in_array($peminjaman->status, [Peminjaman::STATUS_MENUNGGU_PERSETUJUAN]);
        }
        return false; // Admin sudah true
    }

    /**
     * Menentukan apakah pengguna dapat mengupdate detail item peminjaman.
     * Misalnya, mengubah catatan pada item (jarang terjadi, biasanya status yang diubah).
     * Untuk perubahan status (Diambil, Dikembalikan), gunakan ability yang lebih spesifik.
     */
    public function update(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        // Admin sudah true.
        // Guru mungkin bisa edit jika Peminjaman miliknya dan status masih 'Menunggu Persetujuan'.
        if ($user->hasRole(User::ROLE_GURU)) {
            return $detailPeminjaman->peminjaman->id_guru === $user->id &&
                   $detailPeminjaman->peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN;
        }
        // Operator mungkin bisa update catatan jika terkait dengan barangnya.
        if ($user->hasRole(User::ROLE_OPERATOR)) {
             $barangQr = $detailPeminjaman->barangQrCode;
             if ($barangQr && $barangQr->id_ruangan && $user->ruanganYangDiKelola()->where('id', $barangQr->id_ruangan)->exists()) {
                 return in_array($detailPeminjaman->peminjaman->status, [Peminjaman::STATUS_MENUNGGU_PERSETUJUAN, Peminjaman::STATUS_DISETUJUI, Peminjaman::STATUS_SEDANG_DIPINJAM]);
             }
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus item dari peminjaman.
     * Biasanya dilakukan saat Peminjaman masih 'Menunggu Persetujuan'.
     */
    public function delete(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        // Admin sudah true.
        if ($user->hasRole(User::ROLE_GURU)) {
            return $detailPeminjaman->peminjaman->id_guru === $user->id &&
                   $detailPeminjaman->peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN;
        }
        return false;
    }

    /**
     * Menentukan apakah pengguna (Operator) dapat memproses penyerahan (handover) item ini.
     */
    public function processHandover(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        if (!$user->hasRole(User::ROLE_OPERATOR)) {
            return false;
        }
        // Item harus dalam status 'Disetujui' dan Peminjaman induk juga 'Disetujui'
        if ($detailPeminjaman->status_unit !== DetailPeminjaman::STATUS_ITEM_DISETUJUI ||
            $detailPeminjaman->peminjaman->status !== Peminjaman::STATUS_DISETUJUI) {
            return false;
        }
        // Operator harus bertanggung jawab atas ruangan tempat barang berasal
        $barangQr = $detailPeminjaman->barangQrCode;
        return $barangQr && $barangQr->id_ruangan && $user->ruanganYangDiKelola()->where('id', $barangQr->id_ruangan)->exists();
    }

    /**
     * Menentukan apakah pengguna (Operator) dapat memproses pengembalian (return) item ini.
     */
    public function processReturn(User $user, DetailPeminjaman $detailPeminjaman): bool
    {
        if (!$user->hasRole(User::ROLE_OPERATOR)) {
            return false;
        }
        // Item harus dalam status 'Diambil' atau 'Rusak Saat Dipinjam'
        if (!in_array($detailPeminjaman->status_unit, [DetailPeminjaman::STATUS_ITEM_DIAMBIL, DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM])) {
            return false;
        }
        // Peminjaman induk harus 'Sedang Dipinjam' atau 'Terlambat'
        if (!in_array($detailPeminjaman->peminjaman->status, [Peminjaman::STATUS_SEDANG_DIPINJAM, Peminjaman::STATUS_TERLAMBAT])) {
            return false;
        }
        // Operator harus bertanggung jawab atas ruangan tempat barang berasal (atau ruangan tujuan pengembalian jika ditentukan)
        // Untuk kesederhanaan, kita cek ruangan asal barang
        $barangQr = $detailPeminjaman->barangQrCode;
        return $barangQr && $barangQr->id_ruangan && $user->ruanganYangDiKelola()->where('id', $barangQr->id_ruangan)->exists();
    }
}