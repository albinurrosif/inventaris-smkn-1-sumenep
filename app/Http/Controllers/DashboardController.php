<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use App\Models\BarangQrCode; // Ditambahkan untuk query unit
use Illuminate\Http\Request; // Ditambahkan jika Anda akan menggunakannya
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View; // Ditambahkan untuk return type hinting
use Illuminate\Http\RedirectResponse; // Ditambahkan untuk return type hinting

class DashboardController extends Controller
{
    // =========================================================================
    //  Dashboard Admin
    // =========================================================================

    /**
     * Menampilkan dashboard untuk admin.
     */
    public function admin(): View
    {
        // Jumlah jenis barang (induk)
        $jumlahJenisBarang = Barang::count();
        // Jumlah unit barang fisik
        $jumlahUnitBarang = BarangQrCode::count();
        $jumlahUser = User::count();
        // Tambahkan data lain yang relevan untuk admin dashboard
        // Misalnya: jumlah peminjaman menunggu persetujuan, jumlah barang rusak, dll.
        $peminjamanMenunggu = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)->count();


        return view('admin.dashboard', compact('jumlahJenisBarang', 'jumlahUnitBarang', 'jumlahUser', 'peminjamanMenunggu'));
    }

    // =========================================================================
    //  Dashboard Operator
    // =========================================================================

    /**
     * Menampilkan dashboard untuk operator.
     */
    public function operator(): View|RedirectResponse
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user && $user->hasRole(User::ROLE_OPERATOR)) {
            // Ambil semua ruangan yang dikelola oleh operator ini
            // Menggunakan nama relasi yang benar: ruanganYangDiKelola
            $ruanganDikelola = $user->ruanganYangDiKelola; // Ini akan mengambil collection Ruangan

            if ($ruanganDikelola->isNotEmpty()) {
                $ruanganIds = $ruanganDikelola->pluck('id')->toArray();

                // Jumlah JENIS BARANG yang memiliki UNIT di ruangan yang dikelola operator
                $jumlahJenisBarangDiRuanganOperator = Barang::whereHas('qrCodes', function ($query) use ($ruanganIds) {
                    $query->whereIn('id_ruangan', $ruanganIds)->whereNull('deleted_at'); // Hanya unit aktif
                })->count();

                // Jumlah UNIT BARANG FISIK di ruangan yang dikelola operator
                $jumlahUnitBarangDiRuanganOperator = BarangQrCode::whereIn('id_ruangan', $ruanganIds)
                    ->whereNull('deleted_at') // Hanya unit aktif
                    ->count();

                // Riwayat peminjaman yang perlu diproses/disetujui oleh operator ini
                // Ini mungkin perlu logika yang lebih kompleks, misalnya peminjaman yang unitnya ada di ruangan operator
                // Untuk contoh sederhana, kita ambil peminjaman yang statusnya menunggu dan unitnya ada di ruangan operator
                $peminjamanMenungguOperator = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                    ->whereHas('detailPeminjaman.barangQrCode', function ($query) use ($ruanganIds) {
                        $query->whereIn('id_ruangan', $ruanganIds);
                    })
                    ->latest()
                    ->take(5)
                    ->get();

                return view('operator.dashboard', compact(
                    'jumlahJenisBarangDiRuanganOperator',
                    'jumlahUnitBarangDiRuanganOperator',
                    'peminjamanMenungguOperator',
                    'ruanganDikelola' // Kirim juga daftar ruangan yang dikelola jika perlu ditampilkan
                ));
            } else {
                // Operator tidak mengelola ruangan sama sekali
                return view('operator.dashboard', [
                    'jumlahJenisBarangDiRuanganOperator' => 0,
                    'jumlahUnitBarangDiRuanganOperator' => 0,
                    'peminjamanMenungguOperator' => collect(), // Collection kosong
                    'ruanganDikelola' => collect()
                ]);
            }
        } else {
            // Jika bukan operator atau user tidak ditemukan, redirect atau tampilkan error
            Auth::logout(); // Contoh: logout jika role tidak sesuai
            return redirect()->route('login')->with('error', 'Akses tidak sah.');
        }
    }

    // =========================================================================
    //  Dashboard Guru
    // =========================================================================

    /**
     * Menampilkan dashboard untuk guru.
     */
    public function guru(): View|RedirectResponse
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user && $user->hasRole(User::ROLE_GURU)) {
            // Jumlah peminjaman yang sedang aktif (status 'Sedang Dipinjam') oleh guru ini
            $jumlahPeminjamanAktif = Peminjaman::where('id_guru', $user->id)
                ->where('status', Peminjaman::STATUS_SEDANG_DIPINJAM)
                ->count();
            // Jumlah pengajuan peminjaman yang menunggu persetujuan oleh guru ini
            $jumlahPengajuanMenunggu = Peminjaman::where('id_guru', $user->id)
                ->where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                ->count();
            // Riwayat 5 peminjaman terakhir
            $riwayatPeminjamanGuru = Peminjaman::where('id_guru', $user->id)
                ->latest()
                ->take(5)
                ->get();

            return view('guru.dashboard', compact(
                'jumlahPeminjamanAktif',
                'jumlahPengajuanMenunggu',
                'riwayatPeminjamanGuru'
            ));
        } else {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akses tidak sah.');
        }
    }

    /**
     * Mengarahkan pengguna ke dashboard yang sesuai berdasarkan peran mereka.
     */
    public function redirectDashboard(): RedirectResponse
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user) {
            if ($user->hasRole(User::ROLE_ADMIN)) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
                return redirect()->route('operator.dashboard');
            } elseif ($user->hasRole(User::ROLE_GURU)) {
                return redirect()->route('guru.dashboard');
            } else {
                // Role tidak dikenal, logout dan redirect ke login
                Auth::logout();
                return redirect()->route('login')->with('error', 'Peran pengguna tidak valid.');
            }
        }
        return redirect()->route('login'); // Jika tidak ada user, ke login
    }
}
