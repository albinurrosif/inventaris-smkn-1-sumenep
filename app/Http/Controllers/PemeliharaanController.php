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
use App\Notifications\NewPemeliharaanRequest;
use App\Notifications\PemeliharaanStatusUpdated;
use App\Notifications\PemeliharaanAssigned;
use App\Rules\NoActiveMaintenance;

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


    // public function create(Request $request): View
    // {
    //     $this->authorize('create', Pemeliharaan::class);
    //     $user = Auth::user();
    //     /** @var \App\Models\User $user */

    //     // Query dasar yang sudah disempurnakan
    //     $baseQuery = BarangQrCode::with(['barang', 'ruangan', 'pemegangPersonal'])
    //         ->whereNull('deleted_at')

    //         // --- GANTI BARIS INI ---
    //         ->whereNotIn('status', [BarangQrCode::STATUS_DIPINJAM, BarangQrCode::STATUS_DALAM_PEMELIHARAAN])
    //         // --- AKHIR PERUBAHAN ---

    //         ->where('kondisi', '!=', BarangQrCode::KONDISI_HILANG)
    //         ->whereDoesntHave('pemeliharaanRecords', function ($query) {
    //             $query->where('status_pengajuan', 'Diajukan')
    //                 ->orWhere('status_pengajuan', 'Disetujui');
    //         });

    //     // Terapkan filter berdasarkan peran pengguna (logika ini tetap sama)
    //     if ($user->hasRole(User::ROLE_OPERATOR)) {
    //         $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
    //         $baseQuery->where(function ($q) use ($ruanganOperatorIds, $user) {
    //             $q->whereIn('id_ruangan', $ruanganOperatorIds)
    //                 ->orWhere('id_pemegang_personal', $user->id);
    //         });
    //     } elseif ($user->hasRole(User::ROLE_GURU)) {
    //         $baseQuery->where('id_pemegang_personal', $user->id);
    //     }

    //     $barangQrOptions = $baseQuery->get()->map(function ($item) {
    //         $lokasi = optional($item->ruangan)->nama_ruangan ?? (optional($item->pemegangPersonal)->username ? 'Dipegang oleh: ' . $item->pemegangPersonal->username : 'Tanpa Lokasi');
    //         $text = "{$item->barang->nama_barang} ({$item->kode_inventaris_sekolah}) | Lokasi: {$lokasi}";
    //         return ['id' => $item->id, 'text' => $text];
    //     });

    //     $prioritasOptions = Pemeliharaan::getValidPrioritas();

    //     $barangQrCode = null; // Defaultnya null
    //     $idFromRequest = $request->input('id_barang_qr_code');

    //     // Jika ada ID yang dikirim dari tombol "Laporkan Kerusakan"
    //     if ($idFromRequest) {
    //         // Kita gunakan LAGI $baseQuery untuk memastikan barang yang diminta dari URL
    //         // memang valid dan lolos semua filter. Ini penting untuk keamanan.
    //         $singleItemQuery = (clone $baseQuery);
    //         $barangQrCode = $singleItemQuery->find($idFromRequest);
    //     }

    //     return view('pages.pemeliharaan.create', [
    //         'barangQrOptions' => $barangQrOptions,
    //         'prioritasOptions' => $prioritasOptions,
    //         'barangQrCode' => $barangQrCode,
    //     ]);
    // }

    // public function store(Request $request): RedirectResponse
    // {
    //     $this->authorize('create', Pemeliharaan::class);
    //     $user = Auth::user();
    //     /** @var \App\Models\User $user */

    //     $validated = $request->validate([
    //         'id_barang_qr_code' => 'required|exists:barang_qr_codes,id',
    //         'tanggal_pengajuan' => 'required|date|before_or_equal:today',
    //         'catatan_pengajuan' => 'required|string|max:1000',
    //         'prioritas' => ['required', Rule::in(array_keys(Pemeliharaan::getValidPrioritas()))],
    //         // Validasi untuk foto kerusakan
    //         'foto_kerusakan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
    //     ]);

    //     $barangQr = BarangQrCode::find($validated['id_barang_qr_code']);
    //     if (!$barangQr || $barangQr->trashed() || in_array($barangQr->kondisi, [BarangQrCode::KONDISI_HILANG]) || $barangQr->status === BarangQrCode::STATUS_DIPINJAM) {
    //         return redirect()->back()->with('error', 'Unit barang tidak valid atau tidak dalam status/kondisi yang memungkinkan untuk dilaporkan pemeliharaan.')->withInput();
    //     }
    //     // Otorisasi tambahan berdasarkan peran
    //     if ($user->hasRole(User::ROLE_OPERATOR)) {
    //         $isAllowed = optional($user->ruanganYangDiKelola())->where('id', $barangQr->id_ruangan)->exists() || $barangQr->id_pemegang_personal === $user->id;
    //         if (!$isAllowed) return redirect()->back()->with('error', 'Operator tidak diizinkan membuat laporan untuk unit barang ini.')->withInput();
    //     } elseif ($user->hasRole(User::ROLE_GURU)) {
    //         if ($barangQr->id_pemegang_personal !== $user->id) return redirect()->back()->with('error', 'Guru hanya bisa membuat laporan untuk barang yang dipegang secara personal.')->withInput();
    //     }


    //     try {
    //         DB::beginTransaction();
    //         // Handle upload file
    //         $pathKerusakan = null;
    //         if ($request->hasFile('foto_kerusakan')) {
    //             $pathKerusakan = $request->file('foto_kerusakan')->store('pemeliharaan/kerusakan', 'public');
    //         }

    //         $pemeliharaan = new Pemeliharaan();
    //         $pemeliharaan->fill($validated);
    //         $pemeliharaan->id_user_pengaju = $user->id;
    //         $pemeliharaan->foto_kerusakan_path = $pathKerusakan; // Simpan path
    //         $pemeliharaan->save();

    //         LogAktivitas::create([
    //             'id_user' => Auth::id(),
    //             'aktivitas' => 'Pengajuan Pemeliharaan',
    //             'deskripsi' => "Pengajuan pemeliharaan untuk unit {$barangQr->kode_inventaris_sekolah}: " . Str::limit($pemeliharaan->deskripsi_kerusakan, 150),
    //             'model_terkait' => Pemeliharaan::class,
    //             'id_model_terkait' => $pemeliharaan->id,
    //             'data_baru' => $pemeliharaan->toJson(),
    //             'ip_address' => $request->ip(),
    //             'user_agent' => $request->userAgent()
    //         ]);
    //         DB::commit();

    //         // --- TAMBAHKAN KODE INI UNTUK MENGIRIM NOTIFIKASI ---
    //         // Muat relasi yang diperlukan untuk notifikasi
    //         $pemeliharaan->load('pengaju', 'barangQrCode.barang', 'barangQrCode.ruangan', 'barangQrCode.pemegangPersonal');

    //         // 1. Kirim notifikasi ke semua Admin
    //         $admins = User::where('role', User::ROLE_ADMIN)->get();
    //         foreach ($admins as $admin) {
    //             $admin->notify(new NewPemeliharaanRequest($pemeliharaan));
    //         }

    //         // 2. Kirim notifikasi ke Operator yang terkait dengan ruangan atau pemegang personal barang
    //         $relevantOperators = collect();

    //         // Jika barang memiliki ruangan
    //         if ($pemeliharaan->barangQrCode && $pemeliharaan->barangQrCode->id_ruangan) {
    //             $operatorsInRuangan = User::where('role', User::ROLE_OPERATOR)
    //                 ->whereHas('ruanganYangDiKelola', function ($query) use ($pemeliharaan) {
    //                     $query->where('id', $pemeliharaan->barangQrCode->id_ruangan);
    //                 })
    //                 ->get();
    //             $relevantOperators = $relevantOperators->merge($operatorsInRuangan);
    //         }

    //         // Jika barang dipegang personal
    //         if ($pemeliharaan->barangQrCode && $pemeliharaan->barangQrCode->id_pemegang_personal) {
    //             $operatorAsPersonalHolder = User::where('id', $pemeliharaan->barangQrCode->id_pemegang_personal)
    //                 ->where('role', User::ROLE_OPERATOR) // Pastikan dia memang operator
    //                 ->get();
    //             $relevantOperators = $relevantOperators->merge($operatorAsPersonalHolder);
    //         }

    //         // Kirim notifikasi ke operator yang relevan (pastikan unik)
    //         foreach ($relevantOperators->unique('id') as $operator) {
    //             $operator->notify(new NewPemeliharaanRequest($pemeliharaan));
    //         }
    //         // --- AKHIR KODE NOTIFIKASI ---


    //         // Menggunakan helper yang lebih andal
    //         $redirectUrl = $this->getRedirectUrl('pemeliharaan');
    //         return redirect($redirectUrl)->with('success', 'Laporan pemeliharaan berhasil diajukan.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Gagal menyimpan laporan pemeliharaan: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
    //         return redirect()->back()->with('error', 'Gagal mengajukan laporan pemeliharaan. Kesalahan: ' . $e->getMessage())->withInput();
    //     }
    // }

    // Di dalam PemeliharaanController.php
    public function create(Request $request) // <-- Tambahkan Request $request
    {
        $this->authorize('create', Pemeliharaan::class);

        // --- (Logika filter barang Anda tetap sama persis) ---
        $finalStatuses = [
            Pemeliharaan::STATUS_SELESAI,
            Pemeliharaan::STATUS_TUNTAS,
            Pemeliharaan::STATUS_DITOLAK,
        ];
        $activeMaintenanceBarangIds = Pemeliharaan::whereHas('barangQrCode', function ($query) {
            $query->whereNull('deleted_at');
        })
            ->whereNotIn('status_pengajuan', [$finalStatuses[2]])
            ->whereNotIn('status_pengerjaan', [$finalStatuses[0]])
            ->pluck('id_barang_qr_code');
        $barangList = \App\Models\BarangQrCode::whereNotIn('id', $activeMaintenanceBarangIds)
            ->whereNull('deleted_at')
            ->with('barang')
            ->orderBy('kode_inventaris_sekolah', 'asc')
            ->get();
        $prioritasList = Pemeliharaan::getValidPrioritas();
        // --- (Akhir logika filter barang) ---


        // ================== AWAL LOGIKA BARU ==================

        // 1. Ambil ID barang dari URL (jika ada)
        $idFromRequest = $request->input('id_barang_qr_code');
        $barangToSelect = null;

        // 2. Cari barang tersebut di dalam daftar yang sudah kita filter
        if ($idFromRequest) {
            $barangToSelect = $barangList->firstWhere('id', $idFromRequest);
        }

        // 3. Kirim variabel $barangToSelect ke view
        return view('pages.pemeliharaan.create', compact('barangList', 'prioritasList', 'barangToSelect'));

        // =================== AKHIR LOGIKA BARU ===================
    }

    // Di dalam file: app/Http/Controllers/PemeliharaanController.php

    public function store(Request $request)
    {
        $this->authorize('create', Pemeliharaan::class);

        // Tetap menggunakan 'foto_kerusakan' untuk konsistensi
        $validated = $request->validate([
            'id_barang_qr_code' => ['required', 'exists:barang_qr_codes,id', new \App\Rules\NoActiveMaintenance],
            'catatan_pengajuan' => 'required|string|max:2000',
            'prioritas' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Pemeliharaan::getValidPrioritas()))],
            'foto_kerusakan' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $barang = \App\Models\BarangQrCode::find($validated['id_barang_qr_code']);

        // ================== AWAL PERBAIKAN ==================

        // 1. Definisikan variabel untuk log riwayat SEBELUM transaksi
        $kondisiSebelum = $barang->kondisi;
        $statusSebelum = $barang->status;

        DB::beginTransaction();
        try {
            // 2. Siapkan data untuk disimpan
            $dataToCreate = $validated;

            // Hapus key file agar tidak error saat create
            unset($dataToCreate['foto_kerusakan']);

            // 3. Tambahkan "snapshot" ke data yang akan dibuat
            $dataToCreate['kondisi_saat_lapor'] = $kondisiSebelum;
            $dataToCreate['status_saat_lapor'] = $statusSebelum;

            // Tambahkan data sistem lainnya
            $dataToCreate['id_user_pengaju'] = Auth::id();
            $dataToCreate['tanggal_pengajuan'] = now();
            $dataToCreate['status_pengajuan'] = Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN;
            $dataToCreate['status_pengerjaan'] = Pemeliharaan::STATUS_PENGERJAAN_BELUM_DIKERJAKAN;

            // Proses upload foto jika ada
            if ($request->hasFile('foto_kerusakan')) {
                $dataToCreate['foto_kerusakan_path'] = $request->file('foto_kerusakan')->store('pemeliharaan/kerusakan', 'public');
            }

            $pemeliharaan = Pemeliharaan::create($dataToCreate);

            // Ubah status barang menjadi "Dalam Pemeliharaan"
            $barang->status = \App\Models\BarangQrCode::STATUS_DALAM_PEMELIHARAAN;
            $barang->save();

            // Buat log status barang (sekarang variabelnya sudah ada)
            \App\Models\BarangStatus::create([
                'id_barang_qr_code' => $barang->id,
                'id_user_pencatat' => Auth::id(),
                'kondisi_sebelumnya' => $kondisiSebelum, // <-- Sekarang sudah aman
                'kondisi_sesudahnya' => $barang->kondisi,
                'status_ketersediaan_sebelumnya' => $statusSebelum, // <-- Sekarang sudah aman
                'status_ketersediaan_sesudahnya' => $barang->status,
                'deskripsi_kejadian' => "Dilaporkan untuk pemeliharaan ID: {$pemeliharaan->id}",
                'id_pemeliharaan_trigger' => $pemeliharaan->id,
            ]);

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Membuat Laporan Pemeliharaan',
                'deskripsi' => "Membuat laporan pemeliharaan #{$pemeliharaan->id} untuk barang: {$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})",
                'model_terkait' => Pemeliharaan::class,
                'id_model_terkait' => $pemeliharaan->id,
                'data_baru' => $pemeliharaan->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ================== TAMBAHKAN BLOK NOTIFIKASI INI ==================

            // Muat relasi yang dibutuhkan agar data di notifikasi lengkap
            $pemeliharaan->load('pengaju', 'barangQrCode.barang');

            // 1. Kirim notifikasi ke semua Admin
            $admins = User::where('role', User::ROLE_ADMIN)->get();
            \Illuminate\Support\Facades\Notification::send($admins, new NewPemeliharaanRequest($pemeliharaan));

            // 2. Kirim notifikasi ke Operator yang relevan
            if (!$pemeliharaan->pengaju->hasRole(User::ROLE_OPERATOR)) {
                if ($pemeliharaan->barangQrCode && $pemeliharaan->barangQrCode->id_ruangan) {
                    $operators = User::where('role', User::ROLE_OPERATOR)
                        ->whereHas('ruanganYangDiKelola', function ($query) use ($pemeliharaan) {
                            // PERBAIKAN: Gunakan nama tabel dan nama kolom yang benar ('ruangans.id')
                            $query->where('ruangans.id', $pemeliharaan->barangQrCode->id_ruangan);
                        })->get();
                    \Illuminate\Support\Facades\Notification::send($operators, new NewPemeliharaanRequest($pemeliharaan));
                }
            }
            // ====================================================================

            DB::commit();

            return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
                ->with('success', 'Laporan kerusakan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan laporan pemeliharaan: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat laporan.')->withInput();
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
        //dd($pemeliharaan);
        $this->authorize('view', $pemeliharaan);

        // 1. Ambil log status pertama yang terkait dengan pemeliharaan ini
        $logStatusAwal = \App\Models\BarangStatus::where('id_pemeliharaan_trigger', $pemeliharaan->id)
            ->orderBy('tanggal_pencatatan', 'asc')
            ->first();

        // 2. Tentukan variabel untuk kondisi & status awal.
        // Jika log ditemukan, gunakan data dari log. Jika tidak, gunakan data barang saat ini sebagai fallback.
        $kondisiSaatLapor = optional($logStatusAwal)->kondisi_sebelumnya ?? optional($pemeliharaan->barangQrCode)->getOriginal('kondisi');
        $statusSaatLapor = optional($logStatusAwal)->status_ketersediaan_sebelumnya ?? optional($pemeliharaan->barangQrCode)->getOriginal('status');

        $picList = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->orderBy('username')->get();
        $rolePrefix = $this->getRolePrefix();

        //dd($pemeliharaan->toArray());


        // 3. Kirim variabel baru ini ke view
        return view('pages.pemeliharaan.show', compact(
            'pemeliharaan',
            'picList',
            'rolePrefix',
            'kondisiSaatLapor', // <-- Variabel baru
            'statusSaatLapor'   // <-- Variabel baru
        ));
    }

    /**
     * Menyetujui laporan pemeliharaan.
     */
    public function approve(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    {
        $this->authorize('approveOrReject', $pemeliharaan);

        $request->validate(
            ['id_operator_pengerjaan' => 'required|exists:users,id'],
            ['id_operator_pengerjaan.required' => 'Harap pilih PIC (Operator) sebelum menyetujui.']
        );

        DB::transaction(function () use ($request, $pemeliharaan) {
            $pemeliharaan->update([
                'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI,
                'id_user_penyetuju' => Auth::id(),
                'tanggal_persetujuan' => now(),
                'id_operator_pengerjaan' => $request->id_operator_pengerjaan,
                'catatan_persetujuan' => $request->catatan_persetujuan,
            ]);

            if ($pemeliharaan->barangQrCode) {
                $pemeliharaan->barangQrCode->update(['status' => \App\Models\BarangQrCode::STATUS_DALAM_PEMELIHARAAN]);
            }
            // (Logika logging bisa ditambahkan di sini)
            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Menyetujui Laporan Pemeliharaan',
                'deskripsi' => "Menyetujui laporan #{$pemeliharaan->id} dan menugaskan ke PIC: " . User::find($request->id_operator_pengerjaan)->username,
                'model_terkait' => Pemeliharaan::class,
                'id_model_terkait' => $pemeliharaan->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ================== TAMBAHKAN BLOK NOTIFIKASI INI ==================
            $pic = User::find($request->id_operator_pengerjaan);
            if ($pic) {
                // Gunakan class PemeliharaanAssigned yang sudah Anda buat
                $pic->notify(new PemeliharaanAssigned($pemeliharaan));
            }
            // ====================================================================

        });

        return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
            ->with('success', 'Laporan pemeliharaan berhasil disetujui.');
    }

    /**
     * Menolak laporan pemeliharaan.
     */
    public function reject(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    {
        $this->authorize('approveOrReject', $pemeliharaan);

        // Validasi sekarang menggunakan 'catatan_persetujuan'
        $request->validate(
            ['catatan_persetujuan' => 'required|string|max:1000'],
            ['catatan_persetujuan.required' => 'Alasan penolakan wajib diisi pada kolom catatan.']
        );

        $pemeliharaan->update([
            'status_pengajuan' => Pemeliharaan::STATUS_PENGAJUAN_DITOLAK,
            'id_user_penyetuju' => Auth::id(),
            'tanggal_persetujuan' => now(), // Tetap catat tanggal penolakan
            // Simpan alasan penolakan ke kolom 'catatan_persetujuan'
            'catatan_persetujuan' => $request->catatan_persetujuan,
        ]);

        LogAktivitas::create([
            'id_user' => Auth::id(),
            'aktivitas' => 'Menolak Laporan Pemeliharaan',
            'deskripsi' => "Menolak laporan pemeliharaan #{$pemeliharaan->id} dengan alasan: " . Str::limit($request->catatan_persetujuan, 100),
            'model_terkait' => Pemeliharaan::class,
            'id_model_terkait' => $pemeliharaan->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // ================== TAMBAHKAN BLOK NOTIFIKASI INI ==================
        if ($pemeliharaan->pengaju) {
            $alasanPenolakan = $request->catatan_persetujuan;
            // Gunakan class PemeliharaanStatusUpdated
            $pemeliharaan->pengaju->notify(new PemeliharaanStatusUpdated($pemeliharaan, $alasanPenolakan));
        }
        // ====================================================================


        return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
            ->with('success', 'Laporan pemeliharaan telah ditolak.');
    }

    /**
     * Memulai proses pengerjaan pemeliharaan.
     */
    public function startWork(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    {
        $this->authorize('startWork', $pemeliharaan);

        $pemeliharaan->update([
            'status_pengerjaan' => Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN,
            'tanggal_mulai_pengerjaan' => now(),
        ]);

        LogAktivitas::create([
            'id_user' => Auth::id(),
            'aktivitas' => 'Memulai Pengerjaan Pemeliharaan',
            'deskripsi' => "PIC memulai pengerjaan untuk laporan pemeliharaan #{$pemeliharaan->id} pada barang: {$pemeliharaan->barangQrCode->barang->nama_barang}",
            'model_terkait' => Pemeliharaan::class,
            'id_model_terkait' => $pemeliharaan->id,
            'ip_address' => $request->ip(), // <-- Perlu menambahkan Request $request di parameter method
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
            ->with('success', 'Proses perbaikan telah dimulai.');
    }

    /**
     * Menyelesaikan proses pengerjaan pemeliharaan.
     */
    // Di dalam file: app/Http/Controllers/PemeliharaanController.php

    public function completeWork(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    {
        $this->authorize('completeWork', $pemeliharaan);

        $validated = $request->validate([
            'deskripsi_pekerjaan' => 'required|string|max:2000',
            'hasil_pemeliharaan' => 'required|string|max:1000',
            'biaya' => 'nullable|numeric|min:0',
            'kondisi_barang_setelah_pemeliharaan' => ['required', \Illuminate\Validation\Rule::in(\App\Models\BarangQrCode::getValidKondisi())],
            'foto_perbaikan' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // ================== AWAL LOGIKA BARU YANG KONSISTEN ==================

        $dataToUpdate = $validated;
        unset($dataToUpdate['foto_perbaikan']); // Hapus key file agar tidak error saat mass assignment

        // Proses upload foto jika ada
        if ($request->hasFile('foto_perbaikan')) {
            // Hapus foto lama jika ada
            if ($pemeliharaan->foto_perbaikan_path) {
                Storage::disk('public')->delete($pemeliharaan->foto_perbaikan_path);
            }
            // Simpan yang baru dan tambahkan path ke data update
            $dataToUpdate['foto_perbaikan_path'] = $request->file('foto_perbaikan')->store('pemeliharaan/perbaikan', 'public');
        }

        // Tambahkan data status dan tanggal
        $dataToUpdate['status_pengerjaan'] = Pemeliharaan::STATUS_PENGERJAAN_SELESAI;
        $dataToUpdate['tanggal_selesai_pengerjaan'] = now();

        DB::transaction(function () use ($dataToUpdate, $pemeliharaan, $request) {
            // 1. Update data pemeliharaan

            $pemeliharaan->update($dataToUpdate);

            // 2. Update data barang terkait
            if ($pemeliharaan->barangQrCode) {
                // Kita tidak perlu menyimpan kondisi/status sebelumnya di sini karena event di Model sudah menanganinya
                $pemeliharaan->barangQrCode->update([
                    'status' => \App\Models\BarangQrCode::STATUS_TERSEDIA,
                    'kondisi' => $pemeliharaan->kondisi_barang_setelah_pemeliharaan,
                ]);
            }

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Menyelesaikan Pekerjaan Pemeliharaan',
                'deskripsi' => "Menyelesaikan pekerjaan untuk laporan #{$pemeliharaan->id}. Hasil: " . Str::limit($pemeliharaan->hasil_pemeliharaan, 100),
                'model_terkait' => Pemeliharaan::class,
                'id_model_terkait' => $pemeliharaan->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ================== TAMBAHKAN BLOK NOTIFIKASI INI ==================
            if ($pemeliharaan->pengaju) {
                $hasil = $request->hasil_pemeliharaan;
                $pemeliharaan->pengaju->notify(new PemeliharaanStatusUpdated($pemeliharaan, "Perbaikan selesai. Hasil: " . $hasil));
            }
            // ====================================================================

        });

        // =================== AKHIR LOGIKA BARU YANG KONSISTEN ===================

        return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
            ->with('success', 'Pekerjaan perbaikan telah selesai dan data barang diperbarui.');
    }

    public function confirmHandover(Request $request, Pemeliharaan $pemeliharaan): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('confirmHandover', $pemeliharaan);

        // Tambahkan validasi untuk file foto
        $request->validate(
            ['foto_tuntas' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'],
            ['foto_tuntas.required' => 'Foto bukti serah terima wajib diunggah.']
        );

        $pathTuntas = null;
        if ($request->hasFile('foto_tuntas')) {
            // Simpan file ke storage/app/public/pemeliharaan/tuntas
            $pathTuntas = $request->file('foto_tuntas')->store('pemeliharaan/tuntas', 'public');
        }

        $pemeliharaan->update([
            'tanggal_tuntas' => now(),
            'foto_tuntas_path' => $pathTuntas
        ]);

        LogAktivitas::create([
            'id_user' => Auth::id(),
            'aktivitas' => 'Menuntaskan Pemeliharaan',
            'deskripsi' => "Konfirmasi serah terima untuk laporan #{$pemeliharaan->id}. Proses pemeliharaan tuntas.",
            'model_terkait' => Pemeliharaan::class,
            'id_model_terkait' => $pemeliharaan->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // ================== TAMBAHKAN BLOK NOTIFIKASI INI ==================
        if ($pemeliharaan->pengaju) {
            $pemeliharaan->pengaju->notify(new PemeliharaanStatusUpdated($pemeliharaan, "Barang telah diterima kembali."));
        }
        // ====================================================================


        return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
            ->with('success', 'Konfirmasi serah terima berhasil. Proses pemeliharaan kini telah tuntas.');
    }


    // public function edit(Pemeliharaan $pemeliharaan): View
    // {
    //     $this->authorize('update', $pemeliharaan);
    //     $user = Auth::user();
    //     /** @var \App\Models\User $user */

    //     $pemeliharaan->load([
    //         'barangQrCode' => fn($q) => $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']),
    //         'pengaju',
    //         'penyetuju',
    //         'operatorPengerjaan'
    //     ]);
    //     $barangQrCode = $pemeliharaan->barangQrCode;

    //     // Otorisasi tambahan ini sudah benar, tidak perlu diubah.
    //     if ($user->hasRole(User::ROLE_OPERATOR) && $user->id !== $pemeliharaan->id_user_pengaju && $user->id !== $pemeliharaan->id_operator_pengerjaan) {
    //         if (!($barangQrCode && (optional($user->ruanganYangDiKelola())->where('id', $barangQrCode->id_ruangan)->exists() || $barangQrCode->id_pemegang_personal === $user->id))) {
    //             abort(403, 'Operator tidak diizinkan mengedit laporan pemeliharaan ini.');
    //         }
    //     } elseif ($user->hasRole(User::ROLE_GURU) && $user->id !== $pemeliharaan->id_user_pengaju) {
    //         abort(403, 'Guru hanya bisa mengedit laporannya sendiri jika status masih "Diajukan".');
    //     }

    //     // Logika untuk menyiapkan daftar PIC sudah benar, tidak perlu diubah.
    //     $picList = collect();
    //     if ($user->hasRole(User::ROLE_ADMIN)) {
    //         $picList = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->orderBy('username')->get();
    //     } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
    //         if ($pemeliharaan->id_operator_pengerjaan === null || $pemeliharaan->id_operator_pengerjaan === $user->id) {
    //             $picList = User::where('id', $user->id)->get();
    //         } else {
    //             $currentPic = User::find($pemeliharaan->id_operator_pengerjaan);
    //             if ($currentPic) $picList->push($currentPic);
    //         }
    //     }

    //     // Menyiapkan data untuk form dropdowns, sudah benar.
    //     $statusPengajuanList = Pemeliharaan::getValidStatusPengajuan();
    //     $statusPengerjaanList = Pemeliharaan::getValidStatusPengerjaan();
    //     // Ambil semua kondisi valid dari model
    //     $semuaKondisi = BarangQrCode::getValidKondisi();

    //     // Kemudian, saring (filter) array tersebut untuk menghapus 'Hilang'
    //     // Kita gunakan konstanta dari model untuk memastikan akurasi
    //     $kondisiBarangList = array_filter($semuaKondisi, function ($kondisi) {
    //         return $kondisi !== \App\Models\BarangQrCode::KONDISI_HILANG;
    //     });
    //     $prioritasOptions = Pemeliharaan::getValidPrioritas();
    //     $rolePrefix = $this->getRolePrefix();

    //     // Mengarahkan ke satu view terpusat di 'pages'
    //     return view('pages.pemeliharaan.edit', compact(
    //         'pemeliharaan',
    //         'barangQrCode',
    //         'picList',
    //         'statusPengajuanList',
    //         'statusPengerjaanList',
    //         'kondisiBarangList',
    //         'prioritasOptions',
    //         'rolePrefix'
    //     ));
    // }


    // public function update(Request $request, Pemeliharaan $pemeliharaan): RedirectResponse
    // {
    //     // Otorisasi dan pengecekan apakah laporan sudah terkunci (sudah benar)
    //     $this->authorize('update', $pemeliharaan);
    //     if ($pemeliharaan->isLocked()) {
    //         return redirect()->route($this->getRedirectUrl("pemeliharaan/{$pemeliharaan->id}"))
    //             ->with('error', 'Laporan yang sudah final tidak dapat diedit lagi.');
    //     }

    //     $user = Auth::user();
    //     /** @var \App\Models\User $user */

    //     $dataLamaPemeliharaan = $pemeliharaan->getAttributes();
    //     // Simpan status lama untuk notifikasi
    //     $oldStatusPengajuan = $pemeliharaan->status_pengajuan;
    //     $oldStatusPengerjaan = $pemeliharaan->status_pengerjaan;
    //     $oldOperatorPengerjaanId = $pemeliharaan->id_operator_pengerjaan;


    //     $pemeliharaan->fill($request->all());

    //     $rules = [];

    //     // ======================================================================
    //     // AWAL LOGIKA VALIDASI BARU DENGAN BLOK 'IF' INDEPENDEN
    //     // ======================================================================

    //     // Aksi 1: Validasi jika STATUS PENGAJUAN berubah (Aksi oleh Admin)
    //     if ($pemeliharaan->isDirty('status_pengajuan')) {
    //         $rules['status_pengajuan'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidStatusPengajuan()))];
    //         if ($request->input('status_pengajuan') === Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI) {
    //             $rules['id_operator_pengerjaan'] = 'required|exists:users,id';
    //         }
    //     }

    //     // Aksi 2: Validasi jika STATUS PENGERJAAN berubah (Aksi oleh Operator/Admin)
    //     if ($pemeliharaan->isDirty('status_pengerjaan')) {
    //         $rules['status_pengerjaan'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidStatusPengerjaan()))];
    //         if (in_array($request->input('status_pengerjaan'), [
    //             Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
    //             Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI,
    //             Pemeliharaan::STATUS_PENGERJAAN_GAGAL
    //         ])) {
    //             $rules['kondisi_barang_setelah_pemeliharaan'] = ['required', Rule::in(BarangQrCode::getValidKondisi())];
    //             $rules['hasil_pemeliharaan'] = 'required|string|max:1000';
    //         }
    //         $rules['deskripsi_pekerjaan'] = 'nullable|string|max:1000';
    //         $rules['biaya'] = 'nullable|numeric|min:0';
    //         $rules['catatan_pengerjaan'] = 'nullable|string|max:1000';
    //         $rules['foto_perbaikan'] = 'nullable|image|mimes:jpeg,jpg,png|max:2048';
    //     }

    //     // Aksi 3: Validasi jika PENGGUNA AWAL mengedit laporannya (tidak ada status yang berubah)
    //     if (!$pemeliharaan->isDirty('status_pengajuan') && !$pemeliharaan->isDirty('status_pengerjaan')) {
    //         if ($user->id === $pemeliharaan->getOriginal('id_user_pengaju') && $pemeliharaan->getOriginal('status_pengajuan') === Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN) {
    //             $rules['catatan_pengajuan'] = 'required|string|max:1000';
    //             $rules['prioritas'] = ['required', Rule::in(array_keys(Pemeliharaan::getValidPrioritas()))];
    //             $rules['foto_kerusakan'] = 'nullable|image|mimes:jpeg,jpg,png|max:2048';
    //         }
    //     }

    //     $validated = $request->validate($rules);

    //     // ======================================================================
    //     // AKHIR LOGIKA VALIDASI BARU
    //     // ======================================================================

    //     try {
    //         DB::beginTransaction();

    //         // Mengisi kembali dengan data yang sudah divalidasi, lebih aman
    //         $pemeliharaan->fill($validated);

    //         // (Logika upload file foto tidak berubah)
    //         if ($request->hasFile('foto_kerusakan')) {
    //             if ($pemeliharaan->foto_kerusakan_path) Storage::disk('public')->delete($pemeliharaan->foto_kerusakan_path);
    //             $pemeliharaan->foto_kerusakan_path = $request->file('foto_kerusakan')->store('pemeliharaan/kerusakan', 'public');
    //         }
    //         if ($request->hasFile('foto_perbaikan')) {
    //             if ($pemeliharaan->foto_perbaikan_path) Storage::disk('public')->delete($pemeliharaan->foto_perbaikan_path);
    //             $pemeliharaan->foto_perbaikan_path = $request->file('foto_perbaikan')->store('pemeliharaan/perbaikan', 'public');
    //         }

    //         // (Logika otomatisasi tanggal tidak berubah, sudah benar)
    //         if ($pemeliharaan->isDirty('status_pengajuan') && in_array($pemeliharaan->status_pengajuan, [Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI, Pemeliharaan::STATUS_PENGAJUAN_DITOLAK])) {
    //             $pemeliharaan->tanggal_persetujuan = now();
    //             $pemeliharaan->id_user_penyetuju = $user->id;
    //         }
    //         if ($pemeliharaan->isDirty('status_pengerjaan')) {
    //             if ($pemeliharaan->status_pengerjaan === Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN && is_null($pemeliharaan->getOriginal('tanggal_mulai_pengerjaan'))) {
    //                 $pemeliharaan->tanggal_mulai_pengerjaan = now();
    //             }
    //             if (in_array($pemeliharaan->status_pengerjaan, [Pemeliharaan::STATUS_PENGERJAAN_SELESAI, Pemeliharaan::STATUS_PENGERJAAN_GAGAL, Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI])) {
    //                 if (is_null($pemeliharaan->getOriginal('tanggal_mulai_pengerjaan'))) {
    //                     $pemeliharaan->tanggal_mulai_pengerjaan = now();
    //                 }
    //                 $pemeliharaan->tanggal_selesai_pengerjaan = now();
    //             }
    //         }

    //         $pemeliharaan->save(); // Trigger event 'saved' di model

    //         // Log aktivitas
    //         $barangQr = $pemeliharaan->barangQrCode()->withTrashed()->first();
    //         $changedData = array_intersect_key($pemeliharaan->getAttributes(), $pemeliharaan->getDirty());
    //         $originalDataFiltered = array_intersect_key($dataLamaPemeliharaan, $pemeliharaan->getDirty());

    //         if (!empty($changedData)) {
    //             LogAktivitas::create([
    //                 'id_user' => Auth::id(),
    //                 'aktivitas' => 'Update Pemeliharaan',
    //                 'deskripsi' => "Memperbarui data pemeliharaan ID: {$pemeliharaan->id} untuk unit " .
    //                     (optional($barangQr)->kode_inventaris_sekolah ?? 'N/A'),
    //                 'model_terkait' => Pemeliharaan::class,
    //                 'id_model_terkait' => $pemeliharaan->id,
    //                 'data_lama' => json_encode($originalDataFiltered),
    //                 'data_baru' => json_encode($changedData),
    //                 'ip_address' => $request->ip(),
    //                 'user_agent' => $request->userAgent(),
    //             ]);
    //         }

    //         DB::commit();

    //         // --- TAMBAHKAN KODE INI UNTUK MENGIRIM NOTIFIKASI KE GURU/PENGAJU ---
    //         // Notifikasi hanya dikirim jika ada perubahan status yang signifikan
    //         $shouldNotify = false;
    //         $reasonForNotification = null;

    //         // 1. Perubahan status_pengajuan dari Diajukan ke Disetujui/Ditolak/Dibatalkan
    //         if ($pemeliharaan->status_pengajuan !== $oldStatusPengajuan && $pemeliharaan->status_pengajuan !== Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN) {
    //             $shouldNotify = true;
    //             if (in_array($pemeliharaan->status_pengajuan, [Pemeliharaan::STATUS_PENGAJUAN_DITOLAK, Pemeliharaan::STATUS_PENGAJUAN_DIBATALKAN])) {
    //                 $reasonForNotification = $pemeliharaan->catatan_persetujuan ?? null; // Jika ada kolom ini untuk alasan penolakan
    //             }
    //         }
    //         // 2. Perubahan status_pengerjaan dari status non-final ke status final
    //         elseif (
    //             $pemeliharaan->status_pengerjaan !== $oldStatusPengerjaan &&
    //             in_array($pemeliharaan->status_pengerjaan, [
    //                 Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
    //                 Pemeliharaan::STATUS_PENGERJAAN_GAGAL,
    //                 Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI
    //             ])
    //         ) {
    //             $shouldNotify = true;
    //             $reasonForNotification = $pemeliharaan->hasil_pemeliharaan ?? $pemeliharaan->catatan_pengerjaan ?? null;
    //         }

    //         if ($shouldNotify && $pemeliharaan->id_user_pengaju) {
    //             $pengaju = User::find($pemeliharaan->id_user_pengaju);
    //             if ($pengaju) {
    //                 $pengaju->notify(new PemeliharaanStatusUpdated($pemeliharaan, $oldStatusPengajuan, $oldStatusPengerjaan, $reasonForNotification));
    //             }
    //         }

    //         // Notifikasi 2: Tugas Pemeliharaan Ditugaskan untuk Operator PIC (BARU)
    //         // Hanya kirim jika status pengajuan berubah menjadi DISETUJUI DAN operator PIC ditetapkan/berubah
    //         if (
    //             $pemeliharaan->status_pengajuan === Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI &&
    //             $pemeliharaan->id_operator_pengerjaan &&
    //             $pemeliharaan->id_operator_pengerjaan !== $oldOperatorPengerjaanId
    //         ) { // Penting: hanya jika PIC baru/berubah
    //             $operatorPic = User::find($pemeliharaan->id_operator_pengerjaan);
    //             if ($operatorPic && $operatorPic->hasRole(User::ROLE_OPERATOR)) { // Pastikan dia operator
    //                 $operatorPic->notify(new PemeliharaanAssigned($pemeliharaan));
    //             }
    //         }
    //         // --- AKHIR KODE NOTIFIKASI ---

    //         // Redirect ke halaman detail
    //         $redirectUrl = $this->getRedirectUrl("pemeliharaan/{$pemeliharaan->id}");
    //         return redirect($redirectUrl)->with('success', 'Data pemeliharaan berhasil diperbarui.');
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         DB::rollBack();
    //         Log::warning("Validation failed for Pemeliharaan update {$pemeliharaan->id}: ", $e->errors());
    //         return redirect()->back()
    //             ->withErrors($e->validator)
    //             ->withInput()
    //             ->with('error_form_type', 'edit')
    //             ->with('error_pemeliharaan_id', $pemeliharaan->id);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Gagal memperbarui pemeliharaan {$pemeliharaan->id}: " . $e->getMessage(), [
    //             'exception' => $e,
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return redirect()->back()
    //             ->with('error', 'Gagal memperbarui data pemeliharaan. Kesalahan: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }

    // Di dalam PemeliharaanController.php

    public function edit(Pemeliharaan $pemeliharaan)
    {
        // Cek apakah laporan 'terkunci' (sudah final)
        if ($pemeliharaan->isLocked()) {
            return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
                ->with('error', 'Laporan yang sudah diproses atau final tidak dapat diedit.');
        }

        $this->authorize('update', $pemeliharaan); // Anda perlu policy untuk 'update'

        // Logika filter yang sama seperti di method create
        $finalStatuses = [Pemeliharaan::STATUS_SELESAI, Pemeliharaan::STATUS_TUNTAS, Pemeliharaan::STATUS_DITOLAK];
        $activeMaintenanceBarangIds = Pemeliharaan::whereNotIn('status_pengajuan', [$finalStatuses[2]])
            ->whereNotIn('status_pengerjaan', [$finalStatuses[0]])
            ->pluck('id_barang_qr_code');

        // Ambil daftar barang, KECUALI barang yang sedang diedit saat ini
        $barangList = \App\Models\BarangQrCode::whereNotIn('id', $activeMaintenanceBarangIds->except($pemeliharaan->id_barang_qr_code))
            ->whereNull('deleted_at')
            ->with('barang')
            ->get();

        $prioritasList = Pemeliharaan::getValidPrioritas();

        return view('pages.pemeliharaan.edit', compact('pemeliharaan', 'barangList', 'prioritasList'));
    }

    public function update(Request $request, Pemeliharaan $pemeliharaan)
    {
        if ($pemeliharaan->isLocked()) {
            return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
                ->with('error', 'Laporan yang sudah diproses atau final tidak dapat diedit.');
        }

        $this->authorize('update', $pemeliharaan);

        $validated = $request->validate([
            'id_barang_qr_code' => 'required|exists:barang_qr_codes,id',
            'catatan_pengajuan' => 'required|string|max:2000',
            'prioritas' => ['required', \Illuminate\Validation\Rule::in(array_keys(Pemeliharaan::getValidPrioritas()))],
        ]);

        $pemeliharaan->update($validated);

        return redirect()->route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pemeliharaan->id)
            ->with('success', 'Laporan pemeliharaan berhasil diperbarui.');
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
        /** @var \App\Models\User $user */

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
        /** @var \App\Models\User $user */

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
