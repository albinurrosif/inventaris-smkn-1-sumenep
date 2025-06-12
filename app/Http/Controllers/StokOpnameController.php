<?php

namespace App\Http\Controllers;

use App\Models\StokOpname;
use App\Models\DetailStokOpname;
use App\Models\Ruangan;
use App\Models\User;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\LogAktivitas;
use App\Models\ArsipBarang;
use App\Models\BarangStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException; // Ditambahkan
use Carbon\Carbon;

class StokOpnameController extends Controller
{
    use AuthorizesRequests;

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
        $this->authorize('viewAny', StokOpname::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $searchTerm = $request->input('search');
        $statusFilter = $request->input('status');
        $ruanganFilter = $request->input('id_ruangan');
        $operatorFilter = $request->input('id_operator');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $statusArsipFilter = $request->input('status_arsip', 'aktif');

        $query = StokOpname::query();

        if ($statusArsipFilter === 'arsip') {
            $query->onlyTrashed();
        } elseif ($statusArsipFilter === 'semua') {
            $query->withTrashed();
        }

        $query->with(['ruangan', 'operator', 'detailStokOpname']);

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function ($qSub) use ($user, $ruanganOperatorIds) {
                $qSub->where('id_operator', $user->id)
                    ->orWhereIn('id_ruangan', $ruanganOperatorIds);
            });
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('catatan', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('ruangan', fn($qr) => $qr->where('nama_ruangan', 'LIKE', "%{$searchTerm}%")->orWhere('kode_ruangan', 'LIKE', "%{$searchTerm}%"));
            });
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        if ($ruanganFilter) {
            $query->where('id_ruangan', $ruanganFilter);
        }
        if ($operatorFilter && $user->hasRole(User::ROLE_ADMIN)) {
            $query->where('id_operator', $operatorFilter);
        }
        if ($tanggalMulai) {
            $query->whereDate('tanggal_opname', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $query->whereDate('tanggal_opname', '<=', $tanggalSelesai);
        }

        $stokOpnameList = $query->latest('tanggal_opname')->latest('id')->paginate(15)->withQueryString();

        $ruanganList = $user->hasRole(User::ROLE_ADMIN) ? Ruangan::whereNull('deleted_at')->orderBy('nama_ruangan')->get() : $user->ruanganYangDiKelola()->whereNull('deleted_at')->orderBy('nama_ruangan')->get();
        $operatorList = User::where('role', User::ROLE_OPERATOR)->orderBy('username')->get();
        $statusList = StokOpname::getValidStatuses();

        $rolePrefix = $this->getRolePrefix();

        return view('pages.stok-opname.index', compact(
            'stokOpnameList',
            'ruanganList',
            'operatorList',
            'statusList',
            'request',
            'searchTerm',
            'statusFilter',
            'ruanganFilter',
            'operatorFilter',
            'tanggalMulai',
            'tanggalSelesai',
            'statusArsipFilter',
            'rolePrefix' // tambahkan ini supaya bisa dipakai di view
        ));
    }

    public function create(): View
    {
        $this->authorize('create', StokOpname::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $ruanganList = $user->hasRole(User::ROLE_ADMIN)
            ? Ruangan::whereNull('deleted_at')->orderBy('nama_ruangan')->get()
            : $user->ruanganYangDiKelola()->whereNull('deleted_at')->orderBy('nama_ruangan')->get();

        $operatorPelaksanaList = collect();
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $operatorPelaksanaList = User::where('role', User::ROLE_OPERATOR)
                ->orderBy('username')->get();
        }
        $viewPath = $this->getViewPathBasedOnRole('admin.stok-opname.create', 'operator.stok-opname.create');
        return view($viewPath, compact('ruanganList', 'operatorPelaksanaList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StokOpname::class);
        $loggedInUser = Auth::user();

        $validationRules = [
            'id_ruangan' => 'required|exists:ruangans,id',
            'tanggal_opname' => 'required|date|before_or_equal:today',
            'catatan' => 'nullable|string|max:1000',
        ];
        $customAttributes = [
            'id_ruangan' => 'Ruangan',
            'tanggal_opname' => 'Tanggal Opname',
            'id_operator_pelaksana' => 'Operator Pelaksana'
        ];

        if ($loggedInUser->hasRole(User::ROLE_ADMIN)) {
            $validationRules['id_operator_pelaksana'] = [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', User::ROLE_OPERATOR);
                })
            ];
        }

        try {
            $validated = $request->validate($validationRules, [], $customAttributes);

            $operatorIdToAssign = $loggedInUser->id;
            $assignedOperator = $loggedInUser;

            if ($loggedInUser->hasRole(User::ROLE_ADMIN) && !empty($validated['id_operator_pelaksana'])) {
                $pelaksana = User::find($validated['id_operator_pelaksana']);
                if (!$pelaksana || !$pelaksana->hasRole(User::ROLE_OPERATOR)) {
                    return redirect()->back()->withInput()
                        ->withErrors(['id_operator_pelaksana' => 'Operator pelaksana yang dipilih tidak valid atau bukan operator.']);
                }
                $operatorIdToAssign = $pelaksana->id;
                $assignedOperator = $pelaksana;
            }

            if ($loggedInUser->hasRole(User::ROLE_OPERATOR)) {
                if (!$loggedInUser->ruanganYangDiKelola()->where('id', $validated['id_ruangan'])->exists()) {
                    return redirect()->back()->with('error', 'Anda tidak diizinkan membuat stok opname untuk ruangan ini.')->withInput();
                }
            }

            DB::beginTransaction();
            $stokOpname = StokOpname::create([
                'id_ruangan' => $validated['id_ruangan'],
                'id_operator' => $operatorIdToAssign,
                'tanggal_opname' => $validated['tanggal_opname'],
                'catatan' => $validated['catatan'] ?? null,
                'status' => StokOpname::STATUS_DRAFT,
            ]);

            $barangDiRuangan = BarangQrCode::where('id_ruangan', $stokOpname->id_ruangan)
                ->whereNull('deleted_at')
                ->get();

            foreach ($barangDiRuangan as $barang) {
                DetailStokOpname::create([
                    'id_stok_opname' => $stokOpname->id,
                    'id_barang_qr_code' => $barang->id,
                    'kondisi_tercatat' => $barang->kondisi,
                    'kondisi_fisik' => null,
                    'catatan_fisik' => null,
                ]);
            }

            $deskripsiLog = "Membuat sesi Stok Opname untuk ruangan {$stokOpname->ruangan->nama_ruangan} (Pelaksana: {$assignedOperator->username}) oleh {$loggedInUser->username}.";
            if ($loggedInUser->id === $assignedOperator->id && $loggedInUser->hasRole(User::ROLE_ADMIN)) {
                $deskripsiLog = "Membuat sesi Stok Opname untuk ruangan {$stokOpname->ruangan->nama_ruangan} (dilaksanakan oleh Admin {$loggedInUser->username}).";
            }

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Buat Sesi Stok Opname',
                'deskripsi' => $deskripsiLog,
                'model_terkait' => StokOpname::class,
                'id_model_terkait' => $stokOpname->id,
                'data_baru' => $stokOpname->load('ruangan')->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();

            $pesanSukses = "Sesi stok opname untuk ruangan {$stokOpname->ruangan->nama_ruangan} berhasil dibuat.";
            $redirectRoute = $this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show');

            if ($loggedInUser->id !== $assignedOperator->id) {
                $pesanSukses .= " Ditugaskan kepada Operator {$assignedOperator->username}.";
                $redirectRoute = $this->getRedirectRouteName('stok-opname.index', 'admin.stok-opname.index');
                return redirect()->route($redirectRoute)->with('success', $pesanSukses);
            } else {
                $pesanSukses .= " Silakan mulai pemeriksaan fisik.";
                return redirect()->route($redirectRoute, $stokOpname->id)->with('success', $pesanSukses);
            }
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal membuat sesi stok opname: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal membuat sesi stok opname. Terjadi kesalahan.')->withInput();
        }
    }


    public function show($id): View
    {
        // Pastikan $id adalah integer atau string yang valid untuk findOrFail
        $stokOpname = StokOpname::withTrashed()->with([
            'ruangan',
            'operator',
            'detailStokOpname.barangQrCode' => function ($q) {
                $q->withTrashed()->with('barang.kategori');
            }
        ])->findOrFail($id);

        $this->authorize('view', $stokOpname);

        $kondisiFisikList = DetailStokOpname::getValidKondisiFisik();


        $rolePrefix = $this->getRolePrefix();

        return view('pages.stok-opname.show', compact(
            'stokOpname',
            'kondisiFisikList',
            'rolePrefix'
        ));
    }

    public function edit(StokOpname $stokOpname): View
    {
        $this->authorize('update', $stokOpname);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $operatorPelaksanaList = collect();
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $operatorPelaksanaList = User::where('role', User::ROLE_OPERATOR)
                ->orderBy('username')->get();
        }
        $viewPath = $this->getViewPathBasedOnRole('admin.stok-opname.edit', 'operator.stok-opname.edit');
        return view($viewPath, compact('stokOpname', 'operatorPelaksanaList'));
    }

    public function update(Request $request, StokOpname $stokOpname): RedirectResponse
    {
        $this->authorize('update', $stokOpname);
        if ($stokOpname->status !== StokOpname::STATUS_DRAFT) {
            return redirect()->route($this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show'), $stokOpname->id)
                ->with('error', 'Sesi stok opname yang sudah diproses tidak dapat diubah.');
        }

        $user = Auth::user();
        $validationRules = [
            'tanggal_opname' => 'required|date|before_or_equal:today',
            'catatan' => 'nullable|string|max:1000',
        ];
        $customAttributes = [
            'tanggal_opname' => 'Tanggal Opname',
            'id_operator_pelaksana' => 'Operator Pelaksana'
        ];

        $dataToUpdate = $request->only(['tanggal_opname', 'catatan']);
        $assignedOperator = $stokOpname->operator;
        $assignedOperatorUsername = optional($assignedOperator)->username ?? 'N/A';

        if ($user->hasRole(User::ROLE_ADMIN)) {
            $validationRules['id_operator_pelaksana'] = [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', User::ROLE_OPERATOR);
                })
            ];
        }

        try {
            $validated = $request->validate($validationRules, [], $customAttributes);

            if ($user->hasRole(User::ROLE_ADMIN)) {
                if ($request->filled('id_operator_pelaksana')) {
                    $pelaksana = User::find($validated['id_operator_pelaksana']);
                    if (!$pelaksana || !$pelaksana->hasRole(User::ROLE_OPERATOR)) {
                        return redirect()->back()->withInput()
                            ->withErrors(['id_operator_pelaksana' => 'Operator pelaksana yang dipilih tidak valid atau bukan operator.']);
                    }
                    $dataToUpdate['id_operator'] = $pelaksana->id;
                    $assignedOperatorUsername = $pelaksana->username;
                } elseif ($request->has('id_operator_pelaksana') && $request->id_operator_pelaksana === '') {
                    if ($stokOpname->id_operator !== $user->id) {
                        $dataToUpdate['id_operator'] = $user->id;
                        $assignedOperatorUsername = $user->username;
                    }
                }
            }

            DB::beginTransaction();
            $dataLama = $stokOpname->getOriginal();
            $stokOpname->update($dataToUpdate);
            $dataBaru = $stokOpname->refresh()->toArray();

            $deskripsiLog = "Memperbarui sesi Stok Opname untuk ruangan {$stokOpname->ruangan->nama_ruangan} tanggal {$stokOpname->tanggal_opname->format('d-m-Y')}.";
            if ($dataLama['id_operator'] != $stokOpname->id_operator) {
                $deskripsiLog .= " Operator pelaksana diubah menjadi {$assignedOperatorUsername}.";
            }

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Update Sesi Stok Opname',
                'deskripsi' => $deskripsiLog,
                'model_terkait' => StokOpname::class,
                'id_model_terkait' => $stokOpname->id,
                'data_lama' => $dataLama,
                'data_baru' => $dataBaru,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            return redirect()->route($this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show'), $stokOpname->id)
                ->with('success', 'Sesi stok opname berhasil diperbarui.');
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal update sesi stok opname {$stokOpname->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal memperbarui sesi stok opname.')->withInput();
        }
    }


    public function updateDetail(Request $request, StokOpname $stokOpname, DetailStokOpname $detail): JsonResponse
    {
        $this->authorize('processDetails', $stokOpname);
        if ($stokOpname->status !== StokOpname::STATUS_DRAFT) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat mengubah detail, stok opname sudah difinalisasi.'], 403);
        }
        if ($detail->id_stok_opname !== $stokOpname->id) {
            return response()->json(['success' => false, 'message' => 'Detail item tidak sesuai dengan sesi stok opname.'], 400);
        }

        $validated = $request->validate([
            'kondisi_fisik' => ['required', Rule::in(array_keys(DetailStokOpname::getValidKondisiFisik()))],
            'catatan_fisik' => 'nullable|string|max:500',
        ]);

        try {
            $detail->update($validated);
            return response()->json(['success' => true, 'message' => 'Detail item berhasil diperbarui.']);
        } catch (\Exception $e) {
            Log::error("Gagal update detail stok opname {$detail->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui detail item.'], 500);
        }
    }

    public function finalize(Request $request, StokOpname $stokOpname): RedirectResponse
    {
        $this->authorize('finalize', $stokOpname);
        if ($stokOpname->status !== StokOpname::STATUS_DRAFT) {
            return redirect()->route($this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show'), $stokOpname->id)
                ->with('error', 'Sesi stok opname ini sudah difinalisasi atau dibatalkan.');
        }

        $unfilledDetails = $stokOpname->detailStokOpname()->whereNull('kondisi_fisik')->count();
        if ($unfilledDetails > 0) {
            return redirect()->route($this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show'), $stokOpname->id)
                ->with('error', "Masih ada {$unfilledDetails} unit barang yang belum diisi kondisi fisiknya. Harap lengkapi semua item.");
        }

        try {
            DB::beginTransaction();
            $dataLama = $stokOpname->toArray();
            $stokOpname->status = StokOpname::STATUS_SELESAI;
            $stokOpname->save();
            $dataBaru = $stokOpname->refresh()->toArray();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Finalisasi Stok Opname',
                'deskripsi' => "Sesi Stok Opname untuk ruangan {$stokOpname->ruangan->nama_ruangan} tanggal {$stokOpname->tanggal_opname->format('d-m-Y')} telah difinalisasi.",
                'model_terkait' => StokOpname::class,
                'id_model_terkait' => $stokOpname->id,
                'data_lama' => $dataLama,
                'data_baru' => $dataBaru,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show');
            return redirect()->route($redirectRoute, $stokOpname->id)->with('success', 'Stok opname berhasil difinalisasi. Data barang telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal finalisasi stok opname {$stokOpname->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return redirect()->route($this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show'), $stokOpname->id)
                ->with('error', 'Gagal memfinalisasi stok opname. Terjadi kesalahan: ' . (config('app.debug') ? $e->getMessage() : 'Kesalahan Sistem'));
        }
    }

    public function cancel(Request $request, StokOpname $stokOpname): RedirectResponse
    {
        $this->authorize('cancel', $stokOpname);

        if ($stokOpname->status !== StokOpname::STATUS_DRAFT) {
            return redirect()->route($this->getRedirectRouteName('stok-opname.show', 'admin.stok-opname.show'), $stokOpname->id)
                ->with('error', 'Hanya sesi stok opname dengan status DRAFT yang dapat dibatalkan.');
        }

        DB::beginTransaction();
        try {
            // 1. Cari semua LOG PERUBAHAN yang dipicu oleh sesi SO ini
            $statusLogsToRevert = BarangStatus::where('deskripsi_kejadian', 'LIKE', "%Stok Opname ID: {$stokOpname->id}%")
                ->whereNotNull('id_ruangan_sebelumnya') // Filter hanya untuk barang temuan yang pindah lokasi
                ->orderBy('tanggal_pencatatan', 'desc')
                ->get();

            foreach ($statusLogsToRevert as $log) {
                $barangQr = $log->barangQrCode()->withTrashed()->first();
                if ($barangQr) {
                    // 2. Kembalikan barang ke LOKASI dan STATUS semula berdasarkan log
                    $barangQr->id_ruangan = $log->id_ruangan_sebelumnya;
                    $barangQr->id_pemegang_personal = $log->id_pemegang_personal_sebelumnya;
                    $barangQr->status = $log->status_ketersediaan_sebelumnya;

                    // Jika barang sebelumnya diarsipkan (trashed), arsipkan kembali
                    if ($log->status_ketersediaan_sebelumnya === BarangQrCode::STATUS_DIARSIPKAN) {
                        $barangQr->save(); // Simpan perubahan dulu
                        $barangQr->delete(); // Kemudian soft delete
                    } else {
                        $barangQr->save();
                    }
                }
            }

            // 3. Hapus semua detail pemeriksaan dari sesi SO yang dibatalkan ini
            $stokOpname->detailStokOpname()->delete();

            // 4. Baru ubah status sesi SO menjadi Dibatalkan
            $stokOpname->status = StokOpname::STATUS_DIBATALKAN;
            $stokOpname->save();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Batal Sesi Stok Opname',
                'deskripsi' => "Sesi Stok Opname untuk ruangan {$stokOpname->ruangan->nama_ruangan} tanggal {$stokOpname->tanggal_opname->format('d-m-Y')} telah dibatalkan dan semua perubahan dikembalikan.",
                'model_terkait' => StokOpname::class,
                'id_model_terkait' => $stokOpname->id,
            ]);

            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('stok-opname.index', 'admin.stok-opname.index');
            return redirect()->route($redirectRoute)->with('success', 'Sesi stok opname berhasil dibatalkan dan semua perubahan telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal membatalkan stok opname {$stokOpname->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal membatalkan sesi stok opname.');
        }
    }

    public function destroy(Request $request, StokOpname $stokOpname): RedirectResponse
    {
        $this->authorize('delete', $stokOpname);
        try {
            DB::beginTransaction();
            $namaSesi = "SO Ruangan " . optional($stokOpname->ruangan)->nama_ruangan . " Tgl " . Carbon::parse($stokOpname->tanggal_opname)->isoFormat('DD/MM/YY');
            $dataLama = $stokOpname->toArray();
            $stokOpname->delete();
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Arsip Sesi Stok Opname',
                'deskripsi' => "Sesi Stok Opname '{$namaSesi}' (ID: {$dataLama['id']}) berhasil diarsipkan.",
                'model_terkait' => StokOpname::class,
                'id_model_terkait' => $dataLama['id'],
                'data_lama' => $dataLama,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('stok-opname.index', 'admin.stok-opname.index');
            return redirect()->route($redirectRoute)->with('success', "Sesi stok opname '{$namaSesi}' berhasil diarsipkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menghapus stok opname {$stokOpname->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal mengarsipkan sesi stok opname.');
        }
    }

    public function restore(Request $request, $id): RedirectResponse
    {
        $stokOpname = StokOpname::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $stokOpname);
        try {
            DB::beginTransaction();
            $stokOpname->restore();
            $dataBaru = $stokOpname->refresh()->toArray();
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Pulihkan Sesi Stok Opname',
                'deskripsi' => "Sesi Stok Opname ID: {$stokOpname->id} untuk ruangan {$stokOpname->ruangan->nama_ruangan} tanggal {$stokOpname->tanggal_opname->format('d-m-Y')} berhasil dipulihkan.",
                'model_terkait' => StokOpname::class,
                'id_model_terkait' => $stokOpname->id,
                'data_baru' => $dataBaru,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('stok-opname.index', 'admin.stok-opname.index');
            return redirect()->route($redirectRoute, ['status_arsip' => 'arsip'])->with('success', 'Sesi stok opname berhasil dipulihkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan stok opname {$id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal memulihkan sesi stok opname.');
        }
    }

    public function searchBarangQr(Request $request): JsonResponse
    {
        $term = $request->input('q');
        $idStokOpname = $request->input('id_stok_opname');

        if (empty($term) || !$idStokOpname) {
            return response()->json(['items' => []]);
        }

        $stokOpname = StokOpname::find($idStokOpname);
        if (!$stokOpname) {
            return response()->json(['items' => [], 'message' => 'Sesi Stok Opname tidak valid.'], 400);
        }

        $this->authorize('view', $stokOpname);

        $existingDetailBarangQrIds = DetailStokOpname::where('id_stok_opname', $idStokOpname)
            ->pluck('id_barang_qr_code')
            ->all();

        $query = BarangQrCode::withTrashed()
            ->with(['barang:id,nama_barang', 'ruangan:id,nama_ruangan', 'pemegangPersonal:id,username'])
            ->where(function ($q) use ($term) {
                $q->where('kode_inventaris_sekolah', 'LIKE', "%{$term}%")
                    ->orWhere('no_seri_pabrik', 'LIKE', "%{$term}%")
                    // TAMBAHKAN BARIS INI untuk mencari di nama barang induk
                    ->orWhereHas('barang', function ($q_barang) use ($term) {
                        $q_barang->where('nama_barang', 'LIKE', "%{$term}%");
                    });
            })
            ->whereNotIn('id', $existingDetailBarangQrIds)
            ->limit(15);

        $items = $query->get()->map(function ($item) {
            // Pastikan $item->barang tidak null sebelum mengakses ->nama_barang
            $nama_barang_induk = optional($item->barang)->nama_barang ?? 'N/A (Barang Induk Hilang)';
            $ruangan_saat_ini = optional($item->ruangan)->nama_ruangan;
            $pemegang_saat_ini = optional($item->pemegangPersonal)->username;
            $tanggal_perolehan_raw = $item->tanggal_perolehan_unit ? Carbon::parse($item->tanggal_perolehan_unit)->format('Y-m-d') : null;


            return [
                'id' => $item->id,
                'kode_inventaris_sekolah' => $item->kode_inventaris_sekolah,
                'no_seri_pabrik' => $item->no_seri_pabrik,
                'nama_barang_induk' => $nama_barang_induk,
                'ruangan_saat_ini' => $ruangan_saat_ini,
                'pemegang_saat_ini' => $pemegang_saat_ini,
                'deleted_at' => $item->deleted_at ? $item->deleted_at->toIso8601String() : null,
                'kondisi_saat_ini' => $item->kondisi,
                'harga_perolehan_unit' => $item->harga_perolehan_unit,
                'tanggal_perolehan_unit_raw' => $tanggal_perolehan_raw,
            ];
        });

        return response()->json(['items' => $items]);
    }

    public function addBarangTemuan(Request $request): JsonResponse
    {
        $stokOpname = StokOpname::find($request->input('id_stok_opname'));
        if (!$stokOpname) {
            return response()->json(['success' => false, 'message' => 'Sesi Stok Opname tidak valid.'], 400);
        }
        $this->authorize('processDetails', $stokOpname);

        if ($stokOpname->status !== StokOpname::STATUS_DRAFT) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menambah barang, sesi stok opname sudah diproses.'], 403);
        }

        $validated = $request->validate([
            'id_stok_opname' => 'required|exists:stok_opname,id',
            'id_barang_qr_code_existing' => 'nullable|exists:barang_qr_codes,id',
            'id_barang_induk_new' => 'nullable|required_without:id_barang_qr_code_existing|exists:barangs,id',
            'no_seri_pabrik_new' => ['nullable', 'string', 'max:255', Rule::unique('barang_qr_codes', 'no_seri_pabrik')->whereNull('deleted_at')],
            'harga_perolehan_unit_new' => 'nullable|required_with:id_barang_induk_new|numeric|min:0',
            'tanggal_perolehan_unit_new' => 'nullable|required_with:id_barang_induk_new|date|before_or_equal:today',
            'kondisi_fisik_temuan' => ['required', Rule::in(array_keys(DetailStokOpname::getValidKondisiFisik()))],
            'catatan_fisik_temuan' => 'nullable|string|max:500',
        ], [
            'id_barang_induk_new.required_without' => 'Jenis barang induk wajib dipilih jika menambahkan unit baru.',
            'harga_perolehan_unit_new.required_with' => 'Harga perolehan wajib diisi untuk unit baru.',
            'tanggal_perolehan_unit_new.required_with' => 'Tanggal perolehan wajib diisi untuk unit baru.',
            'no_seri_pabrik_new.unique' => 'Nomor seri pabrik untuk unit baru sudah ada.',
            'kondisi_fisik_temuan.required' => 'Kondisi fisik barang temuan wajib diisi.'
        ]);

        DB::beginTransaction();
        try {
            $barangQrCode = null;
            $isNewUnitCreated = false;
            $kondisiFisikTemuan = $validated['kondisi_fisik_temuan'];

            if (!empty($validated['id_barang_qr_code_existing'])) {
                $barangQrCode = BarangQrCode::withTrashed()->find($validated['id_barang_qr_code_existing']);
                if (!$barangQrCode) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Barang yang dipilih tidak ditemukan.'], 404);
                }

                if (DetailStokOpname::where('id_stok_opname', $stokOpname->id)->where('id_barang_qr_code', $barangQrCode->id)->exists()) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Barang ini sudah ada dalam daftar pemeriksaan sesi ini.'], 422);
                }

                $kondisiSebelum = $barangQrCode->kondisi;
                $statusSebelum = $barangQrCode->status;
                $ruanganSebelum = $barangQrCode->id_ruangan;
                $pemegangSebelum = $barangQrCode->id_pemegang_personal;
                $isTrashedSebelum = $barangQrCode->trashed();

                if ($barangQrCode->trashed()) {
                    $barangQrCode->restore();
                    $arsip = ArsipBarang::where('id_barang_qr_code', $barangQrCode->id)
                        ->where('status_arsip', '!=', ArsipBarang::STATUS_ARSIP_DIPULIHKAN)
                        ->first();
                    if ($arsip) {
                        $arsip->status_arsip = ArsipBarang::STATUS_ARSIP_DIPULIHKAN;
                        $arsip->tanggal_dipulihkan = now();
                        $arsip->dipulihkan_oleh = Auth::id();
                        $arsip->save();
                    }
                }
                $barangQrCode->id_ruangan = $stokOpname->id_ruangan;
                $barangQrCode->id_pemegang_personal = null;
                $kondisiBarangBaru = ($kondisiFisikTemuan === DetailStokOpname::KONDISI_DITEMUKAN)
                    ? BarangQrCode::KONDISI_BAIK
                    : $kondisiFisikTemuan;
                // Pastikan kondisiBaru adalah salah satu dari enum BarangQrCode
                if (!in_array($kondisiBarangBaru, BarangQrCode::getValidKondisi())) {
                    $kondisiBarangBaru = BarangQrCode::KONDISI_BAIK; // Fallback
                }
                $barangQrCode->kondisi = $kondisiBarangBaru;
                $barangQrCode->status = BarangQrCode::STATUS_TERSEDIA;
                $barangQrCode->save();

                $barangStatusLog = BarangStatus::create([
                    'id_barang_qr_code' => $barangQrCode->id,
                    'id_user_pencatat' => Auth::id(),
                    'tanggal_pencatatan' => now(),
                    'kondisi_sebelumnya' => $isTrashedSebelum ? null : $kondisiSebelum,
                    'kondisi_sesudahnya' => $barangQrCode->kondisi,
                    'status_ketersediaan_sebelumnya' => $isTrashedSebelum ? BarangQrCode::STATUS_DIARSIPKAN : $statusSebelum,
                    'status_ketersediaan_sesudahnya' => $barangQrCode->status,
                    'id_ruangan_sebelumnya' => $ruanganSebelum,
                    'id_ruangan_sesudahnya' => $barangQrCode->id_ruangan,
                    'id_pemegang_personal_sebelumnya' => $pemegangSebelum,
                    'id_pemegang_personal_sesudahnya' => $barangQrCode->id_pemegang_personal,
                    'deskripsi_kejadian' => "Barang ditemukan saat Stok Opname ID: {$stokOpname->id} di ruangan {$stokOpname->ruangan->nama_ruangan}.",
                ]);
            } elseif (!empty($validated['id_barang_induk_new'])) {
                $barangInduk = Barang::find($validated['id_barang_induk_new']);

                $noSeriPabrik = null;
                if ($barangInduk->menggunakan_nomor_seri && !empty($validated['no_seri_pabrik_new'])) {
                    $noSeriPabrik = $validated['no_seri_pabrik_new'];
                } elseif ($barangInduk->menggunakan_nomor_seri && empty($validated['no_seri_pabrik_new'])) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Nomor seri pabrik wajib diisi untuk jenis barang ini.'], 422);
                }
                $kondisiBarangBaruInput = ($kondisiFisikTemuan === DetailStokOpname::KONDISI_DITEMUKAN)
                    ? BarangQrCode::KONDISI_BAIK
                    : $kondisiFisikTemuan;
                if (!in_array($kondisiBarangBaruInput, BarangQrCode::getValidKondisi())) {
                    $kondisiBarangBaruInput = BarangQrCode::KONDISI_BAIK; // Fallback
                }


                $barangQrCode = BarangQrCode::createWithQrCodeImage(
                    idBarang: $barangInduk->id,
                    idRuangan: $stokOpname->id_ruangan,
                    noSeriPabrik: $noSeriPabrik,
                    hargaPerolehanUnit: $validated['harga_perolehan_unit_new'],
                    tanggalPerolehanUnit: $validated['tanggal_perolehan_unit_new'],
                    kondisi: $kondisiBarangBaruInput,
                    status: BarangQrCode::STATUS_TERSEDIA,
                    deskripsiUnit: "Barang temuan saat Stok Opname ID: {$stokOpname->id}",
                    idPemegangPersonal: null,
                    idPemegangPencatat: Auth::id()
                );
                $isNewUnitCreated = true;
            } else {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Pilih barang yang sudah ada atau tambahkan sebagai unit baru.'], 400);
            }

            $detailSo = DetailStokOpname::create([
                'id_stok_opname' => $stokOpname->id,
                'id_barang_qr_code' => $barangQrCode->id,
                'kondisi_tercatat' => $isNewUnitCreated ? 'Baru' : ($barangQrCode->getOriginal('kondisi') ?? $barangQrCode->kondisi),
                'kondisi_fisik' => $kondisiFisikTemuan,
                'catatan_fisik' => $validated['catatan_fisik_temuan'],
            ]);

            // Update trigger di BarangStatus jika barang existing ditemukan dan log sudah dibuat
            if (!$isNewUnitCreated && isset($barangStatusLog)) {
                $barangStatusLog->id_detail_stok_opname_trigger = $detailSo->id;
                $barangStatusLog->save();
            }


            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Tambah Barang Temuan ke Stok Opname',
                'deskripsi' => "Menambahkan barang temuan: {$barangQrCode->kode_inventaris_sekolah} ke Sesi SO ID: {$stokOpname->id}",
                'model_terkait' => DetailStokOpname::class,
                'id_model_terkait' => $detailSo->id,
                'data_baru' => $detailSo->load('barangQrCode.barang')->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            $kondisiFisikList = DetailStokOpname::getValidKondisiFisik();
            $rolePrefix = $this->getRolePrefix();
            $newRowHtml = view('pages.stok-opname._item_detail_row', [
                'detail' => $detailSo->load(['barangQrCode.barang', 'barangQrCode.ruangan', 'barangQrCode.pemegangPersonal']),
                'index' => $stokOpname->detailStokOpname()->count(),
                'stokOpname' => $stokOpname,
                'kondisiFisikList' => $kondisiFisikList,
                'rolePrefix' => $rolePrefix
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Barang temuan berhasil ditambahkan ke sesi opname.',
                'detail_html' => $newRowHtml,
                'new_detail_id' => $detailSo->id
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Data tidak valid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal tambah barang temuan ke SO ID {$stokOpname->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan barang temuan. Terjadi kesalahan sistem: ' . (config('app.debug') ? $e->getMessage() : 'Silakan coba lagi.')], 500);
        }
    }
    // Metode helper getViewPathBasedOnRole
    private function getViewPathBasedOnRole(string $adminView, string $operatorView, ?string $guruView = null): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return $adminView;
        $viewPath = $adminView; // Default ke admin view
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $viewPath = view()->exists($operatorView) ? $operatorView : $adminView;
        } elseif ($user->hasRole(User::ROLE_GURU) && $guruView) { // Jika ada view spesifik untuk guru
            $viewPath = view()->exists($guruView) ? $guruView : $adminView;
        }

        // Fallback jika view spesifik peran tidak ada, tapi view admin utama harus ada
        if (!view()->exists($viewPath)) {
            Log::warning("View path tidak ditemukan: Target='{$viewPath}', Fallback ke Admin='{$adminView}'.");
            if (!view()->exists($adminView)) {
                // Ini adalah error kritis jika view admin utama juga tidak ada
                Log::critical("View utama admin tidak ditemukan: {$adminView}. Harap periksa konfigurasi view paths.");
                abort(500, "View utama {$adminView} tidak ditemukan.");
            }
            return $adminView; // Fallback ke admin view jika view spesifik peran tidak ada
        }
        return $viewPath;
    }

    // ==========================================================
    // HELPER METHODS
    // ==========================================================

    private function getRolePrefix(): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return '';

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return 'admin.';
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            return 'operator.';
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            return 'guru.';
        }
        return '';
    }
}
