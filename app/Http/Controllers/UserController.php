<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LogAktivitas; // Ditambahkan untuk logging
use App\Models\Peminjaman; // Ditambahkan untuk pengecekan status peminjaman
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Untuk logging error sistem
use Illuminate\Validation\Rule; // Untuk validasi role
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Untuk otorisasi
use Illuminate\Support\Facades\DB; // Untuk transaksi database

/**
 * Controller UserController bertanggung jawab untuk manajemen pengguna.
 */
class UserController extends Controller
{
    use AuthorizesRequests; // Mengaktifkan penggunaan otorisasi

    /**
     * Menampilkan daftar semua pengguna.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        // Otorisasi: siapa yang boleh melihat daftar pengguna
        // Ini akan memanggil UserPolicy@viewAny
        $this->authorize('viewAny', User::class);

        $searchTerm = $request->get('search');
        $roleFilter = $request->get('role');
        $statusFilter = $request->get('status', 'aktif'); // Pastikan baris ini ada


        $query = User::query();

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('username', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter === 'arsip') {
            $query->onlyTrashed();
        } elseif ($statusFilter === 'semua') {
            $query->withTrashed();
        }

        $users = $query->orderBy('username')->paginate(15);
        $roles = User::getRoles(); // Mengambil daftar peran dari model User

        // Asumsi view ada di 'admin.users.index'
        // Pastikan path view ini benar dan ada
        return view('admin.users.index', compact('users', 'roles', 'searchTerm', 'roleFilter', 'statusFilter'));
    }

    /**
     * Menampilkan formulir untuk membuat pengguna baru.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        // Otorisasi: siapa yang boleh membuat pengguna
        // Ini akan memanggil UserPolicy@create
        $this->authorize('create', User::class);

        $roles = User::getRoles();
        // Asumsi view ada di 'admin.users.create'
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Menyimpan pengguna yang baru dibuat ke database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Otorisasi: siapa yang boleh membuat pengguna
        $this->authorize('create', User::class);

        $validatedData = $request->validate([
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(User::getRoles())],
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user = User::create([
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'role' => $validatedData['role'],
                'password' => Hash::make($validatedData['password']),
            ]);

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Tambah Pengguna',
                'deskripsi' => "Pengguna baru {$user->username} (Role: {$user->role}) berhasil ditambahkan.",
                'model_terkait' => User::class,
                'id_model_terkait' => $user->id,
                'data_baru' => $user->makeHidden('password')->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error("Gagal menyimpan pengguna baru: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal menambahkan pengguna. Terjadi kesalahan sistem.')->withInput();
        }
    }

    /**
     * Menampilkan detail pengguna yang spesifik.
     *
     * @param \App\Models\User $user (Menggunakan Route Model Binding)
     * @return \Illuminate\View\View
     */
    public function show(User $user): View
    {
        // Otorisasi: siapa yang boleh melihat detail pengguna ini
        // Ini akan memanggil UserPolicy@view
        $this->authorize('view', $user);

        // Asumsi view ada di 'admin.users.show'
        return view('admin.users.show', compact('user'));
    }

    /**
     * Menampilkan formulir untuk mengedit pengguna yang spesifik.
     *
     * @param \App\Models\User $user (Menggunakan Route Model Binding)
     * @return \Illuminate\View\View
     */
    public function edit(User $user): View
    {
        // Otorisasi: siapa yang boleh mengupdate pengguna ini
        // Ini akan memanggil UserPolicy@update
        $this->authorize('update', $user);

        $roles = User::getRoles();
        // Asumsi view ada di 'admin.users.edit'
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user); // Ini sudah memvalidasi sesuai policy

        // Simpan data lama sebelum diupdate untuk logging
        $oldData = $user->makeHidden('password')->toArray();

        // Aturan validasi dasar untuk semua user
        $validationRules = [
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
        ];

        // Hanya admin yang boleh mengubah role
        if (auth()->user()->hasRole(User::ROLE_ADMIN)) {
            $validationRules['role'] = ['required', Rule::in(User::getRoles())];
        }

        $validatedData = $request->validate($validationRules);

        // Data yang akan diupdate
        $updateData = [
            'username' => $validatedData['username'],
            'email' => $validatedData['email'],
        ];

        // Hanya admin yang boleh update role, itupun bukan role sendiri
        if (auth()->user()->hasRole(User::ROLE_ADMIN)) {
            // Blokir jika admin mencoba mengubah role sendiri
            if (auth()->user()->id === $user->id && $validatedData['role'] !== User::ROLE_ADMIN) {
                return back()->with('error', 'Anda tidak bisa mengubah role akun sendiri');
            }

            $updateData['role'] = $validatedData['role'];
        }

        // Update password jika diisi
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validatedData['password']);
        }

        // Di dalam method update, setelah update password
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validatedData['password']);

            // Log khusus perubahan password
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Update Password',
                'deskripsi' => "Password pengguna {$user->username} (ID: {$user->id}) telah direset",
                'model_terkait' => User::class,
                'id_model_terkait' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Simpan perubahan
        try {
            DB::beginTransaction();

            $user->update($updateData);

            // Log activity
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Update Pengguna',
                'deskripsi' => "Data pengguna {$user->username} (ID: {$user->id}) berhasil diperbarui",
                'model_terkait' => User::class,
                'id_model_terkait' => $user->id,
                'data_lama' => $oldData,
                'data_baru' => $user->makeHidden('password')->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('admin.users.index')->with('success', 'Profil berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memperbarui pengguna {$user->id}: " . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            return back()->with('error', 'Gagal memperbarui profil. Terjadi kesalahan sistem.');
        }
    }

    /**
     * Menghapus (soft delete) pengguna yang spesifik dari database.
     *
     * @param \App\Models\User $user (Menggunakan Route Model Binding)
     * @param \Illuminate\Http\Request $request (Untuk logging)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user); // Memanggil UserPolicy@delete

        // UserPolicy@delete sudah menghandle admin tidak bisa menghapus diri sendiri.
        // Pengecekan ini adalah lapisan tambahan.
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Mencegah penghapusan satu-satunya Admin
        if ($user->hasRole(User::ROLE_ADMIN)) {
            if (User::where('role', User::ROLE_ADMIN)->whereNull('deleted_at')->count() <= 1) {
                return redirect()->route('admin.users.index')->with('error', 'Tidak dapat menghapus satu-satunya akun Admin yang aktif.');
            }
        }

        // PENGECEKAN KETERGANTUNGAN AKTIF (BEST PRACTICE)
        // 1. Cek apakah pengguna masih menjadi operator aktif untuk ruangan
        if ($user->ruanganYangDiKelola()->exists()) { //
            return redirect()->route('admin.users.index')
                ->with('error', "Pengguna {$user->username} tidak dapat dihapus karena masih menjadi operator untuk satu atau lebih ruangan. Harap alihkan tanggung jawab ruangan tersebut terlebih dahulu.");
        }

        // 2. Cek apakah pengguna masih menjadi pemegang personal untuk unit barang aktif
        if ($user->barangQrCodesYangDipegang()->whereNull('deleted_at')->exists()) { //
            return redirect()->route('admin.users.index')
                ->with('error', "Pengguna {$user->username} tidak dapat dihapus karena masih menjadi pemegang personal untuk satu atau lebih unit barang aktif. Harap alihkan kepemilikan unit barang tersebut.");
        }

        // 3. (Opsional) Cek apakah pengguna (jika Guru) memiliki peminjaman yang masih aktif
        if (
            $user->hasRole(User::ROLE_GURU) &&
            $user->peminjamanYangDiajukan() //
            ->whereNotIn('status', [Peminjaman::STATUS_SELESAI, Peminjaman::STATUS_DITOLAK, Peminjaman::STATUS_DIBATALKAN]) //
            ->exists()
        ) {
            return redirect()->route('admin.users.index')
                ->with('error', "Pengguna {$user->username} tidak dapat dihapus karena masih memiliki transaksi peminjaman yang aktif.");
        }
        // Anda bisa menambahkan pengecekan lain seperti tugas pemeliharaan atau stok opname yang sedang berjalan.

        try {
            $userDataSebelumDihapus = $user->makeHidden('password')->toArray();
            $usernameDihapus = $user->username;

            // Sebelum soft delete, Anda bisa memilih untuk mengosongkan foreign key
            // Namun, dengan pengecekan di atas, ini mungkin tidak diperlukan lagi jika Anda mencegah delete.
            // Contoh jika tetap ingin mengosongkan (jika pengecekan di atas di-bypass atau untuk kasus lain):
            // foreach($user->ruanganYangDiKelola as $ruangan) {
            // $ruangan->id_operator = null;
            // $ruangan->save();
            // }
            // foreach($user->barangQrCodesYangDipegang as $item) {
            // $item->id_pemegang_personal = null;
            // $item->save();
            // }

            $user->delete(); // Melakukan soft delete

            LogAktivitas::create([ //
                'id_user' => Auth::id(),
                'aktivitas' => 'Hapus Pengguna',
                'deskripsi' => "Pengguna {$usernameDihapus} berhasil dihapus (soft delete).",
                'model_terkait' => User::class,
                'id_model_terkait' => $user->id,
                'data_lama' => $userDataSebelumDihapus,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('admin.users.index')->with('success', "Pengguna {$usernameDihapus} berhasil dihapus (diarsipkan).");
        } catch (\Exception $e) {
            Log::error("Gagal menghapus pengguna {$user->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('admin.users.index')->with('error', 'Gagal menghapus pengguna. Terjadi kesalahan sistem.');
        }
    }

    /**
     * Memulihkan pengguna yang telah di-soft-delete.
     */
    public function restore(Request $request, $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $user);

        try {
            DB::beginTransaction();
            $user->restore();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Pulihkan Pengguna',
                'deskripsi' => "Pengguna {$user->username} (ID: {$user->id}) berhasil dipulihkan.",
                'model_terkait' => User::class,
                'id_model_terkait' => $user->id,
                'data_baru' => $user->makeHidden('password')->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();

            return redirect()->route('admin.users.index', ['status' => 'arsip'])->with('success', "Pengguna {$user->username} berhasil dipulihkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan pengguna {$id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('admin.users.index', ['status' => 'arsip'])->with('error', 'Gagal memulihkan pengguna. Terjadi kesalahan sistem.');
        }
    }

    public function forceDelete(Request $request, $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $user);

        try {
            DB::beginTransaction();
            $oldData = $user->makeHidden('password')->toArray();
            $username = $user->username;

            $user->forceDelete();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Hapus Permanen Pengguna',
                'deskripsi' => "Pengguna {$username} (ID: {$id}) dihapus permanen dari sistem",
                'model_terkait' => User::class,
                'id_model_terkait' => $id,
                'data_lama' => $oldData,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('admin.users.index', ['status' => 'arsip'])
                ->with('success', "Pengguna {$username} berhasil dihapus permanen.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menghapus permanen pengguna {$id}: " . $e->getMessage());
            return back()->with('error', 'Gagal menghapus permanen. Terjadi kesalahan sistem.');
        }
    }
}
