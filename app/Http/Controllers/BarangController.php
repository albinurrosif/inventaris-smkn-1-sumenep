<?php

namespace App\Http\Controllers;
use App\Models\Barang;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use App\Exports\BarangExport;
use App\Imports\BarangImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Validators\ValidationException;



class BarangController extends Controller
{
    public function index(Request $request)
    {
        $ruanganId = $request->get('ruangan_id');

        $ruanganList = Ruangan::all();

        $barang = Barang::with('ruangan')
            ->when($ruanganId, function ($query, $ruanganId) {
                return $query->where('id_ruangan', $ruanganId);
            })
            ->get();

        return view('barang.index', compact('barang', 'ruanganList', 'ruanganId'));
    }

    public function create()
    {
        $ruangan = Ruangan::all();
        return view('barang.create', compact('ruangan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:50',
            'jumlah_barang' => 'required|integer|min:0',
            'id_ruangan' => 'required|exists:ruangan,id',
            'keadaan_barang' => 'required|in:Baik,Kurang Baik,Rusak Berat',
        ]);

        Barang::create($validated);
        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan');
    }

    public function show(string $id)
    {
        $barang = Barang::with('ruangan')->findOrFail($id);
        return view('barang.show', compact('barang'));
    }

    public function edit(string $id)
    {
        $barang = Barang::findOrFail($id);
        $ruangan = Ruangan::all();
        return view('barang.edit', compact('barang', 'ruangan'));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:50',
            'jumlah_barang' => 'required|integer|min:0',
            'id_ruangan' => 'required|exists:ruangan,id',
            'keadaan_barang' => 'required|in:Baik,Kurang Baik,Rusak Berat',
        ]);

        $barang = Barang::findOrFail($id);
        $barang->update($validated);

        return redirect()->route('barang.index')->with('success', 'Barang berhasil diperbarui');
    }

    public function destroy(string $id)
    {
        $barang = Barang::findOrFail($id);
        $barang->delete();

        return redirect()->route('barang.index')->with('success', 'Barang berhasil dihapus');
    }

    public function export()
    {
        return Excel::download(new BarangExport, 'barang.xlsx');
    }

    

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        try {
            Excel::import(new BarangImport, $request->file('file'));
            return redirect()->route('barang.index')->with('success', 'Data berhasil diimpor.');
        } catch (ValidationException $e) {
            $failures = $e->failures();
            return redirect()->back()
                ->withErrors(['import' => 'Terdapat kesalahan pada file Excel.'])
                ->with('failures', $failures);
        }
    }


}


