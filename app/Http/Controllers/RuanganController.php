<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // Import Validator class

class RuanganController extends Controller
{
    // =========================================================================
    // Manajemen Ruangan
    // =========================================================================

    /**
     * Menampilkan daftar.
     */
    public function index()
    {
        $ruangan = Ruangan::with('operator')->get();
        $operators = User::where('role', 'operator')->get();
        return view('admin.ruangan.index', compact('ruangan', 'operators'));
    }

    public function show($id)
    {
        $ruangan = Ruangan::with('operator')->findOrFail($id);
        $operators = User::where('role', 'operator')->get(); // Add this line

        // Barang-barang di ruangan ini
        $barangList = $ruangan->barang()->withCount(['qrCodes', 'qrCodes as total_unit' => function ($q) {
            $q->whereNull('deleted_at');
        }])->get();

        $totalUnit = $barangList->sum('total_unit');
        $itemCount = $barangList->count();
        $totalValue = $barangList->sum(function ($barang) {
            return $barang->harga_beli * $barang->total_unit;
        });

        return view('admin.ruangan.show', compact('ruangan', 'barangList', 'totalUnit', 'itemCount', 'totalValue', 'operators'));
    }


    /**
     * Menampilkan form untuk membuat.
     */
    public function create()
    {
        $operators = User::where('role', 'operator')->get();
        return view('admin.ruangan.create', compact('operators'));
    }

    /**
     * Menyimpan
     */
    public function store(Request $request)
    {
        // Use Validator::make() for validation
        $validator = Validator::make($request->all(), [
            'nama_ruangan' => 'required|string|max:100',
            'id_operator' => 'nullable|exists:users,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return redirect()->route('ruangan.index')->with('error', 'Gagal menambahkan ruangan. Periksa kembali input anda.'); // More informative message
        }

        // Create the Ruangan object.
        $ruangan = new Ruangan();
        $ruangan->nama_ruangan = $request->input('nama_ruangan');
        $ruangan->id_operator = $request->input('id_operator'); // Use $request->input()
        $ruangan->save();

        return redirect()->route('ruangan.index')->with('success', 'Ruangan berhasil ditambahkan');
    }

    /**
     * Menampilkan form untuk mengedit
     */
    public function edit($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $operators = User::where('role', 'operator')->get();
        return view('admin.ruangan.edit', compact('ruangan', 'operators'));
    }

    /**
     * Memperbarui
     */
    public function update(Request $request, $id)
    {
        // Use Validator::make() for validation
        $validator = Validator::make($request->all(), [
            'nama_ruangan' => 'required|string|max:100',
            'id_operator' => 'nullable|exists:users,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return redirect()->route('ruangan.index')->with('error', 'Gagal memperbarui ruangan.'); // More informative message.
        }

        $ruangan = Ruangan::findOrFail($id);
        $ruangan->nama_ruangan = $request->input('nama_ruangan');
        $ruangan->id_operator = $request->input('id_operator'); // Use $request->input() here too.
        $ruangan->save();

        return redirect()->route('ruangan.index')->with('success', 'Ruangan berhasil diperbarui');
    }

    /**
     * Menghapus
     */
    public function destroy($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $ruangan->delete();

        return redirect()->route('ruangan.index')->with('success', 'Ruangan berhasil dihapus');
    }
}
