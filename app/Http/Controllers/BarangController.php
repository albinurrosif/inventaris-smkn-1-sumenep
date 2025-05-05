<?php

namespace App\Http\Controllers;

use App\Exports\BarangExport;
use App\Imports\BarangImport;
use App\Models\Barang;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class BarangController extends Controller
{
    // =========================================================================
    //  Barang Management
    // =========================================================================

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ruanganId = $request->get('ruangan_id');
        $ruanganList = Ruangan::all();
        $user = Auth::user();
        $ruangan = Ruangan::all();

        $barang = Barang::with('ruangan')
            ->when($ruanganId, function ($query, $ruanganId) {
                return $query->where('id_ruangan', $ruanganId);
            })
            ->when($user->role === 'Operator', function ($query) use ($user) {
                // Hanya tampilkan barang di ruangan yang dikelola operator
                $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
                return $query->whereIn('id_ruangan', $ruanganYangDiKelola);
            })
            ->get();

        // Membedakan view berdasarkan peran pengguna
        if (Auth::user()->role == 'Admin') {
            return view('admin.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'ruangan'));
        } elseif (Auth::user()->role == 'Operator') {
            return view('operator.barang.index', compact('barang', 'ruanganList', 'ruanganId'));
        }
        //default
        return view('admin.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'ruangan'));
    }

    public function indexOperator(Request $request)
    {
        $ruanganId = $request->get('ruangan_id');
        $user = Auth::user();

        // Ambil hanya ruangan yang dikelola oleh operator
        $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->get();

        // Jika tidak ada ruangan yang dikelola
        if ($ruanganYangDiKelola->isEmpty()) {
            // Tampilkan pesan tidak ada data
            return view('operator.barang.index', [
                'barang' => [],
                'ruanganList' => [],
                'ruanganId' => null,
                'tidak_ada_ruangan' => true, // Tambahkan variabel ini
            ]);
        }

        // Ambil ID ruangan yang dikelola
        $ruanganIds = $ruanganYangDiKelola->pluck('id')->toArray();

        $barang = Barang::with('ruangan')
            ->whereIn('id_ruangan', $ruanganIds) // Filter barang berdasarkan ruangan yang dikelola
            ->when($ruanganId, function ($query, $ruanganId) {
                return $query->where('id_ruangan', $ruanganId);
            })
            ->get();

        return view('operator.barang.index', [
            'barang' => $barang,
            'ruanganList' => $ruanganYangDiKelola, // Kirimkan hanya ruangan yang dikelola
            'ruanganId' => $ruanganId,
            'tidak_ada_ruangan' => false,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk_model' => 'nullable|string|max:255',
            'no_seri_pabrik' => 'nullable|string|max:100',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan_pembelian' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kode_barang' => 'required|string|max:50|unique:barang,kode_barang',
            'jumlah_barang' => 'required|integer|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'sumber' => 'nullable|string|max:100',
            'keadaan_barang' => 'required|in:Baik,Kurang Baik,Rusak Berat',
            'keterangan_mutasi' => 'nullable|string',
            'id_ruangan' => 'required|exists:ruangan,id',
        ]);

        $ruangan = Ruangan::findOrFail($request->id_ruangan);
        $user = Auth::user();

        if ($user->role === 'Operator' && $ruangan->id_operator != $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menambah barang di ruangan ini.');
        }

        Barang::create($validated);
        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $barang = Barang::with('ruangan')->findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk melihat detail barang ini.');
            }
        }
        return view('admin.barang.show', compact('barang'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $barang = Barang::findOrFail($id);
        $ruangan = Ruangan::all();
        $user = Auth::user();

        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengedit barang ini.');
            }
        }
        return view('admin.barang.edit', compact('barang', 'ruangan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk_model' => 'nullable|string|max:255',
            'no_seri_pabrik' => 'nullable|string|max:100',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan_pembelian' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kode_barang' => 'required|string|max:50|unique:barang,kode_barang,' . $id,
            'jumlah_barang' => 'required|integer|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'sumber' => 'nullable|string|max:100',
            'keadaan_barang' => 'required|in:Baik,Kurang Baik,Rusak Berat',
            'keterangan_mutasi' => 'nullable|string',
            'id_ruangan' => 'required|exists:ruangan,id',
        ]);

        $barang = Barang::findOrFail($id);
        $ruangan = Ruangan::findOrFail($request->id_ruangan);
        $user = Auth::user();

        if ($user->role === 'Operator' && $ruangan->id_operator != $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah barang di ruangan ini.');
        }
        $barang->update($validated);
        return redirect()->route('barang.index')->with('success', 'Barang berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();
        $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');

        if ($user->role === 'Operator' && !in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus barang ini.');
        }

        if ($barang->peminjaman()->exists()) {
            return redirect()->back()->with('error', 'Barang sedang dipinjam dan tidak dapat dihapus.');
        }
        $barang->delete();
        return redirect()->route('barang.index')->with('success', 'Barang berhasil dihapus');
    }

    // =========================================================================
    //  Excel Import/Export
    // =========================================================================

    /**
     * Export barang data to Excel.
     */
    public function export()
    {
        return Excel::download(new BarangExport, 'barang.xlsx');
    }
    public function exportOperator()
    {
        $user = Auth::user();
        $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
        $barang = Barang::whereIn('id_ruangan', $ruanganYangDiKelola)->get();

        // Pass the filtered data to the export class
        $export = new BarangExport($barang);
        return Excel::download($export, 'barang_operator.xlsx');
    }

    /**
     * Import barang data from Excel.
     */
    public function import(Request $request)
    {
        Log::info('Import Barang dipanggil', [
            'user' => Auth::user()?->email,
            'filename' => $request->file('file')?->getClientOriginalName(),
        ]);

        $request->validate(['file' => 'required|file|mimes:xlsx,csv']);

        try {
            Excel::import(new BarangImport, $request->file('file'));
            return redirect()->route('barang.index')->with('success', 'Data berhasil diimpor.');
        } catch (ValidationException $e) {
            $failures = $e->failures();
            return redirect()->back()->withErrors(['import' => 'Terdapat kesalahan pada file Excel.'])->with('failures', $failures);
        }
    }
}
