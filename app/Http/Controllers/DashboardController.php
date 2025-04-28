<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Barang;
use App\Models\User;
use App\Models\Peminjaman;


class DashboardController extends Controller
{
    public function admin()
    {
        $jumlahBarang = Barang::count();
        $jumlahUser = User::count();

        return view('admin.dashboard', compact('jumlahBarang', 'jumlahUser'));
    }

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

    public function guru()
    {
        $user = Auth::user();
        $jumlahPeminjaman = Peminjaman::where('id_peminjam', $user->id)->count();

        return view('guru.dashboard', compact('jumlahPeminjaman'));
    }
}


