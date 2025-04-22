<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Http\Request;

class RuanganController extends Controller
{
    public function index()
    {
        $ruangan = Ruangan::with('operator')->get();
        $operators = \App\Models\User::where('role', 'operator')->get(); // tambahkan ini
        return view('ruangan.index', compact('ruangan', 'operators'));
    }


    public function create()
    {
        $operators = User::where('role', 'operator')->get(); // asumsikan user punya role
        return view('ruangan.create', compact('operators'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|max:100',
            'id_operator' => 'nullable|exists:users,id',
        ]);

        Ruangan::create($request->all());

        return redirect()->route('ruangan.index')->with('success', 'Ruangan berhasil ditambahkan');
    }

    public function edit($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $operators = User::where('role', 'operator')->get();
        return view('ruangan.edit', compact('ruangan', 'operators'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|max:100',
            'id_operator' => 'nullable|exists:users,id',
        ]);

        $ruangan = Ruangan::findOrFail($id);
        $ruangan->update($request->all());

        return redirect()->route('ruangan.index')->with('success', 'Ruangan berhasil diupdate');
    }

    public function destroy($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $ruangan->delete();

        return redirect()->route('ruangan.index')->with('success', 'Ruangan berhasil dihapus');
    }
    
}
