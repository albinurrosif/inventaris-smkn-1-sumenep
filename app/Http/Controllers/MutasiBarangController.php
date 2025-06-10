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
    public function index(Request $request): View
    {
        // PERUBAHAN: Menggunakan Policy untuk otorisasi
        $this->authorize('viewAny', MutasiBarang::class);
        $user = Auth::user();

        // PENYEMPURNAAN: Eager load dengan nama relasi yang benar dari model
        $query = MutasiBarang::with(['barangQrCode.barang', 'ruanganAsal', 'ruanganTujuan', 'admin']);

        // PENYEMPURNAAN: Logika filter untuk Operator yang lebih rapi
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Menggunakan relasi yang sudah ada di model User
            $ruanganYangDiKelolaIds = $user->ruanganYangDiKelola()->pluck('id');

            $query->where(function (Builder $q) use ($ruanganYangDiKelolaIds) {
                $q->whereIn('id_ruangan_asal', $ruanganYangDiKelolaIds)
                    ->orWhereIn('id_ruangan_tujuan', $ruanganYangDiKelolaIds);
            });
        }

        // Filter berdasarkan id barang qr code jika diberikan
        if ($request->has('id_barang_qr_code')) {
            $query->where('id_barang_qr_code', $request->id_barang_qr_code);
        }

        $riwayatMutasi = $query->latest('tanggal_mutasi')->paginate(20)->withQueryString();

        // PERUBAHAN: Mengarahkan ke satu view terpadu
        return view('pages.mutasi.index', compact('riwayatMutasi', 'request'));
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
