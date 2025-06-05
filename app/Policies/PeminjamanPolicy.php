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
     * Memberikan hak akses super-user kepada Admin.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(User::ROLE_ADMIN)) { //
            return true;
        }
        return null; //
    }

    /**
     * Menentukan apakah user bisa melihat daftar peminjaman.
     * Semua user bisa mengakses halaman index, tetapi controller akan memfilter datanya.
     */
    public function viewAny(User $user): bool
    {
        return true; //
    }

    /**
     * Menentukan apakah user bisa melihat detail sebuah peminjaman.
     */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah ditangani oleh before()
        if ($user->hasRole(User::ROLE_OPERATOR)) { //
            return $this->isOperatorOfAnyItem($user, $peminjaman); //
        }
        if ($user->hasRole(User::ROLE_GURU)) { //
            return $user->id === $peminjaman->id_guru; //
        }
        return false;
    }

    /**
     * Menentukan apakah user bisa membuat pengajuan peminjaman baru.
     */
    public function create(User $user): bool
    {
        // Admin sudah ditangani oleh before(), tapi biasanya Guru yang create
        return $user->hasRole(User::ROLE_GURU); //
    }

    /**
     * Menentukan apakah user (Guru) bisa membatalkan pengajuan peminjaman.
     */
    public function cancel(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah ditangani oleh before()
        return $user->id === $peminjaman->id_guru && $peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN; //
    }

    /**
     * Menentukan apakah user (Operator) bisa menyetujui atau menolak peminjaman.
     */
    public function manage(User $user, Peminjaman $peminjaman): bool
    {
        // Admin sudah ditangani oleh before()
        if ($peminjaman->status !== Peminjaman::STATUS_MENUNGGU_PERSETUJUAN) { //
            return false;
        }
        if ($user->hasRole(User::ROLE_OPERATOR)) { //
            return $this->isOperatorOfAllItems($user, $peminjaman); //
        }
        return false;
    }

    /**
     * Menentukan apakah user (Operator) bisa memproses pengambilan sebuah unit barang.
     */
    public function processHandover(User $user, DetailPeminjaman $detail): bool
    {
        // Admin sudah ditangani oleh before()
        if (!$user->hasRole(User::ROLE_OPERATOR)) { //
            return false;
        }
        if ($detail->status_unit !== DetailPeminjaman::STATUS_ITEM_DISETUJUI) { //
            return false;
        }
        // Pastikan barangQrCode ada sebelum akses id_ruangan
        if (!$detail->barangQrCode) {
            return false;
        }
        return $user->ruanganYangDiKelola()->where('id', $detail->barangQrCode->id_ruangan)->exists(); //
    }

    /**
     * Menentukan apakah user (Operator) bisa memproses pengembalian sebuah unit barang.
     */
    public function processReturn(User $user, DetailPeminjaman $detail): bool
    {
        // Admin sudah ditangani oleh before()
        if (!$user->hasRole(User::ROLE_OPERATOR)) { //
            return false;
        }
        if ($detail->status_unit !== DetailPeminjaman::STATUS_ITEM_DIAMBIL) { //
            return false;
        }
        // Pastikan barangQrCode ada sebelum akses id_ruangan
        if (!$detail->barangQrCode) {
            return false;
        }
        // Saat pengembalian, barang bisa saja dikembalikan ke ruangan asal atau ruangan lain
        // yang dikelola operator. Untuk simplicity, kita cek apakah operator mengelola ruangan barang tersebut
        // (yang seharusnya merupakan ruangan asal atau ruangan tujuan jika barang tidak berpindah tangan).
        // Jika logika pengembalian bisa ke ruangan mana saja yang dikelola Operator, ini sudah cukup.
        return $user->ruanganYangDiKelola()->where('id', $detail->barangQrCode->id_ruangan)->exists(); //
    }


    // === FUNGSI BANTU (HELPER FUNCTIONS) ===

    /**
     * Memeriksa apakah Operator adalah penanggung jawab SEMUA barang dalam sebuah peminjaman.
     */
    private function isOperatorOfAllItems(User $operator, Peminjaman $peminjaman): bool
    {
        if ($peminjaman->detailPeminjaman->isEmpty()) {
            return false; // Tidak ada item untuk diperiksa
        }
        $idRuanganOperator = $operator->ruanganYangDiKelola()->pluck('id'); //

        foreach ($peminjaman->detailPeminjaman as $detail) {
            if (!$detail->barangQrCode || is_null($detail->barangQrCode->id_ruangan) || !$idRuanganOperator->contains($detail->barangQrCode->id_ruangan)) {
                return false; // Jika satu saja barang tidak di ruangan operator, maka false
            }
        }
        return true; // Semua barang ada di ruangan yang dikelola operator
    }

    /**
     * Memeriksa apakah Operator adalah penanggung jawab SETIDAKNYA SATU barang dalam peminjaman.
     */
    private function isOperatorOfAnyItem(User $operator, Peminjaman $peminjaman): bool
    {
        $idRuanganBarang = $peminjaman->detailPeminjaman->pluck('barangQrCode.id_ruangan')->filter(); // Ambil yg tidak null
        if ($idRuanganBarang->isEmpty() && !$peminjaman->detailPeminjaman->isEmpty()) {
            return false; // Ada detail tapi tidak ada yang punya id_ruangan
        }
        if ($peminjaman->detailPeminjaman->isEmpty()) {
            return false; // Tidak ada item sama sekali
        }
        return $operator->ruanganYangDiKelola()->whereIn('id', $idRuanganBarang)->exists(); //
    }
}
