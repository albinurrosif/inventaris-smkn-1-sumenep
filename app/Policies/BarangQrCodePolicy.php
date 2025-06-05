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
        return null; // Biarkan metode policy lain yang memutuskan untuk role selain Admin
    }

    /**
     * Menentukan apakah pengguna dapat melihat daftar unit barang (BarangQrCode).
     * Operator juga bisa (difilter di controller).
     */
    public function viewAny(User $user): bool
    {
        // Admin sudah ditangani oleh before()
        return $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail unit barang tertentu.
     * Operator hanya bisa melihat detail unit barang yang berada di ruangan yang dia kelola atau yang dipegangnya.
     */
    public function view(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        if ($user->hasRole(User::ROLE_OPERATOR)) { //
            // Jika unit dipegang personal oleh Operator ybs
            if ($barangQrCode->id_pemegang_personal === $user->id) {
                return true;
            }
            // Jika unit berada di ruangan yang dikelola oleh operator
            if ($barangQrCode->id_ruangan) { //
                return $user->ruanganYangDiKelola()->where('ruangans.id', $barangQrCode->id_ruangan)->exists(); //
            }
            // Operator tidak bisa lihat unit "mengambang" yang tidak dipegangnya
            // if (is_null($barangQrCode->id_ruangan) && is_null($barangQrCode->id_pemegang_personal)) {
            // return false;
            // } // Logika ini bisa disederhanakan karena sudah ada cek di atas
        }
        return false; //
    }

    /**
     * Menentukan apakah pengguna dapat membuat unit barang baru.
     * Operator bisa (misalnya menambah unit ke Jenis Barang yang sudah ada dan unitnya ada di ruangannya).
     */
    public function create(User $user): bool
    {
        // Admin sudah ditangani oleh before()
        // Pembuatan unit terikat pada Barang induk. Controller akan memvalidasi lebih lanjut
        // apakah Operator boleh menambah unit ke Barang tertentu (misal, berdasarkan ruangan unit lain).
        return $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Menentukan apakah pengguna dapat mengupdate unit barang tertentu.
     * Operator hanya bisa mengupdate unit barang yang bisa dilihatnya.
     */
    public function update(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        return $this->view($user, $barangQrCode); //
    }

    /**
     * Menentukan apakah pengguna dapat mengarsipkan unit barang tertentu.
     * (Menggantikan 'delete' untuk konsistensi dengan controller)
     * Operator hanya bisa mengarsipkan unit barang yang bisa dilihatnya.
     */
    public function archive(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        // if ($barangQrCode->status === BarangQrCode::STATUS_DIPINJAM) return false; // Bisa ditambahkan di sini atau di controller
        return $this->view($user, $barangQrCode);
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan unit barang yang diarsip.
     * Hanya Admin (sudah ditangani before(), jadi Operator tidak akan sampai sini).
     */
    public function restore(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        // Jika ingin eksplisit: return $user->hasRole(User::ROLE_ADMIN);
        return false; // Secara default Operator tidak bisa, Admin sudah true dari before()
    }

    /**
     * Menentukan apakah pengguna dapat melakukan mutasi pada unit barang.
     * Operator bisa jika unit asal ada di ruangannya.
     */
    public function mutasi(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        if ($user->hasRole(User::ROLE_OPERATOR)) { //
            if (is_null($barangQrCode->id_ruangan)) return false; // Tidak bisa mutasi jika tidak di ruangan
            return $user->ruanganYangDiKelola()->where('ruangans.id', $barangQrCode->id_ruangan)->exists(); //
        }
        return false; //
    }

    /**
     * Menentukan apakah pengguna dapat menyerahkan unit ke pemegang personal.
     * Operator bisa jika unit tersebut bisa dilihat/dikelolanya.
     */
    public function assignPersonal(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        // if ($barangQrCode->status === BarangQrCode::STATUS_DIPINJAM) return false;
        return $this->view($user, $barangQrCode);
    }

    /**
     * Menentukan apakah pengguna dapat mengembalikan unit dari pemegang personal ke ruangan.
     * Operator bisa jika unit tersebut bisa dilihatnya (misal, karena dia pemegang atau akan masuk ke ruangannya).
     */
    public function returnPersonal(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        if (is_null($barangQrCode->id_pemegang_personal)) { // Unit harus sedang dipegang personal
            return false;
        }
        // Operator bisa memproses jika dia adalah pemegang personal, atau jika target ruangan adalah miliknya.
        // Controller akan validasi target ruangan jika Operator bukan pemegang.
        // Untuk view umum sebelum action, cek apakah operator bisa view atau adalah pemegang.
        return $this->view($user, $barangQrCode) || $barangQrCode->id_pemegang_personal === $user->id;
    }

    /**
     * Menentukan apakah pengguna dapat mentransfer unit antar pemegang personal.
     * Admin bisa (via before).
     * Pengguna (Operator/Guru) bisa mentransfer JIKA dia adalah pemegang personal saat ini.
     */
    public function transferPersonal(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        if (is_null($barangQrCode->id_pemegang_personal)) {
            return false; // Barang tidak sedang dipegang personal
        }

        // Izinkan jika pengguna yang login adalah pemegang personal saat ini
        if ($barangQrCode->id_pemegang_personal === $user->id) {
            return true;
        }

        return false; // Jika bukan Admin dan bukan pemegang saat ini
    }


    /**
     * Menentukan apakah pengguna dapat mengunduh QR Code.
     * Sama dengan hak lihat unit.
     */
    public function downloadQr(User $user, BarangQrCode $barangQrCode): bool
    {
        // Admin sudah ditangani oleh before()
        return $this->view($user, $barangQrCode); //
    }

    /**
     * Menentukan apakah pengguna dapat mencetak QR Code (misalnya multiple).
     * Operator juga bisa.
     */
    public function printQr(User $user): bool
    {
        // Admin sudah ditangani oleh before()
        return $user->hasRole(User::ROLE_OPERATOR); //
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor daftar unit.
     * Operator juga bisa.
     */
    public function export(User $user): bool
    {
        // Admin sudah ditangani oleh before()
        return $user->hasRole(User::ROLE_OPERATOR);
    }
}
