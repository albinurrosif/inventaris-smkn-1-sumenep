<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;
use App\Models\Peminjaman; // <-- PASTIKAN IMPORT INI ADA
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class KatalogController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BarangQrCode::class);

        // ==================================================================
        // LANGKAH 1: DETEKSI KONTEKS & PERSIAPAN VARIABEL
        // ==================================================================

        $peminjamanId = $request->input('peminjaman_id');
        $keranjangIds = session()->get('keranjang_peminjaman', []);
        $jumlahDiKeranjang = count($keranjangIds);
        $lockedRuangan = null; // Variabel ini akan kita isi berdasarkan konteks

        // Cek konteks berdasarkan prioritas: Mode Edit lebih utama
        if ($peminjamanId) {
            // KONTEKS: User datang dari halaman EDIT peminjaman
            $peminjaman = Peminjaman::with('detailPeminjaman.barangQrCode.ruangan')->find($peminjamanId);
            if ($peminjaman && $peminjaman->detailPeminjaman->isNotEmpty()) {
                // Ambil ruangan dari item pertama sebagai patokan "ruangan terkunci"
                $lockedRuangan = $peminjaman->detailPeminjaman->first()->barangQrCode->ruangan;
            }
        } else if (!empty($keranjangIds)) {
            // KONTEKS: User sedang membuat pengajuan BARU dan sudah ada item di keranjang
            $barangPertama = BarangQrCode::with('ruangan')->find($keranjangIds[0]);
            if ($barangPertama) {
                $lockedRuangan = $barangPertama->ruangan;
            }
        }

        // ==================================================================
        // LANGKAH 2: MEMBANGUN QUERY UNTUK MENAMPILKAN BARANG
        // ==================================================================
        $query = BarangQrCode::with(['barang.kategori', 'ruangan'])
            ->where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNull('deleted_at')
            ->whereNull('id_pemegang_personal')
            ->whereNotNull('id_ruangan');

        // Filter agar barang yang sudah ada di peminjaman lain tidak muncul
        $query->whereDoesntHave('peminjamanDetails', function ($q) {
            $q->whereHas('peminjaman', function ($qPeminjaman) {
                $qPeminjaman->whereNotIn('status', [
                    Peminjaman::STATUS_SELESAI,
                    Peminjaman::STATUS_DITOLAK,
                    Peminjaman::STATUS_DIBATALKAN,
                ]);
            });
        });

        // Terapkan filter dari form pencarian
        if ($request->search) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('nama_barang', 'like', '%' . $request->search . '%')
                    ->orWhere('merk_model', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->id_kategori) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('id_kategori', $request->id_kategori);
            });
        }
        if ($request->id_ruangan) {
            $query->where('id_ruangan', $request->id_ruangan);
        }

        // TERAPKAN FILTER RUANGAN TERKUNCI JIKA ADA
        if ($lockedRuangan) {
            $query->where('id_ruangan', $lockedRuangan->id);
        }

        $barangTersedia = $query->latest('id')->paginate(12)->withQueryString();

        // Data untuk dropdown filter
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();

        // ==================================================================
        // LANGKAH 3: MENGIRIM SEMUA DATA YANG DIPERLUKAN KE VIEW
        // ==================================================================
        return view('pages.katalog.index', compact(
            'barangTersedia',
            'request',
            'kategoriList',
            'ruanganList',
            'lockedRuangan',      // Untuk menampilkan alert ruangan terkunci
            'jumlahDiKeranjang',  // Untuk menampilkan angka di tombol "Lanjutkan"
            'peminjamanId'        // Untuk membuat link "Kembali ke Halaman Edit"
        ));
    }
}
