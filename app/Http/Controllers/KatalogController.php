<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;
use App\Models\Peminjaman;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class KatalogController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BarangQrCode::class);

        $query = BarangQrCode::with(['barang.kategori', 'ruangan'])
            ->where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNull('deleted_at')
            ->whereNull('id_pemegang_personal')
            ->whereNotNull('id_ruangan');

        // PENYESUAIAN KUNCI: Hanya ambil barang yang tidak terikat di peminjaman aktif lain.
        $query->whereDoesntHave('peminjamanDetails', function ($q) {
            $q->whereHas('peminjaman', function ($qPeminjaman) {
                $qPeminjaman->whereNotIn('status', [
                    Peminjaman::STATUS_SELESAI,
                    Peminjaman::STATUS_DITOLAK,
                    Peminjaman::STATUS_DIBATALKAN,
                ]);
            });
        });

        // Filter berdasarkan pencarian teks
        if ($request->search) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('nama_barang', 'like', '%' . $request->search . '%')
                    ->orWhere('merk_model', 'like', '%' . $request->search . '%');
            });
        }

        // PENAMBAHAN: Filter berdasarkan Kategori
        if ($request->id_kategori) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('id_kategori', $request->id_kategori);
            });
        }

        // PENAMBAHAN: Filter berdasarkan Ruangan
        if ($request->id_ruangan) {
            $query->where('id_ruangan', $request->id_ruangan);
        }

        $barangTersedia = $query->latest('id')->paginate(12)->withQueryString();

        // Data untuk dropdown filter
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();

        return view('pages.katalog.index', compact('barangTersedia', 'request', 'kategoriList', 'ruanganList'));
    }
}
