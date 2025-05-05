<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // =========================================================================
    //  Dashboard Admin
    // =========================================================================

    /**
     * Menampilkan dashboard untuk admin.
     */
    public function admin()
    {
        $jumlahBarang = Barang::count();
        $jumlahUser = User::count();

        return view('admin.dashboard', compact('jumlahBarang', 'jumlahUser'));
    }

    // =========================================================================
    //  Dashboard Operator
    // =========================================================================

    /**
     * Menampilkan dashboard untuk operator.
     */
    public function operator()
    {
        $user = Auth::user();

        if ($user) {
            // Ambil semua ruangan yang dikelola oleh operator ini
            $ruanganIds = $user->ruangan->pluck('id')->toArray();

            $jumlahBarang = Barang::whereIn('id_ruangan', $ruanganIds)->count();
            $riwayatPeminjaman = Peminjaman::where('id_peminjam', $user->id)->latest()->take(5)->get();

            return view('operator.dashboard', compact('jumlahBarang', 'riwayatPeminjaman'));
        } else {
            return redirect()->route('login');
        }
    }

    // =========================================================================
    //  Dashboard Guru
    // =========================================================================

    /**
     * Menampilkan dashboard untuk guru.
     */
    public function guru()
    {
        $user = Auth::user();
        $jumlahPeminjaman = Peminjaman::where('id_peminjam', $user->id)->count();

        return view('guru.dashboard', compact('jumlahPeminjaman'));
    }
}
