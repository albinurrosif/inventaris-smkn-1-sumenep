<?php

namespace App\Http\Controllers;

use App\Models\MutasiBarang;
use App\Models\BarangQrCode; // Import model BarangQrCode
use App\Models\Ruangan; // Import model Ruangan
use App\Models\User; // Import model User
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Import untuk transaksi database

class MutasiBarangController extends Controller
{
    /**
 * Display a listing of the resource.
 */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Eager load relasi yang sering digunakan
        $mutasiBarang = MutasiBarang::with(['barangQrCode', 'ruanganLama', 'ruanganBaru', 'user']);

        // Filter berdasarkan ruangan operator jika perlu
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $mutasiBarang = $mutasiBarang->whereIn('id_ruangan_lama', $ruanganYangDiKelola)
                ->orWhereIn('id_ruangan_baru', $ruanganYangDiKelola);
        }

        // Filter berdasarkan id barang qr code jika diberikan
        if ($request->has('id_barang_qr_code')) {
            $mutasiBarang = $mutasiBarang->where('id_barang_qr_code', $request->id_barang_qr_code);
        }

        // Order by dan Pagination
        $mutasiBarang = $mutasiBarang->orderBy('tanggal_mutasi', 'desc')->paginate(10);

        // Get data untuk dropdown filter
        $barangQrCodes = BarangQrCode::all(); // Untuk dropdown Barang QR Code

        if ($user->role == 'Admin') {
            return view('admin.mutasi_barang.index', compact('mutasiBarang', 'barangQrCodes'));
        } else {
            return view('operator.mutasi_barang.index', compact('mutasiBarang', 'barangQrCodes'));
        }
    }



    /**
 * Display the specified resource.
 */
    public function show(string $id)
    {
        $mutasiBarang = MutasiBarang::with(['barangQrCode', 'ruanganLama', 'ruanganBaru', 'user'])->findOrFail($id);
        $user = Auth::user();

        // Hanya admin dan operator yang bisa melihat detail mutasi
        if ($user->role != 'Admin' && $user->role != 'Operator') {
            abort(403, 'Unauthorized action.');
        }

        if ($user->role == 'Admin') {
            return view('admin.mutasi_barang.show', compact('mutasiBarang'));
        } else {
            return view('operator.mutasi_barang.show', compact('mutasiBarang'));
        }
    }


}
