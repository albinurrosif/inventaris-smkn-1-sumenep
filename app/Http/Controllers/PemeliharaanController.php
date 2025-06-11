<?php

namespace App\Http\Controllers;

use App\Models\Pemeliharaan;
use App\Models\BarangQrCode;
use App\Models\User;
use App\Models\LogAktivitas;
// use App\Models\BarangStatus; // Dikomentari jika logika utama di model event
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PemeliharaanController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Pemeliharaan::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $searchTerm = $request->input('search');
        $statusFilter = $request->input('status_pemeliharaan');
        $prioritasFilter = $request->input('prioritas');
        $picFilter = $request->input('id_user_bertanggung_jawab');
        $pelaporFilter = $request->input('id_user_pelapor');
        $tanggalMulai = $request->input('tanggal_mulai_lapor');
        $tanggalSelesai = $request->input('tanggal_selesai_lapor');
        $statusArsipFilter = $request->input('status_arsip', 'aktif');

        $query = Pemeliharaan::query();

        if ($statusArsipFilter === 'arsip') {
            $query->onlyTrashed();
        } elseif ($statusArsipFilter === 'semua') {
            $query->withTrashed();
        }

        $query->with([
            'barangQrCode' => function ($q) {
                $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']);
            },
            'pengaju',
            'penyetuju',
            'operatorPengerjaan'
        ]);

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = optional($user->ruanganYangDiKelola())->pluck('id') ?? collect();
            $query->where(function ($qSub) use ($user, $ruanganOperatorIds) {
                $qSub->where('id_user_pengaju', $user->id)
                    ->orWhere('id_operator_pengerjaan', $user->id);
                if ($ruanganOperatorIds->isNotEmpty()) {
                    $qSub->orWhereHas('barangQrCode', function ($qUnit) use ($ruanganOperatorIds, $user) {
                        $qUnit->where(function ($qUnitDetail) use ($ruanganOperatorIds, $user) {
                            $qUnitDetail->whereIn('id_ruangan', $ruanganOperatorIds)
                                ->orWhere('id_pemegang_personal', $user->id);
                        });
                    });
                } elseif ($user->id_pemegang_personal) { // Jika tidak kelola ruangan tapi pegang personal
                    $qSub->orWhereHas('barangQrCode', function ($qUnit) use ($user) {
                        $qUnit->where('id_pemegang_personal', $user->id);
                    });
                }
            });
        }


        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('deskripsi_kerusakan', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('catatan_pengajuan', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('deskripsi_pekerjaan', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('catatan_pengerjaan', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('barangQrCode', function ($qQr) use ($searchTerm) {
                        $qQr->where('kode_inventaris_sekolah', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('no_seri_pabrik', 'LIKE', "%{$searchTerm}%")
                            ->orWhereHas('barang', function ($qBarang) use ($searchTerm) {
                                $qBarang->where('nama_barang', 'LIKE', "%{$searchTerm}%");
                            });
                    });
            });
        }

        if ($statusFilter) {
            $allValidStatuses = Pemeliharaan::getStatusListForFilter();
            if (array_key_exists($statusFilter, $allValidStatuses)) {
                if (array_key_exists($statusFilter, Pemeliharaan::getValidStatusPengajuan())) {
                    $query->where('status_pengajuan', $statusFilter);
                } elseif (array_key_exists($statusFilter, Pemeliharaan::getValidStatusPengerjaan())) {
                    $query->where('status_pengerjaan', $statusFilter);
                }
            }
        }

        if ($prioritasFilter) {
            $query->where('prioritas', $prioritasFilter);
        }

        if ($picFilter) {
            $query->where('id_operator_pengerjaan', $picFilter);
        }

        if ($pelaporFilter) {
            if ($user->hasRole(User::ROLE_ADMIN) || ($user->hasRole(User::ROLE_OPERATOR) && $user->id == $pelaporFilter)) {
                $query->where('id_user_pengaju', $pelaporFilter);
            } elseif (!$user->hasRole(User::ROLE_ADMIN) && !$user->hasRole(User::ROLE_OPERATOR) && $user->id == $pelaporFilter) {
                $query->where('id_user_pengaju', $pelaporFilter);
            }
        }

        if ($tanggalMulai) {
            $query->whereDate('tanggal_pengajuan', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $query->whereDate('tanggal_pengajuan', '<=', $tanggalSelesai);
        }

        $pemeliharaanList = $query->latest('tanggal_pengajuan')->latest('id')->paginate(15)->withQueryString();

        $usersList = User::orderBy('username')->get();
        $statusPemeliharaanList = Pemeliharaan::getStatusListForFilter();
        $prioritasList = Pemeliharaan::getValidPrioritas();

        return view('pages.pemeliharaan.index', compact(
            'pemeliharaanList',
            'usersList',
            'statusPemeliharaanList',
            'prioritasList',
            'request',
            'searchTerm',
            'statusFilter',
            'prioritasFilter',
            'picFilter',
            'pelaporFilter',
            'tanggalMulai',
            'tanggalSelesai',
            'statusArsipFilter'
        ));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Pemeliharaan::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $barangQrCodeId = $request->query('id_barang_qr_code');
        $barangQrCode = null;
        $barangQrOptions = collect();
        $error = null;

        if ($barangQrCodeId) {
            $barangQrCode = BarangQrCode::with('barang.kategori', 'ruangan', 'pemegangPersonal')->find($barangQrCodeId);
            if (!$barangQrCode || $barangQrCode->trashed() || in_array($barangQrCode->kondisi, [BarangQrCode::KONDISI_HILANG]) || $barangQrCode->status === BarangQrCode::STATUS_DIPINJAM) {
                $error = 'Unit barang tidak valid, terarsip, hilang, atau sedang dipinjam sehingga tidak dapat dilaporkan untuk pemeliharaan.';
            }
            if ($user->hasRole(User::ROLE_OPERATOR) && $barangQrCode && !$error) {
                $isAllowed = optional($user->ruanganYangDiKelola())->where('id', $barangQrCode->id_ruangan)->exists() ||
                    $barangQrCode->id_pemegang_personal === $user->id;
                if (!$isAllowed) {
                    $targetRoute = $this->getRedirectRouteName('pemeliharaan.index', 'admin.pemeliharaan.index');
                    $error = 'Anda tidak diizinkan membuat laporan pemeliharaan untuk unit barang ini.';
                }
            } elseif ($user->hasRole(User::ROLE_GURU) && $barangQrCode && !$error) {
                $isAllowed = $barangQrCode->id_pemegang_personal === $user->id;
                if (!$isAllowed) {
                    $error = 'Guru hanya bisa membuat laporan untuk barang yang dipegang secara personal.';
                }
            }
        } else {
            $baseQuery = BarangQrCode::whereNull('deleted_at')
                ->whereNotIn('kondisi', [BarangQrCode::KONDISI_HILANG])
                ->where('status', '!=', BarangQrCode::STATUS_DIPINJAM)
                ->with('barang', 'ruangan', 'pemegangPersonal');

            if ($user->hasRole(User::ROLE_OPERATOR)) {
                $ruanganOperatorIds = optional($user->ruanganYangDiKelola())->pluck('id') ?? collect();
                $baseQuery->where(function ($q) use ($ruanganOperatorIds, $user) {
                    if ($ruanganOperatorIds->isNotEmpty()) {
                        $q->whereIn('id_ruangan', $ruanganOperatorIds);
                    }
                    $q->orWhere('id_pemegang_personal', $user->id);
                });
            } elseif ($user->hasRole(User::ROLE_GURU)) {
                $baseQuery->where('id_pemegang_personal', $user->id);
            }
            // Untuk Admin, semua barang ditampilkan (tidak ada filter tambahan)

            $barangQrOptions = $baseQuery->orderBy('id_barang')->orderBy('kode_inventaris_sekolah')
                ->get()
                ->map(function ($item) {
                    $ruanganText = $item->ruangan ? " - Ruang: " . $item->ruangan->nama_ruangan : "";
                    $pemegangText = $item->pemegangPersonal ? " - Dipegang: " . $item->pemegangPersonal->username : "";
                    return ['id' => $item->id, 'text' => "{$item->barang->nama_barang} ({$item->kode_inventaris_sekolah}){$ruanganText}{$pemegangText} - Kondisi: {$item->kondisi}"];
                });
        }

        $prioritasOptions = Pemeliharaan::getValidPrioritas();

        $viewPath = $this->getViewPathBasedOnRole('admin.pemeliharaan.create', 'operator.pemeliharaan.create', 'guru.pemeliharaan.create');
        return view($viewPath, compact('barangQrCode', 'barangQrOptions', 'error', 'prioritasOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Pemeliharaan::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $validated = $request->validate([
            'id_barang_qr_code' => 'required|exists:barang_qr_codes,id',
            'tanggal_pengajuan' => 'required|date|before_or_equal:today',
            'catatan_pengajuan' => 'required|string|max:1000',
            'prioritas' => ['required', Rule::in(array_keys(Pemeliharaan::getValidPrioritas()))],
            // Validasi untuk foto kerusakan
            'foto_kerusakan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
        ]);

        $barangQr = BarangQrCode::find($validated['id_barang_qr_code']);
        if (!$barangQr || $barangQr->trashed() || in_array($barangQr->kondisi, [BarangQrCode::KONDISI_HILANG]) || $barangQr->status === BarangQrCode::STATUS_DIPINJAM) {
            return redirect()->back()->with('error', 'Unit barang tidak valid atau tidak dalam status/kondisi yang memungkinkan untuk dilaporkan pemeliharaan.')->withInput();
        }
        // Otorisasi tambahan berdasarkan peran
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $isAllowed = optional($user->ruanganYangDiKelola())->where('id', $barangQr->id_ruangan)->exists() || $barangQr->id_pemegang_personal === $user->id;
            if (!$isAllowed) return redirect()->back()->with('error', 'Operator tidak diizinkan membuat laporan untuk unit barang ini.')->withInput();
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            if ($barangQr->id_pemegang_personal !== $user->id) return redirect()->back()->with('error', 'Guru hanya bisa membuat laporan untuk barang yang dipegang secara personal.')->withInput();
        }


        try {
            DB::beginTransaction();
            // Handle upload file
            $pathKerusakan = null;
            if ($request->hasFile('foto_kerusakan')) {
                $pathKerusakan = $request->file('foto_kerusakan')->store('pemeliharaan/kerusakan', 'public');
            }

            $pemeliharaan = new Pemeliharaan();
            $pemeliharaan->fill($validated);
            $pemeliharaan->id_user_pengaju = $user->id;
            $pemeliharaan->foto_kerusakan_path = $pathKerusakan; // Simpan path
            $pemeliharaan->save();

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Pengajuan Pemeliharaan',
                'deskripsi' => "Pengajuan pemeliharaan untuk unit {$barangQr->kode_inventaris_sekolah}: " . Str::limit($pemeliharaan->deskripsi_kerusakan, 150),
                'model_terkait' => Pemeliharaan::class,
                'id_model_terkait' => $pemeliharaan->id,
                'data_baru' => $pemeliharaan->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('pemeliharaan.index', 'admin.pemeliharaan.index');
            return redirect()->route($redirectRoute)->with('success', 'Laporan pemeliharaan berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan laporan pemeliharaan: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal mengajukan laporan pemeliharaan. Kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id): View
    {
        $pemeliharaan = Pemeliharaan::withTrashed()->with([
            'barangQrCode' => function ($q) {
                $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']);
            },
            'pengaju',
            'penyetuju',
            'operatorPengerjaan'
        ])->findOrFail($id);
        $this->authorize('view', $pemeliharaan);

        $viewPath = $this->getViewPathBasedOnRole('admin.pemeliharaan.show', 'operator.pemeliharaan.show', 'guru.pemeliharaan.show');
        return view($viewPath, compact('pemeliharaan'));
    }

    public function edit(Pemeliharaan $pemeliharaan): View
    {
        $this->authorize('update', $pemeliharaan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $pemeliharaan->load([
            'barangQrCode' => fn($q) => $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']),
            'pengaju',
            'penyetuju',
            'operatorPengerjaan'
        ]);
        $barangQrCode = $pemeliharaan->barangQrCode;

        // Otorisasi tambahan
        if ($user->hasRole(User::ROLE_OPERATOR) && $user->id !== $pemeliharaan->id_user_pengaju && $user->id !== $pemeliharaan->id_operator_pengerjaan) {
            if (!($barangQrCode && (optional($user->ruanganYangDiKelola())->where('id', $barangQrCode->id_ruangan)->exists() || $barangQrCode->id_pemegang_personal === $user->id))) {
                abort(403, 'Operator tidak diizinkan mengedit laporan pemeliharaan ini.');
            }
        } elseif ($user->hasRole(User::ROLE_GURU) && $user->id !== $pemeliharaan->id_user_pengaju) {
            abort(403, 'Guru tidak diizinkan mengedit laporan pemeliharaan ini.');
        }

        $picList = collect();
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $picList = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->orderBy('username')->get();
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            if ($pemeliharaan->id_operator_pengerjaan === null || $pemeliharaan->id_operator_pengerjaan === $user->id) {
                $picList = User::where('id', $user->id)->get(); // Hanya bisa pilih diri sendiri
            } else { // Jika PIC sudah orang lain (di-assign Admin), Operator tidak bisa ubah PIC
                $currentPic = User::find($pemeliharaan->id_operator_pengerjaan);
                if ($currentPic) $picList->push($currentPic);
            }
        }

        $statusPengajuanList = Pemeliharaan::getValidStatusPengajuan();
        $statusPengerjaanList = Pemeliharaan::getValidStatusPengerjaan();
        $kondisiBarangList = BarangQrCode::getValidKondisi();
        $prioritasOptions = Pemeliharaan::getValidPrioritas();

        $viewPath = $this->getViewPathBasedOnRole('admin.pemeliharaan.edit', 'operator.pemeliharaan.edit', 'guru.pemeliharaan.edit');
        return view($viewPath, compact(
            'pemeliharaan',
            'barangQrCode',
            'picList',
            'statusPengajuanList',
            'statusPengerjaanList',
            'kondisiBarangList',
            'prioritasOptions'
        ));
    }

    public function update(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    {
        $this->authorize('update', $pemeliharaan);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $rules = [];
        // Aturan validasi dasar yang bisa diisi oleh pengaju (jika status masih diajukan)
        if ($user->id === $pemeliharaan->id_user_pengaju && $pemeliharaan->status_pengajuan === Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN) {
            $rules['deskripsi_kerusakan'] = 'required|string|max:1000';
            $rules['prioritas'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidPrioritas()))];
            $rules['catatan_pengajuan'] = 'nullable|string|max:1000';
        }

        // Aturan untuk Operator PIC jika pengajuan sudah disetujui
        if (($user->hasRole(User::ROLE_OPERATOR) && $user->id === $pemeliharaan->id_operator_pengerjaan) || $user->hasRole(User::ROLE_ADMIN)) {
            if ($pemeliharaan->status_pengajuan === Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI) {
                $rules['status_pengerjaan'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidStatusPengerjaan()))];
                if (in_array($request->input('status_pengerjaan'), [Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN, Pemeliharaan::STATUS_PENGERJAAN_SELESAI, Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI, Pemeliharaan::STATUS_PENGERJAAN_GAGAL, Pemeliharaan::STATUS_PENGERJAAN_DITUNDA])) {
                    $rules['tanggal_mulai_pengerjaan'] = 'required|date|before_or_equal:today';
                } else {
                    $rules['tanggal_mulai_pengerjaan'] = 'nullable|date|before_or_equal:today';
                }

                if (in_array($request->input('status_pengerjaan'), [Pemeliharaan::STATUS_PENGERJAAN_SELESAI, Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI, Pemeliharaan::STATUS_PENGERJAAN_GAGAL])) {
                    $rules['tanggal_selesai_pengerjaan'] = 'required|date|after_or_equal:tanggal_mulai_pengerjaan|before_or_equal:today';
                    $rules['kondisi_barang_setelah_pemeliharaan'] = ['required', Rule::in(array_keys(BarangQrCode::getValidKondisi()))];
                    $rules['hasil_pemeliharaan'] = 'required|string|max:1000';
                } else {
                    $rules['tanggal_selesai_pengerjaan'] = 'nullable|date|after_or_equal:tanggal_mulai_pengerjaan|before_or_equal:today';
                    $rules['kondisi_barang_setelah_pemeliharaan'] = ['nullable', Rule::in(array_keys(BarangQrCode::getValidKondisi()))];
                    $rules['hasil_pemeliharaan'] = 'nullable|string|max:1000';
                }
                $rules['deskripsi_pekerjaan'] = 'nullable|string|max:1000';
                $rules['biaya'] = 'nullable|numeric|min:0';
                $rules['catatan_pengerjaan'] = 'nullable|string|max:1000';
            }
        }

        if ($user->hasRole(User::ROLE_ADMIN)) { // Admin bisa override dan tambah rules
            $adminRules = [
                'deskripsi_kerusakan' => 'required|string|max:1000',
                'prioritas' => ['required', Rule::in(array_keys(Pemeliharaan::getValidPrioritas()))],
                'catatan_pengajuan' => 'nullable|string|max:1000',
                'status_pengajuan' => ['required', Rule::in(array_keys(Pemeliharaan::getValidStatusPengajuan()))],
                'catatan_persetujuan' => 'nullable|string|max:1000',
                'id_operator_pengerjaan' => 'nullable|exists:users,id',
            ];
            $rules = array_merge($rules, $adminRules); // Timpa rules yang mungkin sudah ada dengan rules admin

            if (in_array($request->input('status_pengajuan'), [Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI, Pemeliharaan::STATUS_PENGAJUAN_DITOLAK])) {
                $rules['tanggal_persetujuan'] = 'required|date|before_or_equal:today';
            } else {
                $rules['tanggal_persetujuan'] = 'nullable|date';
            }
        }

        if (empty($rules)) { // Jika tidak ada rule terdefinisi, berarti user tidak punya hak update
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk memperbarui data pada tahap ini atau untuk laporan ini.')->withInput();
        }
        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();
            $dataLamaPemeliharaan = $pemeliharaan->getAttributes();
          // Handle upload/update foto kerusakan
          if ($request->hasFile('foto_kerusakan')) {
            // Hapus file lama jika ada
            if ($pemeliharaan->foto_kerusakan_path) {
                Storage::disk('public')->delete($pemeliharaan->foto_kerusakan_path);
            }
            $validated['foto_kerusakan_path'] = $request->file('foto_kerusakan')->store('pemeliharaan/kerusakan', 'public');
        }

        // Handle upload/update foto perbaikan
        if ($request->hasFile('foto_perbaikan')) {
             // Hapus file lama jika ada
            if ($pemeliharaan->foto_perbaikan_path) {
                Storage::disk('public')->delete($pemeliharaan->foto_perbaikan_path);
            }
            $validated['foto_perbaikan_path'] = $request->file('foto_perbaikan')->store('pemeliharaan/perbaikan', 'public');
        }

        $pemeliharaan->fill($validated);


            if ($user->hasRole(User::ROLE_ADMIN)) {
                if (in_array($pemeliharaan->status_pengajuan, [Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI, Pemeliharaan::STATUS_PENGAJUAN_DITOLAK]) && $pemeliharaan->isDirty('status_pengajuan')) {
                    if (empty($pemeliharaan->id_user_penyetuju) || Auth::id() != $pemeliharaan->id_user_penyetuju) {
                        $pemeliharaan->id_user_penyetuju = Auth::id();
                    }
                    if (empty($pemeliharaan->tanggal_persetujuan) || ($validated['tanggal_persetujuan'] ?? null) !== $pemeliharaan->getOriginal('tanggal_persetujuan')) {
                        $pemeliharaan->tanggal_persetujuan = $validated['tanggal_persetujuan'] ?? now();
                    }
                } elseif ($pemeliharaan->status_pengajuan === Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN && $pemeliharaan->isDirty('status_pengajuan')) {
                    $pemeliharaan->id_user_penyetuju = null;
                    $pemeliharaan->tanggal_persetujuan = null;
                    $pemeliharaan->catatan_persetujuan = null;
                }
                // Admin bisa assign/ubah PIC
                if (isset($validated['id_operator_pengerjaan'])) { // Hanya update jika memang dikirim dari form Admin
                    $pemeliharaan->id_operator_pengerjaan = $validated['id_operator_pengerjaan'];
                }
            } elseif ($user->hasRole(User::ROLE_OPERATOR) && $user->id === $pemeliharaan->id_operator_pengerjaan) {
                // Jika Operator PIC yang update dan tanggal mulai/selesai pengerjaan belum diisi, set otomatis
                if ($pemeliharaan->isDirty('status_pengerjaan')) {
                    if (empty($pemeliharaan->tanggal_mulai_pengerjaan) && in_array($pemeliharaan->status_pengerjaan, [Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN, Pemeliharaan::STATUS_PENGERJAAN_SELESAI, Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI])) {
                        $pemeliharaan->tanggal_mulai_pengerjaan = $validated['tanggal_mulai_pengerjaan'] ?? now();
                    }
                    if (empty($pemeliharaan->tanggal_selesai_pengerjaan) && in_array($pemeliharaan->status_pengerjaan, [Pemeliharaan::STATUS_PENGERJAAN_SELESAI, Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI])) {
                        $pemeliharaan->tanggal_selesai_pengerjaan = $validated['tanggal_selesai_pengerjaan'] ?? now();
                    }
                }
            }

            $pemeliharaan->save(); // Akan mentrigger event 'saved' di model

            $barangQr = $pemeliharaan->barangQrCode()->withTrashed()->first(); // Re-fetch untuk mendapatkan status terbaru jika diubah oleh event
            $changedData = array_intersect_key($pemeliharaan->getAttributes(), $pemeliharaan->getDirty());
            $originalDataFiltered = array_intersect_key($dataLamaPemeliharaan, $pemeliharaan->getDirty());

            if (!empty($changedData)) {
                LogAktivitas::create([
                    'id_user' => Auth::id(),
                    'aktivitas' => 'Update Pemeliharaan',
                    'deskripsi' => "Memperbarui data pemeliharaan ID: {$pemeliharaan->id} untuk unit " . (optional($barangQr)->kode_inventaris_sekolah ?? 'N/A'),
                    'model_terkait' => Pemeliharaan::class,
                    'id_model_terkait' => $pemeliharaan->id,
                    'data_lama' => json_encode($originalDataFiltered),
                    'data_baru' => json_encode($changedData),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
            DB::commit();

            $rolePrefix = $this->getRolePrefixFromUser($user);
            $showRouteName = $rolePrefix . 'pemeliharaan.show';
            if (!Route::has($showRouteName)) {
                $showRouteName = 'admin.pemeliharaan.show';
            }

            return redirect()->route($showRouteName, $pemeliharaan->id)->with('success', 'Data pemeliharaan berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning("Validation failed for Pemeliharaan update {$pemeliharaan->id}: ", $e->errors());
            return redirect()->back()->withErrors($e->validator)->withInput()
                ->with('error_form_type', 'edit')->with('error_pemeliharaan_id', $pemeliharaan->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memperbarui pemeliharaan {$pemeliharaan->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal memperbarui data pemeliharaan. Kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    {
        $this->authorize('delete', $pemeliharaan);
        try {
            DB::beginTransaction();
            $dataLama = $pemeliharaan->toArray();
            $deskripsiLog = $pemeliharaan->deskripsi_kerusakan ?? $pemeliharaan->catatan_pengajuan ?? "ID {$pemeliharaan->id}";
            $barangQr = $pemeliharaan->barangQrCode()->withTrashed()->first();

            $pemeliharaan->delete(); // Akan mentrigger event 'deleted' di model

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Arsip Pemeliharaan',
                'deskripsi' => "Mengarsipkan laporan pemeliharaan: \"{$deskripsiLog}\" untuk unit " . (optional($barangQr)->kode_inventaris_sekolah ?? 'N/A'),
                'model_terkait' => Pemeliharaan::class,
                'id_model_terkait' => $pemeliharaan->id,
                'data_lama' => json_encode($dataLama),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('pemeliharaan.index', 'admin.pemeliharaan.index');
            return redirect()->route($redirectRoute)->with('success', 'Laporan pemeliharaan berhasil diarsipkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal mengarsipkan pemeliharaan {$pemeliharaan->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal mengarsipkan laporan pemeliharaan.');
        }
    }

    public function restore(Request $request, $id): RedirectResponse
    {
        $pemeliharaan = Pemeliharaan::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $pemeliharaan);
        try {
            DB::beginTransaction();
            $pemeliharaan->restore(); // Akan mentrigger event 'restored' dan kemudian 'saved' oleh model
            $barangQr = $pemeliharaan->barangQrCode()->withTrashed()->first();

            // Logika setelah restore, jika event 'saved' di model tidak menangani ini sepenuhnya
            // atau jika ada logika khusus saat restore yang berbeda dari save biasa.
            // Jika model 'saved' event sudah cukup, bagian ini mungkin tidak diperlukan.
            if ($barangQr && !$barangQr->trashed()) {
                $pemeliharaan->refresh(); // Dapatkan status terbaru setelah event 'saved' mungkin berjalan
                $kondisiSebelumUpdate = $barangQr->getOriginal('kondisi') ?? $barangQr->kondisi;
                $statusSebelumUpdate = $barangQr->getOriginal('status') ?? $barangQr->status;
                $perluSimpanDanCatat = false;

                if (
                    $pemeliharaan->status_pengajuan === Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI &&
                    in_array($pemeliharaan->status_pengerjaan, [Pemeliharaan::STATUS_PENGERJAAN_BELUM_DIKERJAKAN, Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN])
                ) {
                    if ($barangQr->status !== BarangQrCode::STATUS_DALAM_PEMELIHARAAN) {
                        $barangQr->status = BarangQrCode::STATUS_DALAM_PEMELIHARAAN;
                        $perluSimpanDanCatat = true;
                    }
                    if ($barangQr->kondisi === BarangQrCode::KONDISI_BAIK) {
                        $barangQr->kondisi = BarangQrCode::KONDISI_KURANG_BAIK;
                        $perluSimpanDanCatat = true;
                    }
                }
                if ($perluSimpanDanCatat) {
                    $barangQr->saveQuietly();
                    // BarangStatus::create(...); // Jika perlu catatan spesifik untuk restore
                }
            }

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Pulihkan Pemeliharaan',
                'deskripsi' => "Laporan pemeliharaan ID {$pemeliharaan->id} untuk unit " . (optional($barangQr)->kode_inventaris_sekolah ?? 'N/A') . " berhasil dipulihkan.",
                'model_terkait' => Pemeliharaan::class,
                'id_model_terkait' => $pemeliharaan->id,
                'data_baru' => json_encode($pemeliharaan->fresh()->toArray()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            $redirectRoute = $this->getRedirectRouteName('pemeliharaan.index', 'admin.pemeliharaan.index');
            return redirect()->route($redirectRoute, ['status_arsip' => 'arsip'])->with('success', 'Laporan pemeliharaan berhasil dipulihkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan pemeliharaan {$id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal memulihkan laporan pemeliharaan.');
        }
    }

    private function getRolePrefixFromUser(?User $user): string
    {
        if (!$user) return 'admin.';
        $roleName = strtolower($user->role ?? '');
        $map = [
            User::ROLE_ADMIN => 'admin.',
            User::ROLE_OPERATOR => 'operator.',
            User::ROLE_GURU => 'guru.',
        ];
        return $map[$roleName] ?? ''; // Kembalikan string kosong jika peran tidak dikenal, agar bisa coba route global
    }

    private function getViewPathBasedOnRole(string $adminView, string $operatorView, ?string $guruView = null): string
    {
        $user = Auth::user();
        if (!$user) return $adminView;

        $targetView = $adminView;
        $roleName = strtolower($user->role ?? '');

        if ($roleName === User::ROLE_OPERATOR) {
            $targetView = view()->exists($operatorView) ? $operatorView : $adminView;
        } elseif ($roleName === User::ROLE_GURU && $guruView) {
            $targetView = view()->exists($guruView) ? $guruView : $adminView;
        }

        if (!view()->exists($targetView)) {
            Log::warning("View path tidak ditemukan: Target='{$targetView}', menggunakan Fallback='{$adminView}'. Role Pengguna: {$roleName}");
            if (!view()->exists($adminView)) {
                Log::critical("VIEW ADMIN DEFAULT TIDAK DITEMUKAN: {$adminView}. Periksa konfigurasi path view Anda.");
                abort(500, "Kesalahan konfigurasi: View utama '{$adminView}' tidak dapat ditemukan.");
            }
            return $adminView;
        }
        return $targetView;
    }

    private function getRedirectRouteName(string $baseRouteName, string $adminFallbackRouteName): string
    {
        $user = Auth::user();
        if (!$user) return $adminFallbackRouteName;

        $rolePrefix = $this->getRolePrefixFromUser($user);

        if (!empty($rolePrefix) && Route::has($rolePrefix . $baseRouteName)) {
            return $rolePrefix . $baseRouteName;
        }
        if (Route::has($baseRouteName)) { // Coba rute global jika prefix tidak ada atau rute berprefix tidak ditemukan
            return $baseRouteName;
        }
        // Fallback terakhir ke rute admin default
        return Route::has($adminFallbackRouteName) ? $adminFallbackRouteName : $baseRouteName;
    }
}
