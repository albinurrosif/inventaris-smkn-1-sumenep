<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use App\Models\User;
use App\Models\BarangQrCode; // Jika diperlukan untuk pengecekan lebih lanjut
use App\Models\LogAktivitas;
// use App\Models\BarangStatus; // Tidak digunakan langsung di sini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class RuanganController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ruangan::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $searchTerm = $request->get('search');
        $statusFilter = $request->get('status', 'aktif');

        $query = Ruangan::query(); // Inisialisasi query builder

        // Terapkan filter status SEBELUM with()
        if ($statusFilter === 'arsip') {
            $query->onlyTrashed();
        } elseif ($statusFilter === 'semua') {
            $query->withTrashed();
        }
        // Jika 'aktif', tidak perlu filter tambahan karena model menggunakan SoftDeletes

        // Eager load relasi yang dibutuhkan, termasuk count untuk barang aktif di ruangan
        $query->with(['operator'])
            ->withCount(['barangQrCodes as barang_qr_codes_count' => function ($q_barang) {
                // Jika status filter adalah 'arsip' atau 'semua', kita mungkin ingin menghitung
                // barang yang juga terarsip bersama ruangan, atau hanya barang aktif.
                // Untuk konsistensi, mari kita hitung hanya barang aktif terlepas dari status ruangan.
                // Jika ruangan dipulihkan, barangnya tidak otomatis ikut pulih.
                $q_barang->whereNull('deleted_at');
            }]);

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $query->whereIn('id', $user->ruanganYangDiKelola()->pluck('id'));
        }

        if ($searchTerm) {
            $query->where(function ($q_search) use ($searchTerm) {
                $q_search->where('nama_ruangan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('kode_ruangan', 'like', '%' . $searchTerm . '%');
            });
        }

        $ruangans = $query->orderBy('nama_ruangan')->paginate(15)->withQueryString();
        $operators = User::where('role', User::ROLE_OPERATOR)->orderBy('username')->get();
        // $viewPath = $this->getViewPathBasedOnRole('admin.ruangan.index', 'operator.ruangan.index');

        return view('pages.ruangan.index', compact('ruangans', 'operators', 'request', 'searchTerm', 'statusFilter'));
    }

    public function show($id): View
    {
        $ruangan = Ruangan::withTrashed()->findOrFail($id);
        $this->authorize('view', $ruangan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            if (!$this->isOperatorOfRuangan($user, $ruangan) && !$user->ruanganYangDiKelola()->where('ruangans.id', $ruangan->id)->exists()) {
                abort(403, 'Anda tidak diizinkan untuk melihat detail ruangan ini.');
            }
        }

        $operators = User::where('role', User::ROLE_OPERATOR)->orderBy('username')->get();

        $ruangan->load(['operator']); // Load operator

        // Load barangQrCodes dengan kondisi tertentu
        $unitBarangQuery = $ruangan->barangQrCodes()->whereNull('deleted_at')->with(['barang.kategori', 'pemegangPersonal']);

        // Jika ingin memuat semua barang terkait ruangan (termasuk yang mungkin ter-softdelete bersama ruangan)
        // if ($ruangan->trashed()) {
        //     $unitBarangQuery = $ruangan->barangQrCodes()->withTrashed()->with(['barang.kategori', 'pemegangPersonal']);
        // }

        $unitBarang = $unitBarangQuery->paginate(10);

        $totalUnit = $ruangan->getTotalUnitCount();
        $totalValue = $ruangan->getTotalValue();

        //$viewPath = $this->getViewPathBasedOnRole('admin.ruangan.show', 'operator.ruangan.show');
        return view('pages.ruangan.show', compact('ruangan', 'unitBarang', 'totalUnit', 'totalValue', 'operators'));
    }

    public function create(): View
    {
        $this->authorize('create', Ruangan::class);
        $operators = User::where('role', User::ROLE_OPERATOR)
            ->orderBy('username')
            ->get();
        $viewPath = $this->getViewPathBasedOnRole('admin.ruangan.create', 'operator.ruangan.create');
        return view($viewPath, compact('operators'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Ruangan::class);
        $validated = $request->validate([
            'nama_ruangan' => 'required|string|max:255|unique:ruangans,nama_ruangan',
            'kode_ruangan' => 'required|string|max:50|unique:ruangans,kode_ruangan',
            'id_operator' => 'nullable|exists:users,id',
        ], [], [
            'nama_ruangan' => 'Nama Ruangan',
            'kode_ruangan' => 'Kode Ruangan',
            'id_operator' => 'Operator Penanggung Jawab',
        ]);

        if (!empty($validated['id_operator'])) {
            $operator = User::find($validated['id_operator']);
            if (!$operator || !$operator->hasRole(User::ROLE_OPERATOR)) {
                return back()->withInput()->withErrors(['id_operator' => 'Pengguna yang dipilih bukan merupakan operator.'], 'storeRuanganErrors');
            }
        }

        try {
            DB::beginTransaction();
            $ruangan = Ruangan::create($validated);
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Tambah Ruangan',
                'deskripsi' => "Menambahkan ruangan: {$ruangan->nama_ruangan} (Kode: {$ruangan->kode_ruangan})",
                'model_terkait' => Ruangan::class,
                'id_model_terkait' => $ruangan->id,
                'data_baru' => $ruangan->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('ruangan.index', 'admin.ruangan.index');
            return redirect()->route($redirectRoute)->with('success', 'Ruangan berhasil ditambahkan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator, 'storeRuanganErrors')
                ->withInput()
                ->with('error_form_type', 'create');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating ruangan: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menambahkan ruangan. Terjadi kesalahan.');
        }
    }

    public function edit(Ruangan $ruangan): View
    {
        $this->authorize('update', $ruangan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR) && !$this->isOperatorOfRuangan($user, $ruangan)) {
            abort(403, 'Anda tidak diizinkan mengedit ruangan ini.');
        }
        $operators = User::where('role', User::ROLE_OPERATOR)->orderBy('username')->get();
        $viewPath = $this->getViewPathBasedOnRole('admin.ruangan.edit', 'operator.ruangan.edit');
        return view($viewPath, compact('ruangan', 'operators'));
    }

    public function update(Request $request, Ruangan $ruangan): RedirectResponse
    {
        $this->authorize('update', $ruangan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR) && !$this->isOperatorOfRuangan($user, $ruangan)) {
            abort(403, 'Anda tidak diizinkan memperbarui ruangan ini.');
        }
        $validated = $request->validate([
            'nama_ruangan' => ['required', 'string', 'max:255', Rule::unique('ruangans')->ignore($ruangan->id)],
            'kode_ruangan' => ['required', 'string', 'max:50', Rule::unique('ruangans')->ignore($ruangan->id)],
            'id_operator' => 'nullable|exists:users,id',
        ], [], [
            'nama_ruangan' => 'Nama Ruangan',
            'kode_ruangan' => 'Kode Ruangan',
            'id_operator' => 'Operator Penanggung Jawab',
        ]);

        if (!empty($validated['id_operator'])) {
            $operatorUser = User::find($validated['id_operator']);
            if (!$operatorUser || !$operatorUser->hasRole(User::ROLE_OPERATOR)) {
                return back()->withInput()->withErrors(['id_operator' => 'Pengguna yang dipilih untuk operator bukan merupakan operator.'], 'updateRuanganErrors');
            }
        }

        try {
            DB::beginTransaction();
            $oldData = $ruangan->getOriginal();
            $ruangan->update($validated);
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Update Ruangan',
                'deskripsi' => "Memperbarui ruangan: {$ruangan->nama_ruangan} (Kode: {$ruangan->kode_ruangan})",
                'model_terkait' => Ruangan::class,
                'id_model_terkait' => $ruangan->id,
                'data_lama' => array_intersect_key($oldData, $validated),
                'data_baru' => $ruangan->fresh()->only(array_keys($validated)),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('ruangan.index', 'admin.ruangan.index');
            return redirect()->route($redirectRoute)->with('success', 'Ruangan berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator, 'updateRuanganErrors')
                ->withInput()
                ->with('error_form_type', 'edit')
                ->with('error_ruangan_id', $ruangan->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating ruangan {$ruangan->id}: " . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal memperbarui ruangan. Terjadi kesalahan.');
        }
    }

    public function destroy(Ruangan $ruangan): RedirectResponse
    {
        $this->authorize('delete', $ruangan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR) && !$this->isOperatorOfRuangan($user, $ruangan)) {
            abort(403, 'Anda tidak diizinkan menghapus ruangan ini.');
        }

        if ($ruangan->barangQrCodes()->whereNull('deleted_at')->exists()) {
            $redirectRoute = $this->getRedirectRouteName('ruangan.index', 'admin.ruangan.index');
            return redirect()->route($redirectRoute)
                ->with('error', "Ruangan '{$ruangan->nama_ruangan}' tidak dapat dihapus karena masih terdapat barang aktif di dalamnya. Pindahkan atau arsipkan barang terlebih dahulu.");
        }

        try {
            DB::beginTransaction();
            $dataLama = $ruangan->toArray();
            $namaRuanganDihapus = $ruangan->nama_ruangan;
            $ruangan->delete();
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Hapus Ruangan',
                'deskripsi' => "Menghapus ruangan: {$namaRuanganDihapus} (ID: {$dataLama['id']})",
                'model_terkait' => Ruangan::class,
                'id_model_terkait' => $dataLama['id'],
                'data_lama' => $dataLama,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('ruangan.index', 'admin.ruangan.index');
            return redirect()->route($redirectRoute)->with('success', "Ruangan {$namaRuanganDihapus} berhasil dihapus (diarsipkan).");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting ruangan {$ruangan->id}: " . $e->getMessage());
            $redirectRoute = $this->getRedirectRouteName('ruangan.index', 'admin.ruangan.index');
            return redirect()->route($redirectRoute)
                ->with('error', 'Terjadi kesalahan saat menghapus ruangan.');
        }
    }

    public function restore(Request $request, $id): RedirectResponse
    {
        $ruangan = Ruangan::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $ruangan);

        try {
            DB::beginTransaction();
            $ruangan->restore();
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Pulihkan Ruangan',
                'deskripsi' => "Ruangan '{$ruangan->nama_ruangan}' (ID: {$ruangan->id}) berhasil dipulihkan.",
                'model_terkait' => Ruangan::class,
                'id_model_terkait' => $ruangan->id,
                'data_baru' => $ruangan->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            return redirect()->route($this->getRedirectRouteName('ruangan.index', 'admin.ruangan.index'), ['status' => 'arsip'])
                ->with('success', "Ruangan {$ruangan->nama_ruangan} berhasil dipulihkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan ruangan {$id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route($this->getRedirectRouteName('ruangan.index', 'admin.ruangan.index'), ['status' => 'arsip'])
                ->with('error', 'Gagal memulihkan ruangan. Terjadi kesalahan sistem.');
        }
    }

    public function inventory(Ruangan $ruangan): View
    {
        $this->authorize('view', $ruangan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR) && !$this->isOperatorOfRuangan($user, $ruangan)) {
            abort(403, 'Anda tidak diizinkan melihat inventaris ruangan ini.');
        }
        $items = $ruangan->barangQrCodes()
            ->whereNull('deleted_at')
            ->with(['barang.kategori', 'pemegangPersonal'])
            ->orderBy('kode_inventaris_sekolah')
            ->paginate(20);
        $viewPath = $this->getViewPathBasedOnRole('admin.ruangan.inventory', 'operator.ruangan.inventory', null);
        return view($viewPath, compact('ruangan', 'items'));
    }

    public function addItem(Request $request, Ruangan $ruangan): RedirectResponse
    {
        $this->authorize('update', $ruangan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR) && !$this->isOperatorOfRuangan($user, $ruangan)) {
            return back()->with('error', 'Anda tidak diizinkan menambah barang ke ruangan ini.');
        }
        $validated = $request->validate(['id_barang_qr_code' => 'required|exists:barang_qr_codes,id']);
        try {
            DB::beginTransaction();
            $item = BarangQrCode::find($validated['id_barang_qr_code']);
            if (!$item) {
                return back()->with('error', 'Unit barang tidak ditemukan.');
            }
            if ($item->id_ruangan == $ruangan->id) {
                return back()->with('info', "Barang {$item->kode_inventaris_sekolah} sudah berada di ruangan {$ruangan->nama_ruangan}.");
            }
            $ruanganAsalSebelumnya = $item->ruangan ? $item->ruangan->nama_ruangan : ($item->pemegangPersonal ? 'Pemegang: ' . $item->pemegangPersonal->username : 'Tidak Diketahui');
            $dataLamaItem = $item->getAttributes();
            $item->id_ruangan = $ruangan->id;
            $item->id_pemegang_personal = null;
            $item->save();
            // LogAktivitas::create([ /* ... */ ]); // Disingkat untuk brevity
            // BarangStatus::create([ /* ... */ ]); // Disingkat untuk brevity
            DB::commit();
            return back()->with('success', "Barang {$item->kode_inventaris_sekolah} berhasil ditambahkan/dipindahkan ke ruangan {$ruangan->nama_ruangan}.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error adding item to room {$ruangan->id}: " . $e->getMessage());
            return back()->with('error', 'Gagal menambahkan barang ke ruangan. Terjadi kesalahan.');
        }
    }

    public function removeItem(Request $request, Ruangan $ruangan, BarangQrCode $item): RedirectResponse
    {
        $this->authorize('update', $ruangan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR) && !$this->isOperatorOfRuangan($user, $ruangan)) {
            return back()->with('error', 'Anda tidak diizinkan mengeluarkan barang dari ruangan ini.');
        }
        if ($item->id_ruangan != $ruangan->id) {
            return back()->with('error', 'Barang tidak berada di ruangan ini atau sudah dipindahkan.');
        }
        Log::warning("Fungsi removeItem di RuanganController dipanggil. Seharusnya ini di-handle oleh fitur Mutasi, Serah Terima Personal, atau Arsip. Ruangan: {$ruangan->id}, Item: {$item->id}");
        return back()->with('error', 'Fungsi ini sedang ditinjau. Untuk mengeluarkan barang, gunakan fitur Mutasi, Serah Terima ke Personal, atau Arsip Barang.');
    }

    private function isOperatorOfRuangan(User $user, Ruangan $ruangan): bool
    {
        return $user->ruanganYangDiKelola()->where('ruangans.id', $ruangan->id)->exists();
    }

    private function getViewPathBasedOnRole(string $adminView, string $operatorView, ?string $guruView = null): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return $adminView;
        $viewPath = $adminView;
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $viewPath = view()->exists($operatorView) ? $operatorView : $adminView;
        } elseif ($user->hasRole(User::ROLE_GURU) && $guruView) {
            $viewPath = view()->exists($guruView) ? $guruView : $adminView;
        }
        if (!view()->exists($viewPath)) {
            Log::warning("View path tidak ditemukan: Target='{$viewPath}', Fallback ke Admin='{$adminView}'.");
            if (!view()->exists($adminView)) {
                Log::critical("View utama admin tidak ditemukan: {$adminView}. Harap periksa konfigurasi view paths.");
                abort(500, "View utama {$adminView} tidak ditemukan.");
            }
            return $adminView;
        }
        return $viewPath;
    }

    private function getRedirectRouteName(string $baseRouteName, string $adminFallbackRouteName): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return $adminFallbackRouteName;
        $rolePrefix = '';
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $rolePrefix = 'admin.';
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            $rolePrefix = 'operator.';
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            $rolePrefix = 'guru.';
        }
        if (!empty($rolePrefix) && Route::has($rolePrefix . $baseRouteName)) {
            return $rolePrefix . $baseRouteName;
        }
        if (Route::has($baseRouteName)) {
            return $baseRouteName;
        }
        return Route::has($adminFallbackRouteName) ? $adminFallbackRouteName : $baseRouteName;
    }
}
