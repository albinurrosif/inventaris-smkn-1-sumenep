<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PeminjamanAdminController extends Controller
{
    /**
     * Menampilkan daftar peminjaman untuk admin
     */
    public function index()
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.peminjaman.index', compact('peminjaman'));
    }

    /**
     * Menampilkan detail peminjaman untuk admin
     */
    public function show($id)
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan', 'pengajuanDisetujuiOleh'])
            ->findOrFail($id);

        return view('admin.peminjaman.show', compact('peminjaman'));
    }
}
