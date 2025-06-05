<?php

namespace App\Http\Controllers;

use App\Models\KategoriBarang;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class KategoriBarangController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', KategoriBarang::class);

        $searchTerm = $request->get('search');
        $statusFilter = $request->get('status', 'aktif');

        $query = KategoriBarang::query();

        if ($statusFilter === 'arsip') {
            $query->onlyTrashed();
        } elseif ($statusFilter === 'semua') {
            $query->withTrashed();
        }

        // Hanya hitung jenis barang (Barang) yang aktif untuk jumlah_item_induk
        $query->withCount(['barangs as jumlah_item_induk' => function ($q_barang) {
            $q_barang->whereNull('deleted_at');
        }]);

        if ($searchTerm) {
            $query->where(function ($q_search) use ($searchTerm) {
                $q_search->where('nama_kategori', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }

        $kategoriBarangList = $query->orderBy('nama_kategori', 'asc')
            ->paginate(10)->withQueryString(); // withQueryString() agar filter terbawa saat paginasi

        // Data agregat tetap dihitung berdasarkan status kategori yang ditampilkan
        foreach ($kategoriBarangList as $kategori) {
            // Untuk akurasi, metode di model harus menghormati status 'trashed' jika diperlukan
            $kategori->agregat_total_unit = $kategori->getTotalUnitCount(); // Jumlah total unit dari barang aktif di kategori ini
            $kategori->agregat_unit_tersedia = $kategori->getAvailableUnitCount(); // Jumlah unit tersedia dari barang aktif
            $kategori->agregat_nilai_total = $kategori->getTotalValue(); // Nilai total dari barang aktif
        }

        $viewPath = 'admin.kategori.index';
        return view($viewPath, [
            'kategoriBarangList' => $kategoriBarangList,
            'searchTerm' => $searchTerm,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', KategoriBarang::class);
        return view('admin.kategori.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', KategoriBarang::class);
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori_barangs,nama_kategori',
        ], [], [
            'nama_kategori' => 'Nama Kategori' // Custom attribute name untuk pesan validasi
        ]);

        try {
            DB::beginTransaction();
            $kategoriBarang = KategoriBarang::create($validated);
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Tambah Kategori Barang',
                'deskripsi' => "Menambahkan kategori: {$kategoriBarang->nama_kategori} (ID: {$kategoriBarang->id})",
                'model_terkait' => KategoriBarang::class,
                'id_model_terkait' => $kategoriBarang->id,
                'data_baru' => $kategoriBarang->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            return redirect()->route($this->getRedirectRouteName('kategori-barang.index', 'admin.kategori-barang.index'))
                ->with('success', 'Kategori barang berhasil ditambahkan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator, 'storeKategoriErrors') // Error bag spesifik
                ->withInput()
                ->with('error_form_type', 'create'); // Flag untuk JS
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan kategori barang: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal menambahkan kategori barang. Terjadi kesalahan sistem.')->withInput();
        }
    }

    public function show($id): View // Menggunakan ID agar bisa fetch withTrashed
    {
        $kategoriBarang = KategoriBarang::withTrashed()->findOrFail($id);
        $this->authorize('view', $kategoriBarang);

        $kategoriBarang->jumlah_item_induk = $kategoriBarang->getItemCount(true); // true untuk menghitung item aktif saja
        $kategoriBarang->jumlah_unit_total = $kategoriBarang->getTotalUnitCount(true); // true untuk unit dari item aktif
        $kategoriBarang->jumlah_unit_tersedia = $kategoriBarang->getAvailableUnitCount(true); // true untuk unit tersedia dari item aktif
        $kategoriBarang->nilai_total_estimasi = $kategoriBarang->getTotalValue(true); // true untuk nilai dari item aktif

        // Load barang (induk) yang aktif saja
        $kategoriBarang->load(['barangs' => function ($query) {
            $query->whereNull('deleted_at')
                ->withCount(['qrCodes as jumlah_unit_aktif_per_barang' => function ($q_qr) {
                    $q_qr->whereNull('deleted_at');
                }])->orderBy('nama_barang');
        }]);
        return view('admin.kategori.show', compact('kategoriBarang'));
    }

    public function edit(KategoriBarang $kategoriBarang): View
    {
        $this->authorize('update', $kategoriBarang);
        return view('admin.kategori.edit', compact('kategoriBarang'));
    }

    public function update(Request $request, KategoriBarang $kategoriBarang): RedirectResponse
    {
        $this->authorize('update', $kategoriBarang);
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:255', Rule::unique('kategori_barangs')->ignore($kategoriBarang->id)],
        ], [], ['nama_kategori' => 'Nama Kategori']);

        try {
            DB::beginTransaction();
            $dataLama = $kategoriBarang->getOriginal();
            $kategoriBarang->update($validated);
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Update Kategori Barang',
                'deskripsi' => "Memperbarui kategori: {$kategoriBarang->nama_kategori} (ID: {$kategoriBarang->id})",
                'model_terkait' => KategoriBarang::class,
                'id_model_terkait' => $kategoriBarang->id,
                'data_lama' => array_intersect_key($dataLama, $validated),
                'data_baru' => $kategoriBarang->fresh()->only(array_keys($validated)),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            return redirect()->route($this->getRedirectRouteName('kategori-barang.index', 'admin.kategori-barang.index'))
                ->with('success', 'Kategori barang berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator, 'updateKategoriErrors')
                ->withInput()
                ->with('error_form_type', 'edit')
                ->with('error_kategori_id', $kategoriBarang->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memperbarui kategori barang {$kategoriBarang->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal memperbarui kategori barang. Terjadi kesalahan sistem.')->withInput();
        }
    }

    public function destroy(KategoriBarang $kategoriBarang): RedirectResponse
    {
        $this->authorize('delete', $kategoriBarang);
        if ($kategoriBarang->barangs()->whereNull('deleted_at')->exists()) {
            return redirect()->route($this->getRedirectRouteName('kategori-barang.index', 'admin.kategori-barang.index'))
                ->with('error', "Kategori {$kategoriBarang->nama_kategori} tidak dapat diarsipkan karena masih digunakan oleh beberapa jenis barang aktif. Harap pindahkan atau arsipkan jenis barang tersebut terlebih dahulu.");
        }

        try {
            DB::beginTransaction();
            $namaKategoriDihapus = $kategoriBarang->nama_kategori;
            $idKategoriDihapus = $kategoriBarang->id;
            $dataLama = $kategoriBarang->toArray();
            $kategoriBarang->delete(); // Ini akan memicu event 'deleting' untuk soft delete Barang terkait
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Arsipkan Kategori Barang',
                'deskripsi' => "Mengarsipkan kategori: {$namaKategoriDihapus} (ID: {$idKategoriDihapus})",
                'model_terkait' => KategoriBarang::class,
                'id_model_terkait' => $idKategoriDihapus,
                'data_lama' => $dataLama,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            DB::commit();
            return redirect()->route($this->getRedirectRouteName('kategori-barang.index', 'admin.kategori-barang.index'))
                ->with('success', "Kategori barang {$namaKategoriDihapus} berhasil diarsipkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal mengarsipkan kategori barang {$kategoriBarang->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route($this->getRedirectRouteName('kategori-barang.index', 'admin.kategori-barang.index'))
                ->with('error', 'Gagal mengarsipkan kategori barang. Terjadi kesalahan sistem.');
        }
    }

    public function restore(Request $request, $id): RedirectResponse
    {
        $kategoriBarang = KategoriBarang::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $kategoriBarang);

        try {
            DB::beginTransaction();
            $kategoriBarang->restore(); // Ini akan memicu event 'restoring' di model KategoriBarang & Barang

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Pulihkan Kategori Barang',
                'deskripsi' => "Kategori barang '{$kategoriBarang->nama_kategori}' (ID: {$kategoriBarang->id}) berhasil dipulihkan.",
                'model_terkait' => KategoriBarang::class,
                'id_model_terkait' => $kategoriBarang->id,
                'data_baru' => $kategoriBarang->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();

            return redirect()->route($this->getRedirectRouteName('kategori-barang.index', 'admin.kategori-barang.index'), ['status' => 'arsip'])
                ->with('success', "Kategori barang {$kategoriBarang->nama_kategori} berhasil dipulihkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan kategori barang {$id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route($this->getRedirectRouteName('kategori-barang.index', 'admin.kategori-barang.index'), ['status' => 'arsip'])
                ->with('error', 'Gagal memulihkan kategori barang. Terjadi kesalahan sistem.');
        }
    }

    public function getItems(KategoriBarang $kategoriBarang): JsonResponse
    {
        $this->authorize('view', $kategoriBarang);
        $barangList = $kategoriBarang->barangs()
            ->whereNull('deleted_at')
            ->select('id', 'nama_barang', 'kode_barang')
            ->orderBy('nama_barang')
            ->get();
        return response()->json(['status' => 'success', 'data' => $barangList]);
    }

    public function getStatistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KategoriBarang::class);
        $kategoris = KategoriBarang::whereNull('deleted_at')->orderBy('nama_kategori')->get();
        $stats = $kategoris->map(function ($kategori) {
            return [
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
                'slug' => $kategori->slug,
                'jumlah_item_induk' => $kategori->getItemCount(true), // Hanya item aktif
                'jumlah_unit_total' => $kategori->getTotalUnitCount(true), // Hanya unit dari item aktif
                'jumlah_unit_tersedia' => $kategori->getAvailableUnitCount(true), // Hanya unit tersedia dari item aktif
                'nilai_total_estimasi' => (float) $kategori->getTotalValue(true) // Hanya nilai dari item aktif
            ];
        });
        return response()->json(['status' => 'success', 'data' => $stats]);
    }

    private function getRedirectRouteName(string $baseRouteName, string $adminFallbackRouteName): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return $adminFallbackRouteName;
        $rolePrefix = '';
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $rolePrefix = 'admin.';
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
