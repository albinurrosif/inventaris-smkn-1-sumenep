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
 * Show the form for creating a new resource.
 */
    public function create()
    {
        $user = Auth::user();
        // Hanya admin dan operator yang bisa membuat mutasi
        if ($user->role != 'Admin' && $user->role != 'Operator') {
            abort(403, 'Unauthorized action.');
        }

        // Ambil data yang dibutuhkan untuk form
        if ($user->role == 'Admin') {
            $barangQrCodes = BarangQrCode::where('status', 'Tersedia')->get(); // Hanya tampilkan yang tersedia
            $ruangan = Ruangan::all();
        } else {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->get();
            $barangQrCodes = BarangQrCode::where('status', 'Tersedia')->whereHas('barang', function ($q) use ($ruanganYangDiKelola) {
                $q->whereIn('id_ruangan', $ruanganYangDiKelola->pluck('id'));
            })->get(); // Hanya tampilkan yang tersedia dan di ruangan operator
        }


        return view('mutasi_barang.create', compact('barangQrCodes', 'ruangan'));
    }

    /**
 * Store a newly created resource in storage.
 */
    public function store(Request $request)
    {
        $user = Auth::user();
        // Hanya admin dan operator yang bisa menyimpan mutasi
        if ($user->role != 'Admin' && $user->role != 'Operator') {
            abort(403, 'Unauthorized action.');
        }

        // Validasi input
        $request->validate([
            'id_barang_qr_code' => 'required|exists:barang_qr_code,id',
            'id_ruangan_lama' => 'required|exists:ruangan,id',
            'id_ruangan_baru' => 'nullable|exists:ruangan,id',
            'tanggal_mutasi' => 'required|date',
            'alasan' => 'nullable|string',
            // 'id_user' => 'nullable|exists:users,id', // Tidak perlu validasi, diisi otomatis
        ], [
            'id_barang_qr_code.exists' => 'Barang QR Code tidak valid.',
            'id_ruangan_lama.exists' => 'Ruangan asal tidak valid.',
            'id_ruangan_baru.exists' => 'Ruangan tujuan tidak valid.',
            'tanggal_mutasi.date' => 'Tanggal mutasi harus berupa tanggal yang valid.',
        ]);

        // Cek apakah barang QR code ada dan statusnya tersedia
        $barangQrCode = BarangQrCode::find($request->id_barang_qr_code);
        if (!$barangQrCode) {
            return redirect()->back()->withInput()->withErrors(['id_barang_qr_code' => 'Barang QR Code tidak ditemukan.']);
        }
        if ($barangQrCode->status != 'Tersedia') {
            return redirect()->back()->withInput()->withErrors(['id_barang_qr_code' => 'Barang QR Code tidak tersedia untuk mutasi.']);
        }

        // Cek apakah ruangan lama dan baru sama
        if ($request->id_ruangan_lama == $request->id_ruangan_baru) {
            return redirect()->back()->withInput()->withErrors(['id_ruangan_baru' => 'Ruangan tujuan tidak boleh sama dengan ruangan asal.']);
        }

        // Mulai transaksi database
        DB::beginTransaction();

        try {
            // Buat mutasi barang
            $mutasiBarang = MutasiBarang::create([
                'id_barang_qr_code' => $request->id_barang_qr_code,
                'id_ruangan_lama' => $request->id_ruangan_lama,
                'id_ruangan_baru' => $request->id_ruangan_baru,
                'tanggal_mutasi' => $request->tanggal_mutasi,
                'alasan' => $request->alasan,
                'id_user' => $user->id, // Ambil ID user yang sedang login
            ]);

            // Update status barang QR Code menjadi 'Dipinjam' atau 'Tetap'
            $barangQrCode->status = $request->id_ruangan_baru ? 'Dipinjam' : 'Tetap'; // Jika ada ruangan baru, statusnya 'Dipinjam', jika tidak, 'Tetap'
            $barangQrCode->save();

            // Commit transaksi jika semua berhasil
            DB::commit();

            return redirect()->route('mutasi-barang.index')->with('success', 'Mutasi barang berhasil disimpan.');
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollback();
            return redirect()->back()->withInput()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan mutasi: ' . $e->getMessage()]);
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

    /**
 * Show the form for editing the specified resource.
 */
    public function edit(string $id)
    {
        $mutasiBarang = MutasiBarang::findOrFail($id);
        $user = Auth::user();

        // Hanya admin dan operator yang bisa mengedit mutasi
        if ($user->role != 'Admin' && $user->role != 'Operator') {
            abort(403, 'Unauthorized action.');
        }

        // Ambil data yang dibutuhkan untuk form
        if ($user->role == 'Admin') {
            $barangQrCodes = BarangQrCode::all();
            $ruangan = Ruangan::all();
        } else {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->get();
            $barangQrCodes = BarangQrCode::whereHas('barang', function ($q) use ($ruanganYangDiKelola) {
                $q->whereIn('id_ruangan', $ruanganYangDiKelola->pluck('id'));
            })->get();
        }

        return view('mutasi_barang.edit', compact('mutasiBarang', 'barangQrCodes', 'ruangan'));
    }

    /**
 * Update the specified resource in storage.
 */
    public function update(Request $request, string $id)
    {
        $mutasiBarang = MutasiBarang::findOrFail($id);
        $user = Auth::user();

        // Hanya admin dan operator yang bisa mengupdate mutasi
        if ($user->role != 'Admin' && $user->role != 'Operator') {
            abort(403, 'Unauthorized action.');
        }

        // Validasi input
        $request->validate([
            'id_barang_qr_code' => 'required|exists:barang_qr_code,id',
            'id_ruangan_lama' => 'required|exists:ruangan,id',
            'id_ruangan_baru' => 'nullable|exists:ruangan,id',
            'tanggal_mutasi' => 'required|date',
            'alasan' => 'nullable|string',
            // 'id_user' => 'nullable|exists:users,id', // Tidak perlu validasi, diisi otomatis
        ], [
            'id_barang_qr_code.exists' => 'Barang QR Code tidak valid.',
            'id_ruangan_lama.exists' => 'Ruangan asal tidak valid.',
            'id_ruangan_baru.exists' => 'Ruangan tujuan tidak valid.',
            'tanggal_mutasi.date' => 'Tanggal mutasi harus berupa tanggal yang valid.',

        ]);

        // Cek apakah barang QR code ada
        $barangQrCode = BarangQrCode::find($request->id_barang_qr_code);
        if (!$barangQrCode) {
            return redirect()->back()->withInput()->withErrors(['id_barang_qr_code' => 'Barang QR Code tidak ditemukan.']);
        }

        // Cek apakah ruangan lama dan baru sama
        if ($request->id_ruangan_lama == $request->id_ruangan_baru) {
            return redirect()->back()->withInput()->withErrors(['id_ruangan_baru' => 'Ruangan tujuan tidak boleh sama dengan ruangan asal.']);
        }

        // Mulai transaksi database
        DB::beginTransaction();

        try {
            // Simpan perubahan
            $mutasiBarang->update([
                'id_barang_qr_code' => $request->id_barang_qr_code,
                'id_ruangan_lama' => $request->id_ruangan_lama,
                'id_ruangan_baru' => $request->id_ruangan_baru,
                'tanggal_mutasi' => $request->tanggal_mutasi,
                'alasan' => $request->alasan,
                'id_user' => $user->id, // Set user yang update
            ]);

            // Update status barang QR Code
            if ($request->id_ruangan_baru != $mutasiBarang->id_ruangan_baru) { // Hanya jika ruangannya berubah
                $barangQrCode->status = $request->id_ruangan_baru ? 'Dipinjam' : 'Tetap';
                $barangQrCode->save();
            }

            // Commit transaksi
            DB::commit();

            return redirect()->route('mutasi-barang.index')->with('success', 'Mutasi barang berhasil diupdate.');
        } catch (\Exception $e) {
            // Rollback transaksi
            DB::rollback();
            return redirect()->back()->withInput()->withErrors(['error' => 'Terjadi kesalahan saat mengupdate mutasi: ' . $e->getMessage()]);
        }
    }

    /**
 * Remove the specified resource from storage.
 */
    public function destroy(string $id)
    {
        $mutasiBarang = MutasiBarang::findOrFail($id);
        $user = Auth::user();

        // Hanya admin yang bisa menghapus mutasi
        if ($user->role != 'Admin') {
            abort(403, 'Unauthorized action.');
        }

        // Mulai transaksi database
        DB::beginTransaction();

        try {
            // Update status barang QR Code menjadi 'Tersedia'
            $barangQrCode = $mutasiBarang->barangQrCode; // Ambil relasi sebelum delete
            $barangQrCode->status = 'Tersedia';
            $barangQrCode->save();

            // Hapus mutasi barang
            $mutasiBarang->delete();

            // Commit transaksi
            DB::commit();

            return redirect()->route('mutasi-barang.index')->with('success', 'Mutasi barang berhasil dihapus.');
        } catch (\Exception $e) {
            // Rollback transaksi
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus mutasi: ' . $e->getMessage()]);
        }
    }
}
