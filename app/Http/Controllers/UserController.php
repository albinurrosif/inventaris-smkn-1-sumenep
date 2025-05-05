<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // =========================================================================
    // Manajemen Pengguna
    // =========================================================================

    /**
     * Menampilkan daftar pengguna.
     */
    public function index()
    {
        $users = User::all();
        $roles = User::getRoles();
        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Menampilkan formulir untuk membuat pengguna baru.
     */
    public function create()
    {
        $roles = User::getRoles();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Menyimpan pengguna yang baru dibuat.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:' . implode(',', User::getRoles()),
            'password' => 'required|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Menampilkan pengguna yang ditentukan.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Menampilkan formulir untuk mengedit pengguna yang ditentukan.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = User::getRoles();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Memperbarui pengguna yang ditentukan di penyimpanan.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:' . implode(',', User::getRoles()),
            'password' => 'nullable|min:6',
        ]);

        // Mencegah pengguna mengubah peran admin mereka sendiri
        if (Auth::user()->id === $user->id && $request->role !== $user->role) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
        ]);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna yang ditentukan dari penyimpanan.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Mencegah pengguna menghapus akun mereka sendiri
        if (Auth::user()->id === $user->id) {
            return redirect()->route('users.index')->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
