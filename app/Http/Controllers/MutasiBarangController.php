<?php

namespace App\Http\Controllers;

use App\Models\MutasiBarang;
use App\Models\User;
use App\Models\Ruangan; // Pastikan ini di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MutasiBarangController extends Controller
{
    // PENYEMPURNAAN: Mengaktifkan fitur otorisasi
    use AuthorizesRequests;

    /**
     * Menampilkan daftar riwayat mutasi barang.
     */

    public function index(Request $request)
    {
        $this->authorize('viewAny', MutasiBarang::class);

        // Ambil semua input filter dari request
        $filters = $request->only(['search', 'jenis_mutasi', 'id_user_pencatat', 'tanggal_mulai', 'tanggal_selesai']);

        $query = MutasiBarang::with(['barangQrCode.barang', 'ruanganAsal', 'ruanganTujuan', 'pemegangAsal', 'pemegangTujuan', 'admin'])
            ->filter($request) // <-- Menggunakan scope filter kita
            ->latest('tanggal_mutasi');

        $riwayatMutasi = $query->paginate(15)->withQueryString();

        // Data untuk dropdown filter
        $adminList = User::where('role', User::ROLE_ADMIN)->orWhere('role', User::ROLE_OPERATOR)->orderBy('username')->get();
        $jenisMutasiList = MutasiBarang::select('jenis_mutasi')->distinct()->pluck('jenis_mutasi');

        return view('pages.mutasi.index', compact('riwayatMutasi', 'adminList', 'jenisMutasiList', 'filters'));
    }

    /**
     * Menampilkan detail spesifik dari sebuah transaksi mutasi.
     * PERUBAHAN: Menggunakan Route Model Binding untuk kode yang lebih bersih.
     */
    public function show(MutasiBarang $mutasiBarang): View
    {
        // PERUBAHAN: Menggunakan Policy untuk otorisasi
        $this->authorize('view', $mutasiBarang);

        // Load relasi jika belum ter-load
        $mutasiBarang->load(['barangQrCode.barang', 'ruanganAsal', 'ruanganTujuan', 'admin']);

        // PERUBAHAN: Mengarahkan ke satu view terpadu
        return view('pages.mutasi.show', compact('mutasiBarang'));
    }
}
