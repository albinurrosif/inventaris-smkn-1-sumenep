<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use App\Exports\BarangExport;
use App\Imports\BarangImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Validators\ValidationException;



class BarangController extends Controller
{
    public function index(Request $request)
    {
        $ruanganId = $request->get('ruangan_id');
        $ruangan = Ruangan::all();
        $ruanganList = Ruangan::all();

        $barang = Barang::with('ruangan')
            ->when($ruanganId, function ($query, $ruanganId) {
                return $query->where('id_ruangan', $ruanganId);
            })
            ->get();

        return view('admin.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'ruangan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
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
            ]
        );

        Barang::create($validated);
        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan');
    }
    // Tampilkan detail barang 
    public function show(string $id)
    {
        $barang = Barang::with('ruangan')->findOrFail($id);
        return view('admin.barang.show', compact('barang'));
    }
    // Tampilkan form edit barang 
    public function edit(string $id)
    {
        $barang = Barang::findOrFail($id);
        $ruangan = Ruangan::all();
        return view('admin.barang.edit', compact('barang', 'ruangan'));
    }
    // Simpan perubahan barang 
    public function update(Request $request, string $id)
    {
        $validated = $request->validate(
            [
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
            ]
        );

        $barang = Barang::findOrFail($id);
        $barang->update($validated);
        return redirect()->route('barang.index')->with('success', 'Barang berhasil diperbarui');
    }
    // Hapus barang 
    public function destroy(string $id)
    {
        $barang = Barang::findOrFail($id);
        if ($barang->peminjaman()->exists()) {
            return redirect()->back()->with('error', 'Barang sedang dipinjam dan tidak dapat dihapus.');
        }
        $barang->delete();
        return redirect()->route('barang.index')->with('success', 'Barang berhasil dihapus');
    }
    // Export Excel 
    public function export()
    {
        return Excel::download(new BarangExport, 'barang.xlsx');
    }
    // Import Excel 
    public function import(Request $request)
    {
        Log::info('Import Barang dipanggil', ['user' => Auth::user()?->email, 'filename' => $request->file('file')?->getClientOriginalName()]);
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
