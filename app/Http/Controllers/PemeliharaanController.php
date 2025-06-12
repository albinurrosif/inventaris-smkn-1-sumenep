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
            $ruanganOperatorIds = optional($user->ruanganYangDiKelola())->pluck('id') ?? collect(); // 
            $query->where(function ($qSub) use ($user, $ruanganOperatorIds) {
                $qSub->where('id_user_pengaju', $user->id) // Laporan yang dia buat 
                    ->orWhere('id_operator_pengerjaan', $user->id); // Tugas yang diberikan padanya 
                if ($ruanganOperatorIds->isNotEmpty()) { // 
                    $qSub->orWhereHas('barangQrCode', function ($qUnit) use ($ruanganOperatorIds, $user) {
                        $qUnit->where(function ($qUnitDetail) use ($ruanganOperatorIds, $user) {
                            $qUnitDetail->whereIn('id_ruangan', $ruanganOperatorIds) // Barang di ruangannya 
                                ->orWhere('id_pemegang_personal', $user->id); //
                        });
                    });
                } elseif ($user->id_pemegang_personal) {
                    $qSub->orWhereHas('barangQrCode', function ($qUnit) use ($user) {
                        $qUnit->where('id_pemegang_personal', $user->id); //
                    });
                }
            });
        } elseif ($user->hasRole(User::ROLE_GURU)) { // TAMBAHKAN BLOK INI
            // Guru hanya bisa melihat laporan yang diajukan oleh dirinya sendiri
            $query->where('id_user_pengaju', $user->id);
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

        // Tambahkan atribut 'keterkaitan' pada setiap item untuk ditampilkan di view
        $pemeliharaanList->getCollection()->transform(function ($item) use ($user) {
            $item->keterkaitan = 'Tidak diketahui'; // Default
            if ($user->hasRole(User::ROLE_ADMIN)) {
                $item->keterkaitan = 'Akses Admin';
            } elseif ($item->id_user_pengaju === $user->id) {
                $item->keterkaitan = 'Anda adalah Pelapor';
            } elseif ($item->id_operator_pengerjaan === $user->id) {
                $item->keterkaitan = 'Anda adalah PIC';
            } elseif ($item->barangQrCode && $item->barangQrCode->id_ruangan && $user->ruanganYangDiKelola()->where('id', $item->barangQrCode->id_ruangan)->exists()) {
                $item->keterkaitan = 'Barang di Ruangan Anda';
            }
            return $item;
        });

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

        // Query dasar yang sudah disempurnakan
        $baseQuery = BarangQrCode::with(['barang', 'ruangan', 'pemegangPersonal'])
            ->whereNull('deleted_at')

            // --- GANTI BARIS INI ---
            ->whereNotIn('status', [BarangQrCode::STATUS_DIPINJAM, BarangQrCode::STATUS_DALAM_PEMELIHARAAN])
            // --- AKHIR PERUBAHAN ---

            ->where('kondisi', '!=', BarangQrCode::KONDISI_HILANG)
            ->whereDoesntHave('pemeliharaanRecords', function ($query) {
                $query->where('status_pengajuan', 'Diajukan')
                    ->orWhere('status_pengajuan', 'Disetujui');
            });

        // Terapkan filter berdasarkan peran pengguna (logika ini tetap sama)
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $baseQuery->where(function ($q) use ($ruanganOperatorIds, $user) {
                $q->whereIn('id_ruangan', $ruanganOperatorIds)
                    ->orWhere('id_pemegang_personal', $user->id);
            });
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            $baseQuery->where('id_pemegang_personal', $user->id);
        }

        $barangQrOptions = $baseQuery->get()->map(function ($item) {
            $lokasi = optional($item->ruangan)->nama_ruangan ?? (optional($item->pemegangPersonal)->username ? 'Dipegang oleh: ' . $item->pemegangPersonal->username : 'Tanpa Lokasi');
            $text = "{$item->barang->nama_barang} ({$item->kode_inventaris_sekolah}) | Lokasi: {$lokasi}";
            return ['id' => $item->id, 'text' => $text];
        });

        $prioritasOptions = Pemeliharaan::getValidPrioritas();

        $barangQrCode = null; // Defaultnya null
        $idFromRequest = $request->input('id_barang_qr_code');

        // Jika ada ID yang dikirim dari tombol "Laporkan Kerusakan"
        if ($idFromRequest) {
            // Kita gunakan LAGI $baseQuery untuk memastikan barang yang diminta dari URL
            // memang valid dan lolos semua filter. Ini penting untuk keamanan.
            $singleItemQuery = (clone $baseQuery);
            $barangQrCode = $singleItemQuery->find($idFromRequest);
        }

        return view('pages.pemeliharaan.create', [
            'barangQrOptions' => $barangQrOptions,
            'prioritasOptions' => $prioritasOptions,
            'barangQrCode' => $barangQrCode,
        ]);
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
            // Menggunakan helper yang lebih andal
            $redirectUrl = $this->getRedirectUrl('pemeliharaan');
            return redirect($redirectUrl)->with('success', 'Laporan pemeliharaan berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan laporan pemeliharaan: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal mengajukan laporan pemeliharaan. Kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    // Di dalam PemeliharaanController
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

        // Ambil daftar user yang bisa menjadi PIC (Operator atau Admin)
        $picList = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
            ->orderBy('username')->get();

        // ===== PERUBAHAN DI SINI =====
        // Gunakan method private yang sudah kita buat sebelumnya
        $rolePrefix = $this->getRolePrefix();

        $viewPath = 'pages.pemeliharaan.show';

        // Kirim semua variabel yang dibutuhkan oleh view, termasuk rolePrefix
        return view($viewPath, compact('pemeliharaan', 'picList', 'rolePrefix'));
        // ============================
    }


    public function edit(Pemeliharaan $pemeliharaan): View
    {
        $this->authorize('update', $pemeliharaan);
        $user = Auth::user();

        $pemeliharaan->load([
            'barangQrCode' => fn($q) => $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']),
            'pengaju',
            'penyetuju',
            'operatorPengerjaan'
        ]);
        $barangQrCode = $pemeliharaan->barangQrCode;

        // Otorisasi tambahan ini sudah benar, tidak perlu diubah.
        if ($user->hasRole(User::ROLE_OPERATOR) && $user->id !== $pemeliharaan->id_user_pengaju && $user->id !== $pemeliharaan->id_operator_pengerjaan) {
            if (!($barangQrCode && (optional($user->ruanganYangDiKelola())->where('id', $barangQrCode->id_ruangan)->exists() || $barangQrCode->id_pemegang_personal === $user->id))) {
                abort(403, 'Operator tidak diizinkan mengedit laporan pemeliharaan ini.');
            }
        } elseif ($user->hasRole(User::ROLE_GURU) && $user->id !== $pemeliharaan->id_user_pengaju) {
            abort(403, 'Guru hanya bisa mengedit laporannya sendiri jika status masih "Diajukan".');
        }

        // Logika untuk menyiapkan daftar PIC sudah benar, tidak perlu diubah.
        $picList = collect();
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $picList = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->orderBy('username')->get();
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            if ($pemeliharaan->id_operator_pengerjaan === null || $pemeliharaan->id_operator_pengerjaan === $user->id) {
                $picList = User::where('id', $user->id)->get();
            } else {
                $currentPic = User::find($pemeliharaan->id_operator_pengerjaan);
                if ($currentPic) $picList->push($currentPic);
            }
        }

        // Menyiapkan data untuk form dropdowns, sudah benar.
        $statusPengajuanList = Pemeliharaan::getValidStatusPengajuan();
        $statusPengerjaanList = Pemeliharaan::getValidStatusPengerjaan();
        // Ambil semua kondisi valid dari model
        $semuaKondisi = BarangQrCode::getValidKondisi();

        // Kemudian, saring (filter) array tersebut untuk menghapus 'Hilang'
        // Kita gunakan konstanta dari model untuk memastikan akurasi
        $kondisiBarangList = array_filter($semuaKondisi, function ($kondisi) {
            return $kondisi !== \App\Models\BarangQrCode::KONDISI_HILANG;
        });
        $prioritasOptions = Pemeliharaan::getValidPrioritas();
        $rolePrefix = $this->getRolePrefix();

        // Mengarahkan ke satu view terpusat di 'pages'
        return view('pages.pemeliharaan.edit', compact(
            'pemeliharaan',
            'barangQrCode',
            'picList',
            'statusPengajuanList',
            'statusPengerjaanList',
            'kondisiBarangList',
            'prioritasOptions',
            'rolePrefix'
        ));
    }


    public function update(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    {
        // Otorisasi dan pengecekan apakah laporan sudah terkunci (sudah benar)
        $this->authorize('update', $pemeliharaan);
        if ($pemeliharaan->isLocked()) {
            return redirect()->route($this->getRedirectUrl("pemeliharaan/{$pemeliharaan->id}"))
                ->with('error', 'Laporan yang sudah final tidak dapat diedit lagi.');
        }

        $user = Auth::user();
        /** @var \App\Models\User $user */

        $dataLamaPemeliharaan = $pemeliharaan->getAttributes();
        $pemeliharaan->fill($request->all());

        $rules = [];

        // ======================================================================
        // AWAL LOGIKA VALIDASI BARU DENGAN BLOK 'IF' INDEPENDEN
        // ======================================================================

        // Aksi 1: Validasi jika STATUS PENGAJUAN berubah (Aksi oleh Admin)
        if ($pemeliharaan->isDirty('status_pengajuan')) {
            $rules['status_pengajuan'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidStatusPengajuan()))];
            if ($request->input('status_pengajuan') === Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI) {
                $rules['id_operator_pengerjaan'] = 'required|exists:users,id';
            }
        }

        // Aksi 2: Validasi jika STATUS PENGERJAAN berubah (Aksi oleh Operator/Admin)
        if ($pemeliharaan->isDirty('status_pengerjaan')) {
            $rules['status_pengerjaan'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidStatusPengerjaan()))];
            if (in_array($request->input('status_pengerjaan'), [
                Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
                Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI,
                Pemeliharaan::STATUS_PENGERJAAN_GAGAL
            ])) {
                $rules['kondisi_barang_setelah_pemeliharaan'] = ['required', Rule::in(BarangQrCode::getValidKondisi())];
                $rules['hasil_pemeliharaan'] = 'required|string|max:1000';
            }
            $rules['deskripsi_pekerjaan'] = 'nullable|string|max:1000';
            $rules['biaya'] = 'nullable|numeric|min:0';
            $rules['catatan_pengerjaan'] = 'nullable|string|max:1000';
            $rules['foto_perbaikan'] = 'nullable|image|mimes:jpeg,jpg,png|max:2048';
        }

        // Aksi 3: Validasi jika PENGGUNA AWAL mengedit laporannya (tidak ada status yang berubah)
        if (!$pemeliharaan->isDirty('status_pengajuan') && !$pemeliharaan->isDirty('status_pengerjaan')) {
            if ($user->id === $pemeliharaan->getOriginal('id_user_pengaju') && $pemeliharaan->getOriginal('status_pengajuan') === Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN) {
                $rules['catatan_pengajuan'] = 'required|string|max:1000';
                $rules['prioritas'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidPrioritas()))];
                $rules['foto_kerusakan'] = 'nullable|image|mimes:jpeg,jpg,png|max:2048';
            }
        }

        $validated = $request->validate($rules);

        // ======================================================================
        // AKHIR LOGIKA VALIDASI BARU
        // ======================================================================

        try {
            DB::beginTransaction();

            // Mengisi kembali dengan data yang sudah divalidasi, lebih aman
            $pemeliharaan->fill($validated);

            // (Logika upload file foto tidak berubah)
            if ($request->hasFile('foto_kerusakan')) {
                if ($pemeliharaan->foto_kerusakan_path) Storage::disk('public')->delete($pemeliharaan->foto_kerusakan_path);
                $pemeliharaan->foto_kerusakan_path = $request->file('foto_kerusakan')->store('pemeliharaan/kerusakan', 'public');
            }
            if ($request->hasFile('foto_perbaikan')) {
                if ($pemeliharaan->foto_perbaikan_path) Storage::disk('public')->delete($pemeliharaan->foto_perbaikan_path);
                $pemeliharaan->foto_perbaikan_path = $request->file('foto_perbaikan')->store('pemeliharaan/perbaikan', 'public');
            }

            // (Logika otomatisasi tanggal tidak berubah, sudah benar)
            if ($pemeliharaan->isDirty('status_pengajuan') && in_array($pemeliharaan->status_pengajuan, [Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI, Pemeliharaan::STATUS_PENGAJUAN_DITOLAK])) {
                $pemeliharaan->tanggal_persetujuan = now();
                $pemeliharaan->id_user_penyetuju = $user->id;
            }
            if ($pemeliharaan->isDirty('status_pengerjaan')) {
                if ($pemeliharaan->status_pengerjaan === Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN && is_null($pemeliharaan->getOriginal('tanggal_mulai_pengerjaan'))) {
                    $pemeliharaan->tanggal_mulai_pengerjaan = now();
                }
                if (in_array($pemeliharaan->status_pengerjaan, [Pemeliharaan::STATUS_PENGERJAAN_SELESAI, Pemeliharaan::STATUS_PENGERJAAN_GAGAL, Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI])) {
                    if (is_null($pemeliharaan->getOriginal('tanggal_mulai_pengerjaan'))) {
                        $pemeliharaan->tanggal_mulai_pengerjaan = now();
                    }
                    $pemeliharaan->tanggal_selesai_pengerjaan = now();
                }
            }

            $pemeliharaan->save(); // Trigger event 'saved' di model

            // Log aktivitas
            $barangQr = $pemeliharaan->barangQrCode()->withTrashed()->first();
            $changedData = array_intersect_key($pemeliharaan->getAttributes(), $pemeliharaan->getDirty());
            $originalDataFiltered = array_intersect_key($dataLamaPemeliharaan, $pemeliharaan->getDirty());

            if (!empty($changedData)) {
                LogAktivitas::create([
                    'id_user' => Auth::id(),
                    'aktivitas' => 'Update Pemeliharaan',
                    'deskripsi' => "Memperbarui data pemeliharaan ID: {$pemeliharaan->id} untuk unit " .
                        (optional($barangQr)->kode_inventaris_sekolah ?? 'N/A'),
                    'model_terkait' => Pemeliharaan::class,
                    'id_model_terkait' => $pemeliharaan->id,
                    'data_lama' => json_encode($originalDataFiltered),
                    'data_baru' => json_encode($changedData),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            DB::commit();

            // Redirect ke halaman detail
            $redirectUrl = $this->getRedirectUrl("pemeliharaan/{$pemeliharaan->id}");
            return redirect($redirectUrl)->with('success', 'Data pemeliharaan berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning("Validation failed for Pemeliharaan update {$pemeliharaan->id}: ", $e->errors());
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error_form_type', 'edit')
                ->with('error_pemeliharaan_id', $pemeliharaan->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memperbarui pemeliharaan {$pemeliharaan->id}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data pemeliharaan. Kesalahan: ' . $e->getMessage())
                ->withInput();
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
            $redirectUrl = $this->getRedirectUrl('pemeliharaan');
            return redirect($redirectUrl)->with('success', 'Laporan pemeliharaan berhasil diarsipkan.');
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
            $redirectUrl = $this->getRedirectUrl('pemeliharaan?status_arsip=arsip');
            return redirect($redirectUrl)->with('success', 'Laporan pemeliharaan berhasil dipulihkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan pemeliharaan {$id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal memulihkan laporan pemeliharaan.');
        }
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

    // Tambahkan method ini di dalam class PemeliharaanController
    private function getRolePrefix(): string
    {
        $user = Auth::user();
        if (!$user) {
            return '';
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return 'admin.';
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            return 'operator.';
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            return 'guru.';
        }
        return '';
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
}
