<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use App\Models\User;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\PeminjamanStoreRequest;
use App\Http\Requests\PeminjamanUpdateRequest;
use Carbon\Carbon;

class PeminjamanController extends Controller
{
    use AuthorizesRequests;

    /**
     * PERUBAHAN: Menyesuaikan helper agar merujuk ke 'pages' untuk view bersama.
     * View yang spesifik per peran (seperti form 'create' untuk Guru) tetap bisa diarahkan ke path khusus.
     */
    private function getViewPathBasedOnRole(string $sharedViewPath, ?string $roleSpecificView = null): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        // Jika ada view yang spesifik untuk peran ini dan file-nya ada, gunakan itu.
        if ($roleSpecificView && view()->exists($roleSpecificView)) {
            return $roleSpecificView;
        }

        // Jika tidak, gunakan path view bersama yang ada di 'pages'.
        if (view()->exists($sharedViewPath)) {
            return $sharedViewPath;
        }

        // Fallback jika view bersama juga tidak ditemukan (error-handling).
        Log::critical("View Peminjaman tidak ditemukan di path yang ditentukan: {$sharedViewPath}");
        abort(500, "Konfigurasi view tidak ditemukan untuk Peminjaman.");
    }

    /**
     * PENYEMPURNAAN FINAL: Method ini sekarang mengembalikan URL absolut, bukan nama route.
     * Ini membuatnya lebih tahan terhadap masalah konfigurasi redirect.
     *
     * @param string $baseUri Bagian URI dari route (misal: 'peminjaman' atau 'peminjaman/1')
     * @return string URL tujuan yang lengkap.
     */
    private function getRedirectUrl(string $baseUri): string
    {
        $user = Auth::user();
        if (!$user) {
            // Fallback jika tidak ada user, arahkan ke login
            return url('/login');
        }

        $rolePrefix = '';
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $rolePrefix = 'admin';
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            $rolePrefix = 'operator';
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            $rolePrefix = 'guru';
        }

        // Jika ada prefix peran, gabungkan. Jika tidak, gunakan baseUri saja.
        $fullUri = $rolePrefix ? "{$rolePrefix}/{$baseUri}" : $baseUri;

        // Gunakan helper url() untuk membuat URL yang lengkap
        return url($fullUri);
    }



    public function index(Request $request): View
    {
        $this->authorize('viewAny', Peminjaman::class); // [cite: 2222]
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $query = Peminjaman::with([
            'guru',
            'disetujuiOlehUser',
            'ditolakOlehUser',
            'ruanganTujuanPeminjaman',
            'detailPeminjaman.barangQrCode.barang',
            'detailPeminjaman.barangQrCode.ruangan'
        ])->withCount('detailPeminjaman');

        $searchTerm = $request->input('search');
        $statusFilter = $request->input('status');
        $guruFilter = $request->input('id_guru');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $statusArsipFilter = $request->input('status_arsip', 'aktif'); // [cite: 2225]

        if ($statusArsipFilter === 'arsip') {
            $query->onlyTrashed(); // [cite: 2226]
        } elseif ($statusArsipFilter === 'semua') {
            $query->withTrashed(); // [cite: 2227]
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tujuan_peminjaman', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('guru', fn($ug) => $ug->where('username', 'LIKE', "%{$searchTerm}%"))
                    ->orWhereHas('detailPeminjaman.barangQrCode', fn($ub) => $ub->where('kode_inventaris_sekolah', 'LIKE', "%{$searchTerm}%"))->orWhereHas('detailPeminjaman.barangQrCode.barang', fn($ubm) => $ubm->where('nama_barang', 'LIKE', "%{$searchTerm}%")); // [cite: 2228, 2229]
            });
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter); // [cite: 2230]
        }
        if ($guruFilter && $user->hasRole(User::ROLE_ADMIN)) {
            $query->where('id_guru', $guruFilter); // [cite: 2231]
        }
        if ($tanggalMulai) {
            $query->whereDate('tanggal_pengajuan', '>=', $tanggalMulai); // [cite: 2232]
        }
        if ($tanggalSelesai) {
            $query->whereDate('tanggal_pengajuan', '<=', $tanggalSelesai); // [cite: 2233]
        }

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id'); // [cite: 2234]
            $query->where(function ($q) use ($ruanganOperatorIds) {
                $q->whereHas('detailPeminjaman.barangQrCode', function ($qDetail) use ($ruanganOperatorIds) {
                    $qDetail->whereIn('id_ruangan', $ruanganOperatorIds);
                })
                    ->orWhereIn('id_ruangan_tujuan_peminjaman', $ruanganOperatorIds); // [cite: 2235]
            });
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            $query->where('id_guru', $user->id); // [cite: 2236]
        }

        $peminjamanList = $query->latest('tanggal_pengajuan')->latest('id')->paginate(15)->withQueryString();
        $statusList = Peminjaman::getValidStatuses(); // [cite: 2237]
        $guruList = $user->hasRole(User::ROLE_ADMIN) ? User::where('role', User::ROLE_GURU)->orderBy('username')->get() : collect(); // [cite: 2237]
        $ruanganListAll = Ruangan::orderBy('nama_ruangan')->get(); // [cite: 2238]

        // PERUBAHAN: Mengarahkan ke path view bersama di 'pages'
        return view('pages.peminjaman.index', compact(
            'peminjamanList',
            'statusList',
            'guruList',
            'request',
            'statusArsipFilter',
            'ruanganListAll'
        ));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Peminjaman::class);

        $keranjangIds = session()->get('keranjang_peminjaman', []);

        if (empty($keranjangIds)) {
            return redirect()->route('guru.katalog.index')->with('info', 'Keranjang peminjaman Anda kosong. Silakan pilih barang terlebih dahulu.');
        }

        // INTI PERBAIKAN:
        // Query ini HANYA mengambil berdasarkan ID di keranjang.
        // Tidak ada filter status, kondisi, atau apapun. Tujuannya adalah menampilkan isi keranjang apa adanya.
        $itemsDiKeranjang = BarangQrCode::with(['barang', 'ruangan'])
            ->whereIn('id', $keranjangIds)
            ->get();

        // PENYEMPURNAAN: Cek ketersediaan setiap item untuk ditampilkan di view
        foreach ($itemsDiKeranjang as $item) {
            // Cek apakah item masih bisa dipinjam saat ini
            $isAvailable = $item->status === \App\Models\BarangQrCode::STATUS_TERSEDIA &&
                !$item->deleted_at &&
                !in_array($item->kondisi, [\App\Models\BarangQrCode::KONDISI_RUSAK_BERAT, \App\Models\BarangQrCode::KONDISI_HILANG]);

            $item->is_available_for_loan = $isAvailable;
        }

        $ruanganPertama = $itemsDiKeranjang->isNotEmpty() ? $itemsDiKeranjang->first()->ruangan : null;
        $semuaSatuRuangan = $itemsDiKeranjang->every(fn($item) => $item->id_ruangan === optional($ruanganPertama)->id);

        $ruanganTujuanList = Ruangan::whereNull('deleted_at')->orderBy('nama_ruangan')->get();

        return view('pages.peminjaman.create', compact(
            'itemsDiKeranjang',
            'ruanganTujuanList',
            'ruanganPertama',
            'semuaSatuRuangan'
        ));
    }

    /**
     * Method untuk menangani pencarian barang via AJAX untuk Select2.
     */
    public function searchAvailableItems(Request $request): JsonResponse
    {
        $term = $request->input('q', '');
        $ruanganId = $request->input('ruangan_id');

        $itemsQuery = BarangQrCode::with(['barang', 'ruangan'])
            ->where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNull('deleted_at')
            ->whereNotNull('id_ruangan') // Pastikan hanya barang yang punya ruangan yang muncul
            ->whereNull('id_pemegang_personal');

        $itemsQuery->whereDoesntHave('peminjamanDetails', function ($q) {
            $q->whereHas('peminjaman', function ($qPeminjaman) {
                $qPeminjaman->whereNotIn('status', [
                    Peminjaman::STATUS_SELESAI,
                    Peminjaman::STATUS_DITOLAK,
                    Peminjaman::STATUS_DIBATALKAN,
                ]);
            });
        });

        if ($ruanganId) {
            $itemsQuery->where('id_ruangan', $ruanganId);
        }

        if (!empty($term)) {
            $itemsQuery->whereHas('barang', function ($q) use ($term) {
                $q->where('nama_barang', 'LIKE', "%{$term}%");
            });
        }

        $items = $itemsQuery->limit(20)->get();

        $results = $items->map(function ($item) {
            $namaRuangan = optional($item->ruangan)->nama_ruangan ?? 'N/A';
            return [
                'id' => $item->id,
                'text' => "{$item->barang->nama_barang} ({$item->kode_inventaris_sekolah}) - Lokasi: {$namaRuangan}",
                'ruangan_id' => $item->id_ruangan,
                'ruangan_nama' => $namaRuangan, // Kirim juga nama ruangan
            ];
        });

        return response()->json(['results' => $results]);
    }


    public function store(PeminjamanStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Peminjaman::class); // [cite: 2249]
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::create([
                'id_guru' => $user->id,
                'tujuan_peminjaman' => $validated['tujuan_peminjaman'],
                'tanggal_pengajuan' => now(),
                'tanggal_rencana_pinjam' => $validated['tanggal_rencana_pinjam'],
                'tanggal_harus_kembali' => $validated['tanggal_harus_kembali'],
                'catatan_peminjam' => $validated['catatan_peminjam'] ?? null,
                'id_ruangan_tujuan_peminjaman' => $validated['id_ruangan_tujuan_peminjaman'] ?? null,
                'status' => Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
            ]); // [cite: 2252, 2253]

            foreach ($validated['id_barang_qr_code'] as $barangQrId) {
                $barangQr = BarangQrCode::find($barangQrId); // [cite: 2254]
                if (
                    $barangQr && $barangQr->status === BarangQrCode::STATUS_TERSEDIA && !$barangQr->deleted_at &&
                    !in_array($barangQr->kondisi, [BarangQrCode::KONDISI_RUSAK_BERAT, BarangQrCode::KONDISI_HILANG])
                ) { // [cite: 2255]

                    $existingDetail = DetailPeminjaman::where('id_barang_qr_code', $barangQrId)
                        ->whereHas('peminjaman', function ($qPeminjaman) {
                            $qPeminjaman->whereNotIn('status', [Peminjaman::STATUS_SELESAI, Peminjaman::STATUS_DITOLAK, Peminjaman::STATUS_DIBATALKAN]);
                        })->exists(); // [cite: 2256]

                    if ($existingDetail) {
                        DB::rollBack(); // [cite: 2257]
                        return redirect()->back()
                            ->with('error', "Barang '{$barangQr->barang->nama_barang} ({$barangQr->kode_inventaris_sekolah})' sudah dalam pengajuan atau sedang dipinjam.")
                            ->withInput(); // [cite: 2258]
                    }

                    DetailPeminjaman::create([
                        'id_peminjaman' => $peminjaman->id,
                        'id_barang_qr_code' => $barangQrId,
                        'kondisi_sebelum' => $barangQr->kondisi,
                        'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                    ]); // [cite: 2259, 2260]
                } else {
                    DB::rollBack(); // [cite: 2261]
                    $namaBarangError = $barangQr ? ($barangQr->barang->nama_barang . ' (' . $barangQr->kode_inventaris_sekolah . ')') : "ID: " . $barangQrId; // [cite: 2262]
                    return redirect()->back()
                        ->with('error', "Barang '{$namaBarangError}' tidak tersedia, rusak berat, hilang, atau tidak valid untuk dipinjam.")
                        ->withInput(); // [cite: 2263]
                }
            }

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Buat Pengajuan Peminjaman',
                'deskripsi' => "Membuat pengajuan peminjaman ID: {$peminjaman->id} untuk '{$peminjaman->tujuan_peminjaman}'",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_baru' => $peminjaman->load('detailPeminjaman')->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]); // [cite: 2264, 2265]


            $request->session()->forget('keranjang_peminjaman');

            DB::commit(); // [cite: 2266]


            $redirectUrl = $this->getRedirectUrl('peminjaman');
            return redirect($redirectUrl)->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack(); // [cite: 2267]
            Log::error("Gagal menyimpan pengajuan peminjaman: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]); // [cite: 2268]
            return redirect()->back()->with('error', 'Gagal mengirim pengajuan: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.'))->withInput(); // [cite: 2269]
        }
    }

    public function show(Peminjaman $peminjaman): View
    {
        $this->authorize('view', $peminjaman); // [cite: 2270]
        $peminjaman->load([
            'guru',
            'disetujuiOlehUser',
            'ditolakOlehUser',
            'ruanganTujuanPeminjaman',
            'detailPeminjaman.barangQrCode.barang',
            'detailPeminjaman.barangQrCode.ruangan',
            'detailPeminjaman.barangQrCode.pemegangPersonal'
        ]); // [cite: 2271, 2272]
        $kondisiList = BarangQrCode::getValidKondisi(); // [cite: 2272]

        // PERUBAHAN: Mengarahkan ke path view bersama di 'pages'
        return view('pages.peminjaman.show', compact('peminjaman', 'kondisiList'));
    }

    public function edit(Peminjaman $peminjaman): View|RedirectResponse
    {
        $this->authorize('update', $peminjaman); // [cite: 2274]

        $barangList = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereIn('kondisi', [BarangQrCode::KONDISI_BAIK, BarangQrCode::KONDISI_KURANG_BAIK])
            ->whereNull('deleted_at')
            ->with('barang', 'ruangan', 'pemegangPersonal')
            ->orderBy('id_barang')
            ->get()
            ->map(function ($item) {
                $namaRuangan = $item->ruangan ? $item->ruangan->nama_ruangan : ($item->id_pemegang_personal ? 'Pemegang: ' . optional($item->pemegangPersonal)->username : 'Tidak di Ruangan'); // [cite: 2276]
                return [
                    'id' => $item->id,
                    'text' => $item->barang->nama_barang . ' (' . $item->kode_inventaris_sekolah . ($item->no_seri_pabrik ? ' / SN: ' . $item->no_seri_pabrik : '') . ') - Kondisi: ' . $item->kondisi . ' - Lokasi: ' . $namaRuangan
                ]; // [cite: 2277]
            });

        $currentPeminjamanItemIds = $peminjaman->detailPeminjaman->pluck('id_barang_qr_code'); // [cite: 2278]
        $currentPeminjamanItems = BarangQrCode::whereIn('id', $currentPeminjamanItemIds)
            ->with('barang', 'ruangan', 'pemegangPersonal')
            ->get()
            ->map(function ($item) {
                $namaRuangan = $item->ruangan ? $item->ruangan->nama_ruangan : ($item->id_pemegang_personal ? 'Pemegang: ' . optional($item->pemegangPersonal)->username : 'Tidak di Ruangan');
                return [
                    'id' => $item->id,
                    'text' => $item->barang->nama_barang . ' (' . $item->kode_inventaris_sekolah . ($item->no_seri_pabrik ? ' / SN: ' . $item->no_seri_pabrik : '') . ') - Kondisi: ' . $item->kondisi . ' - Lokasi: ' . $namaRuangan
                ]; // [cite: 2279, 2280]
            });

        $barangList = $barangList->concat($currentPeminjamanItems)->unique('id')->sortBy('text'); // [cite: 2281]


        $selectedBarangIds = $peminjaman->detailPeminjaman->pluck('id_barang_qr_code')->toArray(); // [cite: 2281]
        $ruanganTujuanList = Ruangan::whereNull('deleted_at')->orderBy('nama_ruangan')->get(); // [cite: 2282]

        // PERUBAHAN: Menentukan path view
        $viewPath = $this->getViewPathBasedOnRole(
            'pages.peminjaman.edit', // Path bersama
            Auth::user()->hasRole(User::ROLE_OPERATOR) ? 'pages.peminjaman.edit-catatan' : null // Path spesifik jika ada
        );

        if (Auth::user()->hasRole(User::ROLE_OPERATOR) && !view()->exists($viewPath)) {
            // Jika view spesifik Operator tidak ada, arahkan ke show dengan warning
            $redirectUrl = $this->getRedirectUrl("peminjaman/{$peminjaman->id}");
            return redirect($redirectUrl)->with('warning', 'anda tidak memiliki form khusus untuk edit');
        }

        return view('pages.peminjaman.edit', compact('peminjaman', 'barangList', 'selectedBarangIds', 'ruanganTujuanList'));
    }

    public function update(PeminjamanUpdateRequest $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('update', $peminjaman); // [cite: 2285]
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $dataLama = $peminjaman->load('detailPeminjaman')->toArray(); // [cite: 2288]

            $updateDataPeminjaman = [
                'tujuan_peminjaman' => $validated['tujuan_peminjaman'],
                'tanggal_rencana_pinjam' => $validated['tanggal_rencana_pinjam'],
                'tanggal_harus_kembali' => $validated['tanggal_harus_kembali'],
                'catatan_peminjam' => $validated['catatan_peminjam'] ?? null,
                'id_ruangan_tujuan_peminjaman' => $validated['id_ruangan_tujuan_peminjaman'] ?? null,
            ]; // [cite: 2289, 2290, 2291]

            if ($user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]) && $request->has('catatan_operator')) {
                $updateDataPeminjaman['catatan_operator'] = $validated['catatan_operator'] ?? null; // [cite: 2292]
            }

            $peminjaman->update($updateDataPeminjaman); // [cite: 2293]

            if ($user->id === $peminjaman->id_guru && $peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN) {
                $existingDetailIds = $peminjaman->detailPeminjaman()->pluck('id_barang_qr_code')->toArray(); // [cite: 2294]
                $newDetailIds = $validated['id_barang_qr_code'] ?? [];

                $idsToDelete = array_diff($existingDetailIds, $newDetailIds);
                if (!empty($idsToDelete)) {
                    $peminjaman->detailPeminjaman()->whereIn('id_barang_qr_code', $idsToDelete)->delete(); // [cite: 2295]
                }

                $idsToAdd = array_diff($newDetailIds, $existingDetailIds); // [cite: 2296]
                foreach ($idsToAdd as $barangQrId) {
                    $barangQr = BarangQrCode::find($barangQrId); // [cite: 2297]
                    if (
                        $barangQr && $barangQr->status === BarangQrCode::STATUS_TERSEDIA && !$barangQr->deleted_at &&
                        !in_array($barangQr->kondisi, [BarangQrCode::KONDISI_RUSAK_BERAT, BarangQrCode::KONDISI_HILANG])
                    ) { // [cite: 2298]

                        $existingDetail = DetailPeminjaman::where('id_barang_qr_code', $barangQrId)
                            ->where('id_peminjaman', '!=', $peminjaman->id)
                            ->whereHas('peminjaman', function ($qPeminjaman) {
                                $qPeminjaman->whereNotIn('status', [Peminjaman::STATUS_SELESAI, Peminjaman::STATUS_DITOLAK, Peminjaman::STATUS_DIBATALKAN]);
                            })->exists(); // [cite: 2299, 2300]

                        if ($existingDetail) {
                            DB::rollBack(); // [cite: 2301]
                            return redirect()->back()
                                ->with('error', "Barang '{$barangQr->barang->nama_barang} ({$barangQr->kode_inventaris_sekolah})' sudah dalam pengajuan atau sedang dipinjam di transaksi lain.")
                                ->withInput(); // [cite: 2302]
                        }

                        DetailPeminjaman::create([
                            'id_peminjaman' => $peminjaman->id,
                            'id_barang_qr_code' => $barangQrId,
                            'kondisi_sebelum' => $barangQr->kondisi,
                            'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                        ]); // [cite: 2303, 2304]
                    } else {
                        DB::rollBack(); // [cite: 2305]
                        $namaBarangError = $barangQr ? ($barangQr->barang->nama_barang . ' (' . $barangQr->kode_inventaris_sekolah . ')') : "ID: " . $barangQrId; // [cite: 2306]
                        return redirect()->back()
                            ->with('error', "Barang '{$namaBarangError}' tidak tersedia, rusak berat, hilang, atau tidak valid untuk ditambahkan.")
                            ->withInput(); // [cite: 2307]
                    }
                }
            }
            $peminjaman->refresh()->updateStatusPeminjaman(); // [cite: 2308]

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Update Pengajuan Peminjaman',
                'deskripsi' => "Memperbarui pengajuan peminjaman ID: {$peminjaman->id}",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_lama' => json_encode($dataLama),
                'data_baru' => $peminjaman->fresh()->load('detailPeminjaman')->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]); // [cite: 2309, 2310]


            DB::commit(); // [cite: 2311]
            $redirectUrl = $this->getRedirectUrl("peminjaman/{$peminjaman->id}");
            return redirect($redirectUrl)->with('success', 'Pengajuan peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack(); // [cite: 2312]
            Log::error("Gagal memperbarui pengajuan peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]); // [cite: 2313]
            return redirect()->back()->with('error', 'Gagal memperbarui pengajuan: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.'))->withInput(); // [cite: 2314]
        }
    }


    /**
     * Menyetujui satu item spesifik dalam sebuah peminjaman.
     */
    public function approveItem(Request $request, DetailPeminjaman $detailPeminjaman): JsonResponse
    {
        $this->authorize('manage', $detailPeminjaman->peminjaman);

        if ($detailPeminjaman->status_unit !== 'Diajukan') {
            return response()->json(['success' => false, 'message' => 'Item ini sudah diproses sebelumnya.'], 422);
        }

        DB::beginTransaction();
        try {
            $detailPeminjaman->status_unit = DetailPeminjaman::STATUS_ITEM_DISETUJUI;
            $detailPeminjaman->kondisi_sebelum = $detailPeminjaman->barangQrCode->kondisi;
            $detailPeminjaman->save();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Setujui Item Peminjaman',
                'deskripsi' => "Menyetujui item '{$detailPeminjaman->barangQrCode->kode_inventaris_sekolah}' untuk Peminjaman ID: {$detailPeminjaman->id_peminjaman}",
                'model_terkait' => DetailPeminjaman::class,
                'id_model_terkait' => $detailPeminjaman->id,
                'data_baru' => $detailPeminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Item berhasil disetujui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyetujui item peminjaman: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }

    /**
     * Menolak (dan menghapus) satu item spesifik dari sebuah peminjaman.
     */
    public function rejectItem(Request $request, DetailPeminjaman $detailPeminjaman): JsonResponse
    {
        $this->authorize('manage', $detailPeminjaman->peminjaman);

        if ($detailPeminjaman->status_unit !== 'Diajukan') {
            return response()->json(['success' => false, 'message' => 'Item ini sudah diproses sebelumnya.'], 422);
        }

        // Validasi alasan penolakan
        $request->validate(['alasan' => 'required|string|max:255']);

        DB::beginTransaction();
        try {
            $namaBarang = optional(optional($detailPeminjaman->barangQrCode)->barang)->nama_barang ?? 'Item';
            $kodeInventaris = optional($detailPeminjaman->barangQrCode)->kode_inventaris_sekolah ?? 'N/A';
            $peminjamanId = $detailPeminjaman->id_peminjaman;
            $dataLama = $detailPeminjaman->toArray();

            $detailPeminjaman->status_unit = DetailPeminjaman::STATUS_ITEM_DITOLAK;
            $detailPeminjaman->catatan_unit = $request->input('alasan'); // Simpan alasan di catatan unit
            $detailPeminjaman->save();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Tolak Item Peminjaman',
                'deskripsi' => "Item '{$namaBarang} ({$kodeInventaris})' ditolak dari Peminjaman ID: {$peminjamanId}. Alasan: " . $request->input('alasan'),
                'model_terkait' => DetailPeminjaman::class,
                'id_model_terkait' => $detailPeminjaman->id,
                'data_lama' => json_encode($dataLama),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            // Kirim status baru ke frontend
            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditolak.',
                'new_status_unit' => 'Ditolak'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menolak item peminjaman: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }

    /**
     * Memfinalisasi proses persetujuan setelah semua item ditinjau.
     */
    public function finalizeApproval(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        // PENYESUAIAN: Gunakan ability 'finalize' yang baru
        $this->authorize('finalize', $peminjaman);

        // Cek apakah masih ada item yang belum diproses (masih 'Diajukan')
        $itemsPending = $peminjaman->detailPeminjaman()->where('status_unit', 'Diajukan')->count();
        if ($itemsPending > 0) {
            return redirect()->back()->with('error', "Masih ada {$itemsPending} item yang belum ditinjau. Harap proses semua item.");
        }

        DB::beginTransaction();
        try {
            // Panggil method di model untuk mengupdate status induk berdasarkan detailnya
            $peminjaman->updateStatusPeminjaman();

            // Ambil status terbaru setelah diupdate oleh model
            $statusAkhir = $peminjaman->fresh()->status;

            // Setujui atau Tolak berdasarkan hasil akhir
            if ($statusAkhir === Peminjaman::STATUS_DISETUJUI) {
                $peminjaman->disetujui_oleh = Auth::id();
                $peminjaman->tanggal_disetujui = now();
                $peminjaman->catatan_operator = $request->input('catatan_final', $peminjaman->catatan_operator);
            } elseif ($statusAkhir === Peminjaman::STATUS_DITOLAK) { // Terjadi jika semua item ditolak
                $peminjaman->ditolak_oleh = Auth::id();
                $peminjaman->tanggal_ditolak = now();
                $peminjaman->catatan_operator = $request->input('catatan_final', 'Semua item ditolak.');
            }
            $peminjaman->save();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Finalisasi Persetujuan Peminjaman',
                'deskripsi' => "Finalisasi untuk Peminjaman ID: {$peminjaman->id}. Status akhir: {$peminjaman->status}",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_baru' => $peminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            return redirect($this->getRedirectUrl("peminjaman/{$peminjaman->id}"))
                ->with('success', 'Proses persetujuan telah difinalisasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal finalisasi peminjaman ID {$peminjaman->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memfinalisasi peminjaman.');
        }
    }


    public function processItemHandover(Request $request, DetailPeminjaman $detailPeminjaman): JsonResponse|RedirectResponse
    {
        $this->authorize('processHandover', $detailPeminjaman); // [cite: 2335]
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction(); // [cite: 2337]
        try {
            $barangQr = $detailPeminjaman->barangQrCode; // [cite: 2338]
            $detailPeminjaman->konfirmasiPengambilan($user->id); // [cite: 2338]

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Serah Terima Item Peminjaman',
                'deskripsi' => "Item {$barangQr->kode_inventaris_sekolah} diserahkan untuk Peminjaman ID: {$detailPeminjaman->id_peminjaman}",
                'model_terkait' => DetailPeminjaman::class,
                'id_model_terkait' => $detailPeminjaman->id,
                'data_baru' => $detailPeminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]); // [cite: 2339, 2340]
            DB::commit(); // [cite: 2341]
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Item berhasil diserahkan.', 'new_status_unit' => $detailPeminjaman->status_unit, 'new_status_peminjaman' => $detailPeminjaman->peminjaman->status]); // [cite: 2341]
            }
            return redirect()->back()->with('success', 'Item berhasil diserahkan.'); // [cite: 2342]
        } catch (\Exception $e) {
            DB::rollBack(); // [cite: 2343]
            Log::error("Gagal serah terima item peminjaman Detail ID {$detailPeminjaman->id}: " . $e->getMessage(), ['exception' => $e]); // [cite: 2344]
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyerahkan item: ' . $e->getMessage()], 500); // [cite: 2345]
            }
            return redirect()->back()->with('error', 'Gagal menyerahkan item: ' . $e->getMessage()); // [cite: 2346]
        }
    }

    public function processItemReturn(Request $request, DetailPeminjaman $detailPeminjaman): JsonResponse|RedirectResponse
    {
        $this->authorize('processReturn', $detailPeminjaman); // [cite: 2347]
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $validated = $request->validate([
            'kondisi_setelah_kembali' => ['required', \Illuminate\Validation\Rule::in(BarangQrCode::getValidKondisi())],
            'catatan_pengembalian_unit' => 'nullable|string|max:255',
        ]); // [cite: 2349]

        DB::beginTransaction(); // [cite: 2350]
        try {
            $barangQr = $detailPeminjaman->barangQrCode()->withTrashed()->first(); // [cite: 2350]
            $detailPeminjaman->verifikasiPengembalian(
                $user->id,
                $validated['kondisi_setelah_kembali'],
                $validated['catatan_pengembalian_unit']
            ); // [cite: 2351]

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Proses Pengembalian Item Peminjaman',
                'deskripsi' => "Item {$barangQr->kode_inventaris_sekolah} dikembalikan dari Peminjaman ID: {$detailPeminjaman->id_peminjaman}. Kondisi: {$validated['kondisi_setelah_kembali']}",
                'model_terkait' => DetailPeminjaman::class,
                'id_model_terkait' => $detailPeminjaman->id,
                'data_baru' => $detailPeminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]); // [cite: 2352, 2353]
            DB::commit(); // [cite: 2354]
            if ($request->ajax() || $request->wantsJson()) {
                $barangQr->refresh(); // [cite: 2354]
                return response()->json([
                    'success' => true,
                    'message' => 'Item berhasil diproses pengembaliannya.',
                    'new_status_unit' => $detailPeminjaman->status_unit,
                    'new_kondisi_barang' => $barangQr->kondisi,
                    'new_status_barang' => $barangQr->trashed() ? 'Diarsipkan (Hilang)' : $barangQr->status,
                    'new_status_peminjaman' => $detailPeminjaman->peminjaman->status
                ]); // [cite: 2355, 2356]
            }
            return redirect()->back()->with('success', 'Item berhasil diproses pengembaliannya.'); // [cite: 2357]
        } catch (\Exception $e) {
            DB::rollBack(); // [cite: 2358]
            Log::error("Gagal proses pengembalian item Detail ID {$detailPeminjaman->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]); // [cite: 2359]
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal memproses pengembalian: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.')], 500); // [cite: 2360]
            }
            return redirect()->back()->with('error', 'Gagal memproses pengembalian: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.')); // [cite: 2361]
        }
    }

    public function cancelByUser(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('cancelByUser', $peminjaman); // [cite: 2362]
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!in_array($peminjaman->status, [
            Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
            Peminjaman::STATUS_DISETUJUI
        ])) {
            return redirect()->back()->with('error', 'Peminjaman dengan status ini tidak dapat dibatalkan.');
        }

        DB::beginTransaction(); // [cite: 2364]
        try {
            $catatanPembatalan = "Dibatalkan oleh pengguna: " . $user->username; // [cite: 2365]
            if ($request->filled('alasan_pembatalan')) {
                $catatanPembatalan .= ". Alasan: " . $request->input('alasan_pembatalan'); // [cite: 2366]
            }
            $dataLama = $peminjaman->toArray(); // [cite: 2367]
            $peminjaman->status = Peminjaman::STATUS_DIBATALKAN; // [cite: 2367]
            $peminjaman->catatan_operator = $peminjaman->catatan_operator ? $peminjaman->catatan_operator . " | " . $catatanPembatalan : $catatanPembatalan; // [cite: 2368]
            $peminjaman->save(); // [cite: 2368]

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Batalkan Peminjaman',
                'deskripsi' => "Peminjaman ID: {$peminjaman->id} dibatalkan. {$catatanPembatalan}",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_lama' => json_encode($dataLama),
                'data_baru' => $peminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]); // [cite: 2369, 2370]
            DB::commit(); // [cite: 2371]
            $redirectUrl = $this->getRedirectUrl("peminjaman/{$peminjaman->id}");
            return redirect($redirectUrl)->with('success', 'Peminjaman berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack(); // [cite: 2372]
            Log::error("Gagal membatalkan peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]); // [cite: 2373]
            return redirect()->back()->with('error', 'Gagal membatalkan peminjaman: ' . $e->getMessage()); // [cite: 2373]
        }
    }

    public function destroy(Peminjaman $peminjaman, Request $request): RedirectResponse
    {
        $this->authorize('delete', $peminjaman); // [cite: 2374]
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction(); // [cite: 2376]
        try {
            $dataLama = $peminjaman->load('detailPeminjaman')->toArray(); // [cite: 2377]
            $peminjaman->delete(); // [cite: 2378]

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Arsipkan Peminjaman',
                'deskripsi' => "Mengarsipkan data peminjaman ID: {$peminjaman->id} ({$peminjaman->tujuan_peminjaman})",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_lama' => json_encode($dataLama),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]); // [cite: 2379, 2380]
            DB::commit(); // [cite: 2381]
            $redirectUrl = $this->getRedirectUrl('peminjaman');
            return redirect($redirectUrl)->with('success', 'Data peminjaman berhasil diarsipkan.');
        } catch (\Exception $e) {
            DB::rollBack(); // [cite: 2382]
            Log::error("Gagal mengarsipkan peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]); // [cite: 2383]
            return redirect()->back()->with('error', 'Gagal mengarsipkan data peminjaman.'); // [cite: 2383]
        }
    }

    public function restore(Request $request, $id): RedirectResponse
    {
        $peminjaman = Peminjaman::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $peminjaman); // [cite: 2385]
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction(); // [cite: 2386]
        try {
            $peminjaman->restore(); // [cite: 2387]

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Pulihkan Peminjaman',
                'deskripsi' => "Memulihkan data peminjaman ID: {$peminjaman->id} ({$peminjaman->tujuan_peminjaman})",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_baru' => $peminjaman->fresh()->load('detailPeminjaman')->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]); // [cite: 2388, 2389]
            DB::commit(); // [cite: 2390]
            $redirectUrl = $this->getRedirectUrl('peminjaman');
            return redirect($redirectUrl)->with('success', 'Data peminjaman berhasil dipulihkan.');
        } catch (\Exception $e) {
            DB::rollBack(); // [cite: 2391]
            Log::error("Gagal memulihkan peminjaman ID {$id}: " . $e->getMessage(), ['exception' => $e]); // [cite: 2392]
            return redirect()->back()->with('error', 'Gagal memulihkan data peminjaman.'); // [cite: 2392]
        }
    }
}
