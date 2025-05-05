<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Http\Request;

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
        $request->validate([
            'nama_ruangan' => 'required|string|max:100',
            'id_operator' => 'nullable|exists:users,id',
        ]);

        Ruangan::create($request->all());

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
        $request->validate([
            'nama_ruangan' => 'required|string|max:100',
            'id_operator' => 'nullable|exists:users,id',
        ]);

        $ruangan = Ruangan::findOrFail($id);
        $ruangan->update($request->all());

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
