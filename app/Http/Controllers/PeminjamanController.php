<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use App\Models\User;
use App\Models\LogAktivitas;
use Illuminate\Http\Request; // Tetap dibutuhkan untuk beberapa method
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\PeminjamanStoreRequest; // Anda perlu membuat request ini: php artisan make:request PeminjamanStoreRequest
use App\Http\Requests\PeminjamanUpdateRequest; // Anda perlu membuat request ini: php artisan make:request PeminjamanUpdateRequest
use Carbon\Carbon;

class PeminjamanController extends Controller
{
    use AuthorizesRequests;

    // Helper methods (getViewPathBasedOnRole, getRedirectRouteName)
    // Salin dari PeminjamanController yang sudah ada atau controller lain Anda
    private function getViewPathBasedOnRole(string $adminView, ?string $operatorView = null, ?string $guruView = null): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return $adminView; // Default jika tidak ada user (seharusnya tidak terjadi di middleware auth)
        
        $viewPath = $adminView; // Default
        if ($user->hasRole(User::ROLE_OPERATOR) && $operatorView) {
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

    private function getRedirectRouteName(string $baseRouteName, string $adminFallbackRouteName, ?string $operatorPrefix = 'operator.', ?string $guruPrefix = 'guru.'): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return $adminFallbackRouteName;
        
        $rolePrefix = '';
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $rolePrefix = 'admin.';
        } elseif ($user->hasRole(User::ROLE_OPERATOR) && $operatorPrefix) {
            $rolePrefix = $operatorPrefix;
        } elseif ($user->hasRole(User::ROLE_GURU) && $guruPrefix) {
            $rolePrefix = $guruPrefix;
        }

        if (!empty($rolePrefix) && Route::has($rolePrefix . $baseRouteName)) {
            return $rolePrefix . $baseRouteName;
        }
        if (Route::has($baseRouteName)) {
            return $baseRouteName;
        }
        return Route::has($adminFallbackRouteName) ? $adminFallbackRouteName : $baseRouteName;
    }


    public function index(Request $request): View
    {
        $this->authorize('viewAny', Peminjaman::class); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $query = Peminjaman::with([
            'guru', //
            'disetujuiOlehUser', //
            'ditolakOlehUser', //
            'ruanganTujuanPeminjaman', //
            'detailPeminjaman.barangQrCode.barang', //
            'detailPeminjaman.barangQrCode.ruangan' //
        ]);

        $searchTerm = $request->input('search');
        $statusFilter = $request->input('status');
        $guruFilter = $request->input('id_guru');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $statusArsipFilter = $request->input('status_arsip', 'aktif'); // Default ke aktif

        if ($statusArsipFilter === 'arsip') {
            $query->onlyTrashed();
        } elseif ($statusArsipFilter === 'semua') {
            $query->withTrashed();
        }
        // Jika 'aktif', default query (tanpa withTrashed/onlyTrashed) sudah benar

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tujuan_peminjaman', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('guru', fn($ug) => $ug->where('username', 'LIKE', "%{$searchTerm}%"))
                    ->orWhereHas('detailPeminjaman.barangQrCode', fn($ub) => $ub->where('kode_inventaris_sekolah', 'LIKE', "%{$searchTerm}%"))
                    ->orWhereHas('detailPeminjaman.barangQrCode.barang', fn($ubm) => $ubm->where('nama_barang', 'LIKE', "%{$searchTerm}%"));
            });
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        if ($guruFilter && $user->hasRole(User::ROLE_ADMIN)) {
            $query->where('id_guru', $guruFilter);
        }
        if ($tanggalMulai) {
            $query->whereDate('tanggal_pengajuan', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $query->whereDate('tanggal_pengajuan', '<=', $tanggalSelesai);
        }

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function ($q) use ($ruanganOperatorIds) {
                $q->whereHas('detailPeminjaman.barangQrCode', function ($qDetail) use ($ruanganOperatorIds) {
                    $qDetail->whereIn('id_ruangan', $ruanganOperatorIds);
                })
                ->orWhereIn('id_ruangan_tujuan_peminjaman', $ruanganOperatorIds);
            });
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            $query->where('id_guru', $user->id);
        }

        $peminjamanList = $query->latest('tanggal_pengajuan')->latest('id')->paginate(15)->withQueryString();
        $statusList = Peminjaman::getValidStatuses();
        $guruList = $user->hasRole(User::ROLE_ADMIN) ? User::where('role', User::ROLE_GURU)->orderBy('username')->get() : collect();
        $ruanganListAll = Ruangan::orderBy('nama_ruangan')->get(); // Untuk filter jika diperlukan

        $viewPath = $this->getViewPathBasedOnRole(
            'admin.peminjaman.index',
            'operator.peminjaman.index',
            'guru.peminjaman.index'
        );

        return view($viewPath, compact(
            'peminjamanList',
            'statusList',
            'guruList',
            'request', // Untuk mempertahankan nilai filter di view
            'statusArsipFilter', // Kirim ini ke view
            'ruanganListAll' // Untuk filter di view Admin
        ));
    }

    public function create(): View
    {
        $this->authorize('create', Peminjaman::class); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $barangList = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereIn('kondisi', [BarangQrCode::KONDISI_BAIK, BarangQrCode::KONDISI_KURANG_BAIK])
            ->whereDoesntHave('peminjamanDetails', function ($query) { // Pastikan barang tidak sedang dalam detail peminjaman aktif lain
                $query->whereIn('status_unit', [
                    DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                    DetailPeminjaman::STATUS_ITEM_DISETUJUI,
                    DetailPeminjaman::STATUS_ITEM_DIAMBIL
                ]);
            })
            ->whereNull('deleted_at')
            ->with('barang', 'ruangan', 'pemegangPersonal')
            ->orderBy('id_barang')
            ->get()
            ->map(function ($item) {
                $namaRuangan = $item->ruangan ? $item->ruangan->nama_ruangan : ($item->id_pemegang_personal ? 'Pemegang: ' . optional($item->pemegangPersonal)->username : 'Tidak di Ruangan');
                return [
                    'id' => $item->id,
                    'text' => $item->barang->nama_barang . ' (' . $item->kode_inventaris_sekolah . ($item->no_seri_pabrik ? ' / SN: ' . $item->no_seri_pabrik : '') . ') - Kondisi: ' . $item->kondisi . ' - Lokasi: ' . $namaRuangan
                ];
            });

        $ruanganTujuanList = Ruangan::whereNull('deleted_at')->orderBy('nama_ruangan')->get();

        $viewPath = $this->getViewPathBasedOnRole(
            null,
            null,
            'guru.peminjaman.create'
        );
        return view($viewPath, compact('barangList', 'ruanganTujuanList'));
    }

    public function store(PeminjamanStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Peminjaman::class); // Menggunakan PeminjamanPolicy
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
            ]);

            foreach ($validated['id_barang_qr_code'] as $barangQrId) {
                $barangQr = BarangQrCode::find($barangQrId);
                if ($barangQr && $barangQr->status === BarangQrCode::STATUS_TERSEDIA && !$barangQr->deleted_at &&
                    !in_array($barangQr->kondisi, [BarangQrCode::KONDISI_RUSAK_BERAT, BarangQrCode::KONDISI_HILANG])) {
                    // Cek lagi apakah barang ini sudah ada di detail peminjaman aktif lain
                     $existingDetail = DetailPeminjaman::where('id_barang_qr_code', $barangQrId)
                        ->whereHas('peminjaman', function ($qPeminjaman) {
                            $qPeminjaman->whereNotIn('status', [Peminjaman::STATUS_SELESAI, Peminjaman::STATUS_DITOLAK, Peminjaman::STATUS_DIBATALKAN]);
                        })->exists();

                    if ($existingDetail) {
                        DB::rollBack();
                        return redirect()->back()
                            ->with('error', "Barang '{$barangQr->barang->nama_barang} ({$barangQr->kode_inventaris_sekolah})' sudah dalam pengajuan atau sedang dipinjam.")
                            ->withInput();
                    }

                    DetailPeminjaman::create([
                        'id_peminjaman' => $peminjaman->id,
                        'id_barang_qr_code' => $barangQrId,
                        'kondisi_sebelum' => $barangQr->kondisi,
                        'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                    ]);
                } else {
                    DB::rollBack();
                    $namaBarangError = $barangQr ? ($barangQr->barang->nama_barang . ' (' . $barangQr->kode_inventaris_sekolah . ')') : "ID: ".$barangQrId;
                    return redirect()->back()
                        ->with('error', "Barang '{$namaBarangError}' tidak tersedia, rusak berat, hilang, atau tidak valid untuk dipinjam.")
                        ->withInput();
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
            ]);

            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('peminjaman.index', 'admin.peminjaman.index', 'operator.peminjaman.index', 'guru.peminjaman.index');
            return redirect()->route($redirectRoute)->with('success', 'Pengajuan peminjaman berhasil dikirim.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan pengajuan peminjaman: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal mengirim pengajuan: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.'))->withInput();
        }
    }

    public function show(Peminjaman $peminjaman): View
    {
        $this->authorize('view', $peminjaman); // Menggunakan PeminjamanPolicy
        $peminjaman->load([
            'guru', //
            'disetujuiOlehUser', //
            'ditolakOlehUser', //
            'ruanganTujuanPeminjaman', //
            'detailPeminjaman.barangQrCode.barang', //
            'detailPeminjaman.barangQrCode.ruangan', //
            'detailPeminjaman.barangQrCode.pemegangPersonal' //
        ]);
        $kondisiList = BarangQrCode::getValidKondisi();

        $viewPath = $this->getViewPathBasedOnRole(
            'admin.peminjaman.show',
            'operator.peminjaman.show',
            'guru.peminjaman.show'
        );
        return view($viewPath, compact('peminjaman', 'kondisiList'));
    }

    public function edit(Peminjaman $peminjaman): View|RedirectResponse
    {
        $this->authorize('update', $peminjaman); // Menggunakan PeminjamanPolicy

        // Logic yang sama dengan create, ditambah data peminjaman yang ada
        $barangList = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereIn('kondisi', [BarangQrCode::KONDISI_BAIK, BarangQrCode::KONDISI_KURANG_BAIK])
            ->whereNull('deleted_at')
             ->with('barang', 'ruangan', 'pemegangPersonal')
            ->orderBy('id_barang')
            ->get()
            ->map(function ($item) {
                $namaRuangan = $item->ruangan ? $item->ruangan->nama_ruangan : ($item->id_pemegang_personal ? 'Pemegang: ' . optional($item->pemegangPersonal)->username : 'Tidak di Ruangan');
                return [
                    'id' => $item->id,
                    'text' => $item->barang->nama_barang . ' (' . $item->kode_inventaris_sekolah . ($item->no_seri_pabrik ? ' / SN: ' . $item->no_seri_pabrik : '') . ') - Kondisi: ' . $item->kondisi . ' - Lokasi: ' . $namaRuangan
                ];
            });
        // Tambahkan barang yang sudah ada di peminjaman ini (meskipun mungkin sudah tidak tersedia untuk peminjaman lain)
        $currentPeminjamanItemIds = $peminjaman->detailPeminjaman->pluck('id_barang_qr_code');
        $currentPeminjamanItems = BarangQrCode::whereIn('id', $currentPeminjamanItemIds)
            ->with('barang', 'ruangan', 'pemegangPersonal')
            ->get()
            ->map(function ($item) {
                $namaRuangan = $item->ruangan ? $item->ruangan->nama_ruangan : ($item->id_pemegang_personal ? 'Pemegang: ' . optional($item->pemegangPersonal)->username : 'Tidak di Ruangan');
                 return [
                    'id' => $item->id,
                    'text' => $item->barang->nama_barang . ' (' . $item->kode_inventaris_sekolah . ($item->no_seri_pabrik ? ' / SN: ' . $item->no_seri_pabrik : '') . ') - Kondisi: ' . $item->kondisi . ' - Lokasi: ' . $namaRuangan
                ];
            });
        // Gabungkan dan pastikan unik
        $barangList = $barangList->concat($currentPeminjamanItems)->unique('id')->sortBy('text');


        $selectedBarangIds = $peminjaman->detailPeminjaman->pluck('id_barang_qr_code')->toArray();
        $ruanganTujuanList = Ruangan::whereNull('deleted_at')->orderBy('nama_ruangan')->get();

        $viewPath = $this->getViewPathBasedOnRole(
            'admin.peminjaman.edit',
            Auth::user()->hasRole(User::ROLE_OPERATOR) ? 'operator.peminjaman.edit-catatan' : null, // Operator mungkin hanya edit catatan
            'guru.peminjaman.edit'
        );
         if (Auth::user()->hasRole(User::ROLE_OPERATOR) && $viewPath == 'operator.peminjaman.edit-catatan' && !view()->exists($viewPath)) {
            // Jika view operator.peminjaman.edit-catatan tidak ada, operator tidak bisa edit
            return redirect()->route($this->getRedirectRouteName('peminjaman.show', 'admin.peminjaman.show', 'operator.peminjaman.show'), $peminjaman->id)
                            ->with('warning', 'Anda tidak memiliki form khusus untuk mengedit catatan peminjaman ini.');
        }

        return view($viewPath, compact('peminjaman', 'barangList', 'selectedBarangIds', 'ruanganTujuanList'));
    }

    public function update(PeminjamanUpdateRequest $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('update', $peminjaman); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $dataLama = $peminjaman->load('detailPeminjaman')->toArray();

            $updateDataPeminjaman = [
                'tujuan_peminjaman' => $validated['tujuan_peminjaman'],
                'tanggal_rencana_pinjam' => $validated['tanggal_rencana_pinjam'],
                'tanggal_harus_kembali' => $validated['tanggal_harus_kembali'],
                'catatan_peminjam' => $validated['catatan_peminjam'] ?? null,
                'id_ruangan_tujuan_peminjaman' => $validated['id_ruangan_tujuan_peminjaman'] ?? null,
            ];
            // Admin atau Operator yang berwenang bisa juga update catatan_operator
            if ($user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]) && $request->has('catatan_operator')) {
                $updateDataPeminjaman['catatan_operator'] = $validated['catatan_operator'] ?? null;
            }

            $peminjaman->update($updateDataPeminjaman);

            // Hanya sinkronisasi detail jika pengguna adalah Guru (pemilik pengajuan) dan status masih menunggu
            if ($user->id === $peminjaman->id_guru && $peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN) {
                $existingDetailIds = $peminjaman->detailPeminjaman()->pluck('id_barang_qr_code')->toArray();
                $newDetailIds = $validated['id_barang_qr_code'] ?? [];

                $idsToDelete = array_diff($existingDetailIds, $newDetailIds);
                if (!empty($idsToDelete)) {
                    $peminjaman->detailPeminjaman()->whereIn('id_barang_qr_code', $idsToDelete)->delete();
                }

                $idsToAdd = array_diff($newDetailIds, $existingDetailIds);
                foreach ($idsToAdd as $barangQrId) {
                    $barangQr = BarangQrCode::find($barangQrId);
                    if ($barangQr && $barangQr->status === BarangQrCode::STATUS_TERSEDIA && !$barangQr->deleted_at &&
                        !in_array($barangQr->kondisi, [BarangQrCode::KONDISI_RUSAK_BERAT, BarangQrCode::KONDISI_HILANG])) {
                        
                        $existingDetail = DetailPeminjaman::where('id_barang_qr_code', $barangQrId)
                            ->where('id_peminjaman', '!=', $peminjaman->id) // Cek di peminjaman lain
                            ->whereHas('peminjaman', function ($qPeminjaman) {
                                $qPeminjaman->whereNotIn('status', [Peminjaman::STATUS_SELESAI, Peminjaman::STATUS_DITOLAK, Peminjaman::STATUS_DIBATALKAN]);
                            })->exists();

                        if ($existingDetail) {
                            DB::rollBack();
                            return redirect()->back()
                                ->with('error', "Barang '{$barangQr->barang->nama_barang} ({$barangQr->kode_inventaris_sekolah})' sudah dalam pengajuan atau sedang dipinjam di transaksi lain.")
                                ->withInput();
                        }
                        
                        DetailPeminjaman::create([
                            'id_peminjaman' => $peminjaman->id,
                            'id_barang_qr_code' => $barangQrId,
                            'kondisi_sebelum' => $barangQr->kondisi,
                            'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                        ]);
                    } else {
                        DB::rollBack();
                        $namaBarangError = $barangQr ? ($barangQr->barang->nama_barang . ' (' . $barangQr->kode_inventaris_sekolah . ')') : "ID: ".$barangQrId;
                        return redirect()->back()
                            ->with('error', "Barang '{$namaBarangError}' tidak tersedia, rusak berat, hilang, atau tidak valid untuk ditambahkan.")
                            ->withInput();
                    }
                }
            }
            $peminjaman->refresh()->updateStatusPeminjaman();


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
            ]);

            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('peminjaman.show', 'admin.peminjaman.show', 'operator.peminjaman.show', 'guru.peminjaman.show');
            return redirect()->route($redirectRoute, $peminjaman->id)->with('success', 'Pengajuan peminjaman berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memperbarui pengajuan peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal memperbarui pengajuan: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.'))->withInput();
        }
    }


    public function approve(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('manage', $peminjaman); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction();
        try {
            $peminjaman->status = Peminjaman::STATUS_DISETUJUI;
            $peminjaman->disetujui_oleh = $user->id;
            $peminjaman->tanggal_disetujui = now();
            $peminjaman->catatan_operator = $request->input('catatan_operator_approve');
            $peminjaman->save();

            foreach ($peminjaman->detailPeminjaman as $detail) {
                if ($detail->status_unit === DetailPeminjaman::STATUS_ITEM_DIAJUKAN) {
                    $detail->status_unit = DetailPeminjaman::STATUS_ITEM_DISETUJUI;
                    $detail->kondisi_sebelum = $detail->barangQrCode->kondisi; // Catat kondisi barang saat disetujui (sebelum diambil)
                    $detail->save();
                }
            }

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Setujui Peminjaman',
                'deskripsi' => "Menyetujui peminjaman ID: {$peminjaman->id} oleh {$peminjaman->guru->username}",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_baru' => $peminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('peminjaman.show', 'admin.peminjaman.show', 'operator.peminjaman.show');
            return redirect()->route($redirectRoute, $peminjaman->id)->with('success', 'Peminjaman berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyetujui peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal menyetujui peminjaman: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('manage', $peminjaman); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $request->validate(['catatan_operator_reject' => 'required|string|max:500'],
                           ['catatan_operator_reject.required' => 'Catatan penolakan wajib diisi.']);

        DB::beginTransaction();
        try {
            $peminjaman->status = Peminjaman::STATUS_DITOLAK;
            $peminjaman->ditolak_oleh = $user->id;
            $peminjaman->tanggal_ditolak = now();
            $peminjaman->catatan_operator = $request->input('catatan_operator_reject');
            $peminjaman->save();

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Tolak Peminjaman',
                'deskripsi' => "Menolak peminjaman ID: {$peminjaman->id} oleh {$peminjaman->guru->username}. Alasan: " . $peminjaman->catatan_operator,
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_baru' => $peminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('peminjaman.show', 'admin.peminjaman.show', 'operator.peminjaman.show');
            return redirect()->route($redirectRoute, $peminjaman->id)->with('success', 'Peminjaman berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menolak peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal menolak peminjaman: ' . $e->getMessage());
        }
    }

    public function processItemHandover(Request $request, DetailPeminjaman $detailPeminjaman): JsonResponse|RedirectResponse
    {
        $this->authorize('processHandover', $detailPeminjaman); // Menggunakan DetailPeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction();
        try {
            $barangQr = $detailPeminjaman->barangQrCode;
            $detailPeminjaman->konfirmasiPengambilan($user->id);

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Serah Terima Item Peminjaman',
                'deskripsi' => "Item {$barangQr->kode_inventaris_sekolah} diserahkan untuk Peminjaman ID: {$detailPeminjaman->id_peminjaman}",
                'model_terkait' => DetailPeminjaman::class,
                'id_model_terkait' => $detailPeminjaman->id,
                'data_baru' => $detailPeminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Item berhasil diserahkan.', 'new_status_unit' => $detailPeminjaman->status_unit, 'new_status_peminjaman' => $detailPeminjaman->peminjaman->status]);
            }
            return redirect()->back()->with('success', 'Item berhasil diserahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal serah terima item peminjaman Detail ID {$detailPeminjaman->id}: " . $e->getMessage(), ['exception' => $e]);
             if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyerahkan item: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal menyerahkan item: ' . $e->getMessage());
        }
    }

    public function processItemReturn(Request $request, DetailPeminjaman $detailPeminjaman): JsonResponse|RedirectResponse
    {
        $this->authorize('processReturn', $detailPeminjaman); // Menggunakan DetailPeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $validated = $request->validate([
            'kondisi_setelah_kembali' => ['required', \Illuminate\Validation\Rule::in(BarangQrCode::getValidKondisi())],
            'catatan_pengembalian_unit' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $barangQr = $detailPeminjaman->barangQrCode()->withTrashed()->first(); // withTrashed jika hilang
            $detailPeminjaman->verifikasiPengembalian(
                $user->id,
                $validated['kondisi_setelah_kembali'],
                $validated['catatan_pengembalian_unit']
            );

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Proses Pengembalian Item Peminjaman',
                'deskripsi' => "Item {$barangQr->kode_inventaris_sekolah} dikembalikan dari Peminjaman ID: {$detailPeminjaman->id_peminjaman}. Kondisi: {$validated['kondisi_setelah_kembali']}",
                'model_terkait' => DetailPeminjaman::class,
                'id_model_terkait' => $detailPeminjaman->id,
                'data_baru' => $detailPeminjaman->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            if ($request->ajax() || $request->wantsJson()) {
                // Refresh barangQr untuk mendapatkan status & kondisi terbaru, termasuk jika trashed
                $barangQr->refresh();
                return response()->json([
                    'success' => true,
                    'message' => 'Item berhasil diproses pengembaliannya.',
                    'new_status_unit' => $detailPeminjaman->status_unit,
                    'new_kondisi_barang' => $barangQr->kondisi,
                    'new_status_barang' => $barangQr->trashed() ? 'Diarsipkan (Hilang)' : $barangQr->status,
                    'new_status_peminjaman' => $detailPeminjaman->peminjaman->status
                ]);
            }
            return redirect()->back()->with('success', 'Item berhasil diproses pengembaliannya.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal proses pengembalian item Detail ID {$detailPeminjaman->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal memproses pengembalian: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.')], 500);
            }
            return redirect()->back()->with('error', 'Gagal memproses pengembalian: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.'));
        }
    }

    public function cancelByUser(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('cancelByUser', $peminjaman); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction();
        try {
            $catatanPembatalan = "Dibatalkan oleh pengguna: " . $user->username;
            if ($request->filled('alasan_pembatalan')) {
                $catatanPembatalan .= ". Alasan: " . $request->input('alasan_pembatalan');
            }
            $dataLama = $peminjaman->toArray();
            $peminjaman->status = Peminjaman::STATUS_DIBATALKAN;
            $peminjaman->catatan_operator = $peminjaman->catatan_operator ? $peminjaman->catatan_operator . " | " . $catatanPembatalan : $catatanPembatalan;
            $peminjaman->save();

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
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('peminjaman.show', 'admin.peminjaman.show', 'operator.peminjaman.show', 'guru.peminjaman.show');
            return redirect()->route($redirectRoute, $peminjaman->id)->with('success', 'Peminjaman berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal membatalkan peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal membatalkan peminjaman: ' . $e->getMessage());
        }
    }

    public function destroy(Peminjaman $peminjaman, Request $request): RedirectResponse
    {
        $this->authorize('delete', $peminjaman); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction();
        try {
            $dataLama = $peminjaman->load('detailPeminjaman')->toArray();
            // Event 'deleting' di model Peminjaman akan menghapus detailnya juga
            $peminjaman->delete();

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Arsipkan Peminjaman',
                'deskripsi' => "Mengarsipkan data peminjaman ID: {$peminjaman->id} ({$peminjaman->tujuan_peminjaman})",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id, // Gunakan ID sebelum terhapus (tersedia karena soft delete)
                'data_lama' => json_encode($dataLama),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('peminjaman.index', 'admin.peminjaman.index');
            return redirect()->route($redirectRoute)->with('success', 'Data peminjaman berhasil diarsipkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal mengarsipkan peminjaman ID {$peminjaman->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal mengarsipkan data peminjaman.');
        }
    }

    public function restore(Request $request, $id): RedirectResponse
    {
        $peminjaman = Peminjaman::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $peminjaman); // Menggunakan PeminjamanPolicy
        $user = Auth::user();
        /** @var \App\Models\User $user */

        DB::beginTransaction();
        try {
            // Event 'restoring' di model Peminjaman akan memulihkan detailnya juga
            $peminjaman->restore();

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Pulihkan Peminjaman',
                'deskripsi' => "Memulihkan data peminjaman ID: {$peminjaman->id} ({$peminjaman->tujuan_peminjaman})",
                'model_terkait' => Peminjaman::class,
                'id_model_terkait' => $peminjaman->id,
                'data_baru' => $peminjaman->fresh()->load('detailPeminjaman')->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('peminjaman.index', 'admin.peminjaman.index');
            return redirect()->route($redirectRoute, ['status_arsip' => 'arsip'])->with('success', 'Data peminjaman berhasil dipulihkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan peminjaman ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal memulihkan data peminjaman.');
        }
    }
}