<?php

namespace App\Http\Controllers; // Sesuaikan dengan namespace Anda

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use App\Models\User;
use App\Models\LogAktivitas;
use App\Models\ArsipBarang;
use App\Models\MutasiBarang;
use App\Models\BarangStatus; // Pastikan ini di-use
use App\Models\Peminjaman;
use App\Models\Pemeliharaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode as GeneratorQrCode;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Traits\RedirectsUsers;
use App\Notifications\BarangAssignedToPersonal;
use App\Notifications\BarangMutated;
use App\Notifications\BarangReturnedFromPersonal;
use App\Notifications\BarangTransferredPersonal;

class BarangQrCodeController extends Controller
{
    use AuthorizesRequests;
    use RedirectsUsers;

    /**
     * Menampilkan daftar semua unit barang dengan filter dan paginasi.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BarangQrCode::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        // Opsi filter (tidak berubah)
        $filterOptions = [
            'aktif' => 'Semua Unit Aktif',
            'tersedia' => 'Tersedia',
            'dipesan' => 'Dipesan (Proses Peminjaman)',
            'dipinjam' => 'Dipinjam',
            'pemeliharaan' => 'Dalam Pemeliharaan',
            'rusak_berat_aktif' => 'Rusak Berat (Aktif)',
            'hilang' => 'Hilang (Diarsipkan)',
            'diarsipkan_lain' => 'Diarsipkan (Alasan Lain)',
            'semua' => 'Tampilkan Semua (Aktif & Diarsipkan)',
        ];
        $filterUtama = $request->input('filter_utama', 'aktif');

        // 1. Inisialisasi Query Builder dasar
        $qrCodesQuery = BarangQrCode::query()->with([
            'barang.kategori',
            'ruangan',
            'pemegangPersonal',
            'arsip',
            'peminjamanDetails' => function ($q) {
                $q->whereHas('peminjaman', function ($p) {
                    $p->whereNotIn('status', [Peminjaman::STATUS_SELESAI, Peminjaman::STATUS_DITOLAK, Peminjaman::STATUS_DIBATALKAN]);
                });
            }
        ]);

        // ======================================================================
        // AWAL LOGIKA BARU YANG SEPENUHNYA TERPISAH ANTAR PERAN
        // ======================================================================

        // 2. Terapkan filter berdasarkan peran terlebih dahulu
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            // Jika Operator, terapkan batasan kewenangan
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $qrCodesQuery->where(function ($roleQuery) use ($ruanganOperatorIds, $user) {
                $roleQuery->where('id_pemegang_personal', $user->id)
                    ->orWhereIn('id_ruangan', $ruanganOperatorIds)
                    ->orWhere(function ($qFloating) {
                        $qFloating->whereNull('id_ruangan')->whereNull('id_pemegang_personal');
                    });
            });
        }
        // Untuk Admin, tidak ada batasan peran tambahan yang diterapkan. Query tetap luas.


        // 3. Terapkan filter utama (filter paling atas di UI)
        switch ($filterUtama) {
            case 'tersedia':
                $qrCodesQuery->whereNull('deleted_at')->where('status', BarangQrCode::STATUS_TERSEDIA);
                break;
            case 'dipesan':
                $qrCodesQuery->whereNull('deleted_at')
                    ->where('status', BarangQrCode::STATUS_TERSEDIA)
                    ->whereHas('peminjamanDetails', function ($q) {
                        $q->whereHas('peminjaman', fn($p) => $p->whereIn('status', [Peminjaman::STATUS_MENUNGGU_PERSETUJUAN, Peminjaman::STATUS_DISETUJUI]));
                    });
                break;
            case 'dipinjam':
                $qrCodesQuery->whereNull('deleted_at')->where('status', BarangQrCode::STATUS_DIPINJAM);
                break;
            case 'pemeliharaan':
                $qrCodesQuery->whereNull('deleted_at')->where('status', BarangQrCode::STATUS_DALAM_PEMELIHARAAN);
                break;
            case 'rusak_berat_aktif':
                $qrCodesQuery->whereNull('deleted_at')->where('kondisi', BarangQrCode::KONDISI_RUSAK_BERAT);
                break;
            case 'hilang':
                $qrCodesQuery->onlyTrashed()->where('kondisi', BarangQrCode::KONDISI_HILANG);
                break;
            case 'diarsipkan_lain':
                $qrCodesQuery->onlyTrashed()->whereHas('arsip', fn($q) => $q->where('jenis_penghapusan', '!=', 'Hilang'));
                break;
            case 'semua':
                $qrCodesQuery->withTrashed();
                break;
            default: // 'aktif'
                $qrCodesQuery->whereNull('deleted_at');
                break;
        }

        // 4. Terapkan filter sekunder dari dropdown (ruangan, kategori, dll) dan pencarian
        // Method filter() ini sekarang akan menambahkan kondisi AND pada query yang sudah ada
        $qrCodesQuery->filter($request);


        // ======================================================================
        // AKHIR LOGIKA BARU
        // ======================================================================


        // Paginasi dan pengambilan data
        $qrCodes = $qrCodesQuery->latest('id')->paginate(15)->withQueryString();

        // Data untuk dropdown filter
        $barangList = Barang::orderBy('nama_barang')->get();
        $ruanganList = $user->hasRole(User::ROLE_ADMIN)
            ? Ruangan::orderBy('nama_ruangan')->get()
            : $user->ruanganYangDiKelola()->orderBy('nama_ruangan')->get();
        $rolePrefix = $this->getRolePrefix();

        return view('pages.barang_qr_code.index', compact(
            'qrCodes',
            'ruanganList',
            'barangList',
            'request',
            'rolePrefix',
            'filterOptions',
            'filterUtama'
        ));
    }


    /**
     * Menampilkan form untuk menambahkan satu atau beberapa unit fisik baru.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $barangId = $request->query('barang_id');
        if (!$barangId) {
            return redirect()->route('barang.index')->with('error', 'Jenis Barang Induk tidak ditemukan atau tidak dipilih.');
        }

        $barang = Barang::find($barangId);
        if (!$barang) {
            return redirect()->route('barang.index')->with('error', "Jenis Barang Induk dengan ID {$barangId} tidak ditemukan.");
        }

        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR])) {
            abort(403, 'Anda tidak memiliki izin untuk menambahkan unit barang.');
        }

        if (!$barang->menggunakan_nomor_seri) {
            return redirect()->route('barang.show', $barang->id)
                ->with('warning', "Jenis barang '{$barang->nama_barang}' tidak dikelola per unit individual (tidak menggunakan nomor seri).");
        }

        $ruanganList = $user->hasRole([User::ROLE_OPERATOR])
            ? $user->ruanganYangDiKelola()->orderBy('nama_ruangan')->get()
            : Ruangan::orderBy('nama_ruangan')->get();

        $pemegangList = User::whereIn('role', [User::ROLE_GURU, User::ROLE_OPERATOR, User::ROLE_ADMIN])
            ->orderBy('username')->get();

        $kondisiOptions = BarangQrCode::getValidKondisi();
        // SESUDAH (BENAR)
        $jumlah_unit = filter_var($request->query('jumlah_unit', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);

        return view('pages.barang_qr_code.create_units', compact(
            'barang',
            'jumlah_unit',
            'ruanganList',
            'pemegangList',
            'kondisiOptions'
        ));
    }

    /**
     * Menyimpan satu atau beberapa unit fisik baru (BarangQrCode) ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $barangId = $request->input('id_barang');
        $barang = Barang::find($barangId);

        if (!$barang) {
            return redirect()->route('admin.barang.index')->with('error', 'Jenis Barang Induk tidak valid.');
        }
        if (!$barang->menggunakan_nomor_seri) {
            return redirect()->route('admin.barang.show', $barang->id)->with('error', 'Tidak dapat menambahkan unit individual karena jenis barang ini tidak menggunakan nomor seri.');
        }

        $userPencatat = Auth::user();
        /** @var \App\Models\User $userPencatat */
        if (!$userPencatat->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR])) {
            abort(403, 'Anda tidak memiliki izin untuk menyimpan unit barang.');
        }

        $validated = $request->validate([
            'id_barang' => 'required|exists:barangs,id',
            'units' => 'required|array|min:1',
            'units.*.id_ruangan' => ['nullable', 'exists:ruangans,id'],
            'units.*.id_pemegang_personal' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $ruanganValue = $request->input("units.{$index}.id_ruangan");
                    if ($value && $ruanganValue) {
                        $fail('Unit ke-' . ($index + 1) . ' tidak bisa ditempatkan di ruangan dan dipegang personal sekaligus.');
                    }
                    // Kebijakan opsional: Barang baru harus punya lokasi atau pemegang.
                    // if (!$value && !$ruanganValue) {
                    //     $fail('Unit ke-' . ($index + 1) . ' harus ditempatkan di ruangan atau dipegang oleh personal.');
                    // }
                },
            ],
            'units.*.no_seri_pabrik' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('barang_qr_codes', 'no_seri_pabrik')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($request) {
                    if (empty($value)) return;
                    $currentIndex = explode('.', $attribute)[1];
                    $allSerialsInput = $request->input('units');
                    foreach ($allSerialsInput as $index => $unitInput) {
                        if ($index != $currentIndex && !empty($unitInput['no_seri_pabrik']) && $unitInput['no_seri_pabrik'] === $value) {
                            $fail('Nomor Seri Pabrik duplikat dalam pengajuan (Unit ke-' . ($currentIndex + 1) . ' & ' . ($index + 1) . ').');
                            return;
                        }
                    }
                },
            ],
            'units.*.harga_perolehan_unit' => 'required|numeric|min:0',
            'units.*.tanggal_perolehan_unit' => 'required|date|before_or_equal:today',
            'units.*.sumber_dana_unit' => 'nullable|string|max:255',
            'units.*.no_dokumen_perolehan_unit' => 'nullable|string|max:255',
            'units.*.kondisi' => ['required', Rule::in(BarangQrCode::getValidKondisi())],
            'units.*.deskripsi_unit' => 'nullable|string|max:1000',
        ], [
            'units.*.id_ruangan.exists' => 'Ruangan unit ke-:position tidak valid.',
            'units.*.id_pemegang_personal.exists' => 'Pemegang personal unit ke-:position tidak valid.',
            'units.*.no_seri_pabrik.unique' => 'No. Seri Pabrik unit ke-:position sudah ada.',
            'units.*.harga_perolehan_unit.required' => 'Harga unit ke-:position wajib.',
            'units.*.tanggal_perolehan_unit.required' => 'Tgl. perolehan unit ke-:position wajib.',
            'units.*.kondisi.required' => 'Kondisi unit ke-:position wajib.',
        ]);

        DB::beginTransaction();
        try {
            $createdUnitsCount = 0;
            $pencatatId = $userPencatat->id;

            foreach ($validated['units'] as $unitData) {
                $newUnit = BarangQrCode::createWithQrCodeImage(
                    idBarang: $barang->id,
                    idRuangan: $unitData['id_ruangan'] ?? null,
                    noSeriPabrik: $unitData['no_seri_pabrik'] ?? null,
                    hargaPerolehanUnit: $unitData['harga_perolehan_unit'],
                    tanggalPerolehanUnit: $unitData['tanggal_perolehan_unit'],
                    sumberDanaUnit: $unitData['sumber_dana_unit'] ?? null,
                    noDokumenPerolehanUnit: $unitData['no_dokumen_perolehan_unit'] ?? null,
                    kondisi: $unitData['kondisi'],
                    status: BarangQrCode::STATUS_TERSEDIA,
                    deskripsiUnit: $unitData['deskripsi_unit'] ?? null,
                    idPemegangPersonal: $unitData['id_pemegang_personal'] ?? null,
                    idPemegangPencatat: $pencatatId
                );

                LogAktivitas::create([
                    'id_user' => $userPencatat->id,
                    'aktivitas' => 'Tambah Unit Barang',
                    'deskripsi' => "Menambahkan unit: {$newUnit->kode_inventaris_sekolah} untuk barang: {$barang->nama_barang}",
                    'model_terkait' => BarangQrCode::class,
                    'id_model_terkait' => $newUnit->id,
                    'data_baru' => $newUnit->toJson(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                $createdUnitsCount++;
            }
            DB::commit();
            return redirect()->route('admin.barang.show', $barang->id)->with('success', "{$createdUnitsCount} unit barang berhasil ditambahkan untuk '{$barang->nama_barang}'.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan unit BarangQrCode (Barang ID {$barang->id}): " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal menyimpan unit: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.'))->withInput();
        }
    }


    /**
     * Menampilkan detail lengkap (KIB) dari satu unit barang.
     * Juga menyiapkan data untuk modal-modal aksi.
     */
    public function show(BarangQrCode $barangQrCode): View
    {
        $this->authorize('view', $barangQrCode);

        $qrCode = $barangQrCode->load([
            'barang.kategori',
            'ruangan', // Eager load ruangan jika ada
            'pemegangPersonal', // Eager load pemegangPersonal jika ada
            'mutasiDetails' => fn($q) => $q->with(['ruanganAsal', 'ruanganTujuan', 'admin'])->orderBy('tanggal_mutasi', 'desc'),
            'peminjamanDetails' => fn($q) => $q->with(['peminjaman.guru'])->orderBy('created_at', 'desc'),
            'pemeliharaanRecords' => fn($q) => $q->with(['pengaju', 'operatorPengerjaan', 'penyetuju'])->orderBy('tanggal_pengajuan', 'desc'),
            'barangStatuses' => fn($q) => $q->with(['userPencatat', 'ruanganSebelumnya', 'ruanganSesudahnya', 'pemegangPersonalSebelumnya', 'pemegangPersonalSesudahnya'])->orderBy('tanggal_pencatatan', 'desc'),
            'arsip'
        ]);

        // Data umum yang mungkin dibutuhkan oleh beberapa modal atau halaman KIB itu sendiri
        $ruanganListAll = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        $kondisiOptionsAll = BarangQrCode::getValidKondisi();
        $statusOptionsAll = BarangQrCode::getValidStatus();
        $jenisPenghapusanOptions = ArsipBarang::getValidJenisPenghapusan();
        $user = Auth::user();
        /** @var \App\Models\User $user */


        // ===== AWAL LOGIKA BARU UNTUK TOMBOL PEMELIHARAAN =====

        $bisaLaporkanKerusakan = false; // Default-nya tidak bisa

        // 1. Cek hak akses dasar untuk membuat laporan
        if ($user->can('create', \App\Models\Pemeliharaan::class)) {
            // 2. Cek apakah barang ini dalam status yang valid (tidak sedang dipinjam atau sudah dalam pemeliharaan)
            $statusValid = !in_array($qrCode->status, [BarangQrCode::STATUS_DIPINJAM, BarangQrCode::STATUS_DALAM_PEMELIHARAAN]);

            // 3. Cek apakah sudah ada laporan aktif untuk barang ini
            $tidakAdaLaporanAktif = !$qrCode->pemeliharaanRecords()
                ->where(function ($query) {
                    // Laporan dianggap aktif jika statusnya 'Diajukan'
                    $query->where('status_pengajuan', \App\Models\Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN)
                        // ATAU jika statusnya 'Disetujui' TAPI pengerjaannya BELUM final
                        ->orWhere(function ($q) {
                            $q->where('status_pengajuan', \App\Models\Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI)
                                ->whereNotIn('status_pengerjaan', [
                                    \App\Models\Pemeliharaan::STATUS_PENGERJAAN_SELESAI,
                                    \App\Models\Pemeliharaan::STATUS_PENGERJAAN_GAGAL,
                                    \App\Models\Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI,
                                ]);
                        });
                })
                ->exists();

            // 4. Cek kepemilikan atau kewenangan (Guru hanya bisa lapor miliknya, Operator lapor miliknya atau di ruangannya)
            $punyaKewenangan = false;
            if ($user->hasRole(User::ROLE_ADMIN)) {
                $punyaKewenangan = true;
            } elseif ($user->hasRole(User::ROLE_GURU)) {
                $punyaKewenangan = ($qrCode->id_pemegang_personal === $user->id);
            } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
                $punyaKewenangan = ($qrCode->id_pemegang_personal === $user->id) || ($qrCode->id_ruangan && $user->ruanganYangDiKelola()->where('id', $qrCode->id_ruangan)->exists());
            }

            // Jika semua kondisi terpenuhi, maka tombol boleh muncul
            if ($statusValid && $tidakAdaLaporanAktif && $punyaKewenangan) {
                $bisaLaporkanKerusakan = true;
            }
        }
        // ===== AKHIR LOGIKA BARU =====


        // 1. Data untuk Modal "Serahkan ke Personal"
        // Pengguna yang bisa dipilih untuk diserahi barang (semua user aktif)
        $eligibleUsersForAssign = User::orderBy('username', 'asc')->get();

        // 2. Data untuk Modal "Kembalikan ke Ruangan"
        // Daftar ruangan tujuan, difilter untuk Operator
        $ruangansQueryForReturn = Ruangan::query();
        if ($user && $user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $ruangansQueryForReturn->whereIn('id', $ruanganOperatorIds);
        }
        $ruangansForReturnForm = $ruangansQueryForReturn->orderBy('nama_ruangan', 'asc')->get();

        // 3. Data untuk Modal "Transfer Personal"
        // Pengguna yang bisa dipilih untuk menerima transfer (semua user aktif kecuali pemegang saat ini)
        $eligibleUsersForTransfer = collect(); // Default koleksi kosong
        if ($qrCode->id_pemegang_personal) {
            $eligibleUsersForTransfer = User::where('id', '!=', $qrCode->id_pemegang_personal)
                ->orderBy('username', 'asc')
                ->get();
        }

        $rolePrefix = $this->getRolePrefix();

        return view('pages.barang_qr_code.show', compact(
            'qrCode',
            'ruanganListAll',
            'kondisiOptionsAll',
            'statusOptionsAll',
            'jenisPenghapusanOptions',
            'eligibleUsersForAssign',
            'ruangansForReturnForm',
            'eligibleUsersForTransfer',
            'rolePrefix',
            'bisaLaporkanKerusakan'
        ));
    }

    /**
     * Menampilkan form untuk mengedit detail atribut dasar unit barang.
     * Tidak untuk transisi lokasi/pemegang.
     */
    public function edit(BarangQrCode $barangQrCode): View|RedirectResponse
    {
        $this->authorize('update', $barangQrCode);
        $statusOptions = BarangQrCode::getValidStatus();
        $kondisiOptions = BarangQrCode::getValidKondisi();

        if ($barangQrCode->trashed()) {
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)
                ->with('error', 'Barang yang sudah diarsipkan tidak dapat diedit.');
        }
        // Untuk form edit ini, kita tidak menyediakan pilihan ruangan atau pemegang personal
        // karena transisi tersebut memiliki alur tersendiri.

        return view('admin.barang_qr_code.edit', compact('barangQrCode', 'statusOptions', 'kondisiOptions'));
    }

    /**
     * Memperbarui detail atribut dasar unit barang di database.
     * Metode ini TIDAK untuk mengubah id_ruangan atau id_pemegang_personal.
     */
    public function update(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('update', $barangQrCode);
        $userPencatat = Auth::user();
        /** @var \App\Models\User $userPencatat */

        // PENYESUAIAN: Tambahkan penjaga untuk item yang diarsipkan
        if ($barangQrCode->trashed()) {
            return back()->with('error', 'Barang yang sudah diarsipkan tidak dapat diperbarui.');
        }

        $validated = $request->validate([
            'no_seri_pabrik' => ['nullable', 'string', 'max:255', Rule::unique('barang_qr_codes', 'no_seri_pabrik')->ignore($barangQrCode->id)->whereNull('deleted_at')],
            'kode_inventaris_sekolah' => ['required', 'string', 'max:255', Rule::unique('barang_qr_codes', 'kode_inventaris_sekolah')->ignore($barangQrCode->id)->whereNull('deleted_at')],
            'harga_perolehan_unit' => 'required|numeric|min:0',
            'tanggal_perolehan_unit' => 'required|date|before_or_equal:today',
            'sumber_dana_unit' => 'nullable|string|max:255',
            'no_dokumen_perolehan_unit' => 'nullable|string|max:255',
            'kondisi' => ['required', Rule::in(BarangQrCode::getValidKondisi())],
            'status' => ['required', Rule::in(BarangQrCode::getValidStatus())], // Hati-hati jika status diubah di sini
            'deskripsi_unit' => 'nullable|string|max:1000',
        ]);

        $dataToUpdate = $validated;
        $oldData = $barangQrCode->getRawOriginal(); // Mengambil data mentah sebelum di-fill
        $originalForLog = collect($oldData)->only(array_keys($validated))->all(); // Hanya data lama dari field yang divalidasi
        $oldQrPath = $barangQrCode->qr_path;

        if ($barangQrCode->kode_inventaris_sekolah !== $validated['kode_inventaris_sekolah']) {
            if ($oldQrPath && Storage::disk('public')->exists($oldQrPath)) {
                Storage::disk('public')->delete($oldQrPath);
            }
            $qrContent = $validated['kode_inventaris_sekolah'];
            $directory = 'qr_codes';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $filename = $directory . '/' . Str::slug($qrContent . '-' . Str::random(5)) . '.svg'; // Tambah random untuk unik
            $qrImage = GeneratorQrCode::format('svg')->size(200)->generate($qrContent);
            Storage::disk('public')->put($filename, $qrImage);
            $dataToUpdate['qr_path'] = $filename;
        }

        $barangQrCode->fill($dataToUpdate);

        if ($barangQrCode->isDirty()) {
            $changedAttributes = $barangQrCode->getDirty();
            $barangQrCode->save();

            // Log BarangStatus jika kondisi atau status berubah
            if (isset($changedAttributes['kondisi']) || isset($changedAttributes['status'])) {
                BarangStatus::create([
                    'id_barang_qr_code' => $barangQrCode->id,
                    'id_user_pencatat' => $userPencatat->id,
                    'tanggal_pencatatan' => now(),
                    'kondisi_sebelumnya' => $oldData['kondisi'] ?? null,
                    'kondisi_sesudahnya' => $barangQrCode->kondisi,
                    'status_ketersediaan_sebelumnya' => $oldData['status'] ?? null,
                    'status_ketersediaan_sesudahnya' => $barangQrCode->status,
                    'id_ruangan_sebelumnya' => $barangQrCode->id_ruangan, // Tidak berubah oleh form ini
                    'id_ruangan_sesudahnya' => $barangQrCode->id_ruangan,
                    'id_pemegang_personal_sebelumnya' => $barangQrCode->id_pemegang_personal,
                    'id_pemegang_personal_sesudahnya' => $barangQrCode->id_pemegang_personal,
                    'deskripsi_kejadian' => 'Update detail atribut unit barang.',
                ]);
            }

            LogAktivitas::create([
                'id_user' => $userPencatat->id,
                'aktivitas' => 'Update Unit Barang',
                'deskripsi' => "Memperbarui unit: {$barangQrCode->kode_inventaris_sekolah}",
                'model_terkait' => BarangQrCode::class,
                'id_model_terkait' => $barangQrCode->id,
                'data_lama' => json_encode(array_intersect_key($originalForLog, $changedAttributes)),
                'data_baru' => json_encode($changedAttributes),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return redirect()->route('admin.barang-qr-code.show', $barangQrCode->id)->with('success', 'Data unit barang berhasil diperbarui.');
        }

        return redirect()->route('admin.barang-qr-code.show', $barangQrCode->id)->with('info', 'Tidak ada perubahan data.');
    }


    /**
     * Memproses mutasi (perpindahan) unit barang dari satu ruangan ke ruangan lain.
     */
    public function mutasi(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('mutasi', $barangQrCode);

        if ($barangQrCode->trashed() || $barangQrCode->status === BarangQrCode::STATUS_DIPINJAM || $barangQrCode->id_pemegang_personal !== null) {
            return back()->with('error', 'Aksi tidak diizinkan. Barang mungkin diarsipkan, dipinjam, atau sedang dipegang personal.');
        }

        $validated = $request->validate([
            'id_ruangan_tujuan' => ['required', 'exists:ruangans,id', Rule::notIn([$barangQrCode->id_ruangan])],
            'alasan_pemindahan' => 'required|string|max:1000',
            'surat_pemindahan_path' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ], [
            'id_ruangan_tujuan.not_in' => 'Ruangan tujuan tidak boleh sama dengan ruangan saat ini.',
            'alasan_pemindahan.required' => 'Alasan pemindahan wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            $userPencatat = Auth::user();
            $ruanganTujuan = Ruangan::find($validated['id_ruangan_tujuan']);

            $snapshot = ['id_ruangan' => $barangQrCode->id_ruangan, 'nama_ruangan' => optional($barangQrCode->ruangan)->nama_ruangan, 'kondisi' => $barangQrCode->kondisi, 'status' => $barangQrCode->status];
            $ruanganAsal = Ruangan::find($snapshot['id_ruangan']);

            $suratPath = $request->hasFile('surat_pemindahan_path') ? $request->file('surat_pemindahan_path')->store('mutasi/dokumen', 'public') : null;

            $mutasi = MutasiBarang::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'jenis_mutasi' => 'Antar Ruangan',
                'id_ruangan_asal' => $snapshot['id_ruangan'],
                'id_pemegang_asal' => null, // Eksplisit set null
                'id_ruangan_tujuan' => $ruanganTujuan->id,
                'id_pemegang_tujuan' => null, // Eksplisit set null
                'alasan_pemindahan' => $validated['alasan_pemindahan'],
                'id_user_admin' => $userPencatat->id,
                'surat_pemindahan_path' => $suratPath,
                'tanggal_mutasi' => now(),
            ]);

            $barangQrCode->update(['id_ruangan' => $ruanganTujuan->id]);

            BarangStatus::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'id_user_pencatat' => $userPencatat->id,
                'tanggal_pencatatan' => now(),
                'id_ruangan_sebelumnya' => $snapshot['id_ruangan'],
                'id_ruangan_sesudahnya' => $barangQrCode->id_ruangan,
                'kondisi_sebelumnya' => $snapshot['kondisi'],
                'kondisi_sesudahnya' => $barangQrCode->kondisi,
                'status_ketersediaan_sebelumnya' => $snapshot['status'],
                'status_ketersediaan_sesudahnya' => $barangQrCode->status,
                'deskripsi_kejadian' => "Mutasi dari '{$snapshot['nama_ruangan']}' ke '{$ruanganTujuan->nama_ruangan}'",
                'id_mutasi_barang_trigger' => $mutasi->id,
            ]);

            LogAktivitas::create([
                'id_user' => $userPencatat->id,
                'aktivitas' => 'Mutasi Unit Barang',
                'deskripsi' => "Memutasi unit {$barangQrCode->kode_inventaris_sekolah} dari '{$snapshot['nama_ruangan']}' ke '{$ruanganTujuan->nama_ruangan}'.",
                'model_terkait' => MutasiBarang::class,
                'id_model_terkait' => $mutasi->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if (optional($ruanganAsal)->operator && optional($ruanganAsal->operator)->id !== $userPencatat->id) {
                $ruanganAsal->operator->notify(new BarangMutated($mutasi, $userPencatat));
            }
            if (optional($ruanganTujuan->operator)->id && optional($ruanganTujuan->operator)->id !== $userPencatat->id && optional($ruanganTujuan->operator)->id !== optional($ruanganAsal->operator)->id) {
                $ruanganTujuan->operator->notify(new BarangMutated($mutasi, $userPencatat));
            }

            DB::commit();
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)->with('success', "Unit barang berhasil dipindahkan ke: {$ruanganTujuan->nama_ruangan}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error mutating BarangQrCode (ID: {$barangQrCode->id}): {$e->getMessage()}");
            return back()->with('error', 'Gagal memproses perpindahan unit.')->withInput();
        }
    }

    /**
     * Memproses penyerahan unit barang ke pemegang personal.
     */
    public function assignPersonal(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('assignPersonal', $barangQrCode);

        if ($barangQrCode->trashed() || $barangQrCode->status !== BarangQrCode::STATUS_TERSEDIA) {
            return back()->with('error', 'Hanya barang dengan status "Tersedia" yang dapat diserahkan.');
        }

        $validated = $request->validate([
            'id_pemegang_personal' => ['required', 'exists:users,id', Rule::notIn([$barangQrCode->id_pemegang_personal])],
            'alasan_pemindahan' => 'required|string|max:1000',
        ], ['id_pemegang_personal.not_in' => 'Pemegang baru tidak boleh sama.', 'alasan_pemindahan.required' => 'Alasan penyerahan wajib diisi.']);

        DB::beginTransaction();
        try {
            $userPencatat = Auth::user();
            $userPenerima = User::find($validated['id_pemegang_personal']);

            $snapshot = ['id_ruangan' => $barangQrCode->id_ruangan, 'nama_ruangan' => optional($barangQrCode->ruangan)->nama_ruangan, 'kondisi' => $barangQrCode->kondisi, 'status' => $barangQrCode->status];

            $mutasi = MutasiBarang::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'jenis_mutasi' => 'Ruangan ke Personal',
                'id_ruangan_asal' => $snapshot['id_ruangan'],
                'id_pemegang_asal' => null, // Eksplisit set null
                'id_ruangan_tujuan' => null, // Eksplisit set null
                'id_pemegang_tujuan' => $userPenerima->id,
                'alasan_pemindahan' => $validated['alasan_pemindahan'],
                'id_user_admin' => $userPencatat->id,
                'tanggal_mutasi' => now(),
            ]);

            $barangQrCode->update(['id_pemegang_personal' => $userPenerima->id, 'id_ruangan' => null]);

            BarangStatus::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'id_user_pencatat' => $userPencatat->id,
                'tanggal_pencatatan' => now(),
                'id_ruangan_sebelumnya' => $snapshot['id_ruangan'],
                'id_pemegang_personal_sesudahnya' => $userPenerima->id,
                'kondisi_sebelumnya' => $snapshot['kondisi'],
                'kondisi_sesudahnya' => $barangQrCode->kondisi,
                'status_ketersediaan_sebelumnya' => $snapshot['status'],
                'status_ketersediaan_sesudahnya' => $barangQrCode->status,
                'deskripsi_kejadian' => "Diserahkan dari ruangan '{$snapshot['nama_ruangan']}' ke {$userPenerima->username}",
                'id_mutasi_barang_trigger' => $mutasi->id
            ]);

            LogAktivitas::create([
                'id_user' => $userPencatat->id,
                'aktivitas' => 'Serah Terima ke Personal',
                'deskripsi' => "Menyerahkan unit: {$barangQrCode->kode_inventaris_sekolah} ke {$userPenerima->username}.",
                'model_terkait' => MutasiBarang::class,
                'id_model_terkait' => $mutasi->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // KODE YANG BENAR
            $userPenerima->notify(new BarangAssignedToPersonal($barangQrCode, $userPencatat));
            DB::commit();
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)->with('success', "Barang berhasil diserahkan ke {$userPenerima->username}.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error assigning personal BarangQrCode (ID: {$barangQrCode->id}): {$e->getMessage()}");
            return back()->with('error', 'Gagal menyerahkan barang.')->withInput();
        }
    }

    /**
     * Memproses pengembalian barang dari pemegang personal ke ruangan.
     */
    public function returnFromPersonal(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('returnPersonal', $barangQrCode);

        if ($barangQrCode->id_pemegang_personal === null || $barangQrCode->trashed() || $barangQrCode->status === BarangQrCode::STATUS_DIPINJAM) {
            return back()->with('error', 'Aksi tidak diizinkan. Barang tidak dipegang personal, diarsipkan, atau sedang dipinjam.');
        }

        $validated = $request->validate([
            'id_ruangan_tujuan' => 'required|exists:ruangans,id',
            'alasan_pemindahan' => 'required|string|max:1000',
        ], ['alasan_pemindahan.required' => 'Alasan pengembalian wajib diisi.']);

        DB::beginTransaction();
        try {
            $userPencatat = Auth::user();
            $ruanganTujuan = Ruangan::find($validated['id_ruangan_tujuan']);

            $snapshot = ['id_pemegang' => $barangQrCode->id_pemegang_personal, 'nama_pemegang' => optional($barangQrCode->pemegangPersonal)->username, 'kondisi' => $barangQrCode->kondisi, 'status' => $barangQrCode->status];
            $pemegangLama = User::find($snapshot['id_pemegang']);

            $mutasi = MutasiBarang::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'jenis_mutasi' => 'Personal ke Ruangan',
                'id_pemegang_asal' => $snapshot['id_pemegang'],
                'id_ruangan_asal' => null, // Eksplisit set null
                'id_ruangan_tujuan' => $ruanganTujuan->id,
                'id_pemegang_tujuan' => null, // Eksplisit set null
                'alasan_pemindahan' => $validated['alasan_pemindahan'],
                'id_user_admin' => $userPencatat->id,
                'tanggal_mutasi' => now(),
            ]);

            $barangQrCode->update(['id_ruangan' => $ruanganTujuan->id, 'id_pemegang_personal' => null]);

            BarangStatus::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'id_user_pencatat' => $userPencatat->id,
                'tanggal_pencatatan' => now(),
                'id_ruangan_sesudahnya' => $ruanganTujuan->id,
                'id_pemegang_personal_sebelumnya' => $snapshot['id_pemegang'],
                'kondisi_sebelumnya' => $snapshot['kondisi'],
                'kondisi_sesudahnya' => $barangQrCode->kondisi,
                'status_ketersediaan_sebelumnya' => $snapshot['status'],
                'status_ketersediaan_sesudahnya' => $barangQrCode->status,
                'deskripsi_kejadian' => "Dikembalikan dari {$snapshot['nama_pemegang']} ke ruangan {$ruanganTujuan->nama_ruangan}",
                'id_mutasi_barang_trigger' => $mutasi->id,
            ]);

            LogAktivitas::create([
                'id_user' => $userPencatat->id,
                'aktivitas' => 'Pengembalian dari Personal',
                'deskripsi' => "Mengembalikan unit {$barangQrCode->kode_inventaris_sekolah} dari {$snapshot['nama_pemegang']} ke ruangan {$ruanganTujuan->nama_ruangan}.",
                'model_terkait' => MutasiBarang::class,
                'id_model_terkait' => $mutasi->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if (optional($ruanganTujuan->operator)->id && $ruanganTujuan->operator->id !== $userPencatat->id) {
                $ruanganTujuan->operator->notify(new BarangReturnedFromPersonal($barangQrCode, $ruanganTujuan, $pemegangLama));
            }

            DB::commit();
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)->with('success', "Barang berhasil dikembalikan ke ruangan {$ruanganTujuan->nama_ruangan}.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error returning from personal (BarangQrCode ID: {$barangQrCode->id}): {$e->getMessage()}");
            return back()->with('error', 'Gagal mengembalikan barang.')->withInput();
        }
    }

    /**
     * Memproses transfer barang antar pemegang personal.
     */
    public function transferPersonal(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('transferPersonal', $barangQrCode);

        if ($barangQrCode->id_pemegang_personal === null || $barangQrCode->trashed()) {
            return back()->with('error', 'Barang ini tidak sedang dipegang oleh personal untuk ditransfer.');
        }

        $validated = $request->validate([
            'new_id_pemegang_personal' => ['required', 'exists:users,id', Rule::notIn([$barangQrCode->id_pemegang_personal])],
            'alasan_pemindahan' => 'required|string|max:1000',
        ], ['new_id_pemegang_personal.not_in' => 'Pemegang baru tidak boleh sama.', 'alasan_pemindahan.required' => 'Alasan transfer wajib diisi.']);

        DB::beginTransaction();
        try {
            $userPencatat = Auth::user();
            $pemegangBaru = User::find($validated['new_id_pemegang_personal']);

            $snapshot = ['id_pemegang' => $barangQrCode->id_pemegang_personal, 'nama_pemegang' => optional($barangQrCode->pemegangPersonal)->username, 'kondisi' => $barangQrCode->kondisi, 'status' => $barangQrCode->status];
            $pemegangLama = User::find($snapshot['id_pemegang']);

            $mutasi = MutasiBarang::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'jenis_mutasi' => 'Antar Personal',
                'id_pemegang_asal' => $snapshot['id_pemegang'],
                'id_ruangan_asal' => null, // Eksplisit set null
                'id_pemegang_tujuan' => $pemegangBaru->id,
                'id_ruangan_tujuan' => null, // Eksplisit set null
                'alasan_pemindahan' => $validated['alasan_pemindahan'],
                'id_user_admin' => $userPencatat->id,
                'tanggal_mutasi' => now(),
            ]);

            $barangQrCode->update(['id_pemegang_personal' => $pemegangBaru->id]);

            BarangStatus::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'id_user_pencatat' => $userPencatat->id,
                'tanggal_pencatatan' => now(),
                'id_pemegang_personal_sebelumnya' => $snapshot['id_pemegang'],
                'id_pemegang_personal_sesudahnya' => $pemegangBaru->id,
                'kondisi_sebelumnya' => $snapshot['kondisi'],
                'kondisi_sesudahnya' => $barangQrCode->kondisi,
                'status_ketersediaan_sebelumnya' => $snapshot['status'],
                'status_ketersediaan_sesudahnya' => $barangQrCode->status,
                'deskripsi_kejadian' => "Transfer dari {$snapshot['nama_pemegang']} ke {$pemegangBaru->username}",
                'id_mutasi_barang_trigger' => $mutasi->id,
            ]);

            LogAktivitas::create([
                'id_user' => $userPencatat->id,
                'aktivitas' => 'Transfer Pemegang Personal',
                'deskripsi' => "Mentransfer unit {$barangQrCode->kode_inventaris_sekolah} dari {$snapshot['nama_pemegang']} ke {$pemegangBaru->username}.",
                'model_terkait' => MutasiBarang::class,
                'id_model_terkait' => $mutasi->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $pemegangBaru->notify(new BarangTransferredPersonal($barangQrCode, $pemegangLama, $userPencatat));
            if ($pemegangLama) {
                $pemegangLama->notify(new BarangTransferredPersonal($barangQrCode, $pemegangLama, $userPencatat));
            }

            DB::commit();
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)->with('success', "Barang berhasil ditransfer ke {$pemegangBaru->username}.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error transferring personal holder (BarangQrCode ID: {$barangQrCode->id}): {$e->getMessage()}");
            return back()->with('error', 'Gagal mentransfer pemegang personal.')->withInput();
        }
    }

    public function archive(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('archive', $barangQrCode); // Policy di sini akan memblokir non-Admin.

        // Penjaga untuk mencegah aksi pada barang yang tidak valid
        if ($barangQrCode->trashed()) {
            return back()->with('warning', 'Barang ini sudah ada di dalam arsip.');
        }
        if ($barangQrCode->status === BarangQrCode::STATUS_DIPINJAM) {
            return back()->with('error', 'Barang tidak dapat diarsipkan karena sedang dipinjam.');
        }
        $existingArsip = ArsipBarang::where('id_barang_qr_code', $barangQrCode->id)
            ->where('status_arsip', '!=', ArsipBarang::STATUS_ARSIP_DIPULIHKAN)
            ->exists();
        if ($existingArsip) {
            return back()->with('warning', 'Unit ini sudah dalam proses pengajuan arsip.');
        }

        $validated = $request->validate([
            'jenis_penghapusan' => ['required', 'string', Rule::in(array_keys(ArsipBarang::getValidJenisPenghapusan()))], // [cite: 36]
            'alasan_penghapusan' => 'required|string|max:1000',
            'berita_acara_path' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'foto_bukti_path' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'konfirmasi_arsip_unit' => 'required|in:ARSIPKAN',
        ], ['konfirmasi_arsip_unit.in' => "Mohon ketik 'ARSIPKAN' untuk konfirmasi."]);

        DB::beginTransaction();
        try {
            $userActor = Auth::user(); // Admin yang melakukan aksi
            $barangQrCode->load('barang.kategori', 'ruangan', 'pemegangPersonal'); // Muat relasi sebelum snapshot

            $kondisiSebelum = $barangQrCode->kondisi;
            $statusKetersediaanSebelum = $barangQrCode->status;
            $ruanganSebelum = $barangQrCode->id_ruangan;
            $pemegangSebelum = $barangQrCode->id_pemegang_personal;

            $beritaAcaraPath = $request->hasFile('berita_acara_path') ? $request->file('berita_acara_path')->store('arsip/berita_acara_unit', 'public') : null;
            $fotoBuktiPath = $request->hasFile('foto_bukti_path') ? $request->file('foto_bukti_path')->store('arsip/foto_bukti_unit', 'public') : null;

            // --- LOGIKA SEDERHANA UNTUK ADMIN ---
            $arsip = ArsipBarang::updateOrCreate(
                ['id_barang_qr_code' => $barangQrCode->id],
                [
                    'id_user_pengaju' => $userActor->id, // Pengaju adalah Admin itu sendiri
                    'id_user_penyetuju' => $userActor->id, // Penyetuju juga Admin itu sendiri
                    'jenis_penghapusan' => $validated['jenis_penghapusan'],
                    'alasan_penghapusan' => $validated['alasan_penghapusan'],
                    'berita_acara_path' => $beritaAcaraPath,
                    'foto_bukti_path' => $fotoBuktiPath,
                    'tanggal_pengajuan_arsip' => now(),
                    'tanggal_penghapusan_resmi' => now(),
                    'status_arsip' => ArsipBarang::STATUS_ARSIP_DISETUJUI_PERMANEN, // Langsung disetujui
                    'data_unit_snapshot' => $barangQrCode->toArray(), // Ambil snapshot data
                ]
            );

            // Soft-delete BarangQrCode. Ini akan memicu event di model BarangQrCode.
            if (!$barangQrCode->trashed()) {
                $barangQrCode->delete(); // [cite: 1837]
            }

            // Catat perubahan status di BarangStatus
            BarangStatus::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'id_user_pencatat' => $userActor->id,
                'tanggal_pencatatan' => now(),
                'kondisi_sebelumnya' => $kondisiSebelum,
                'kondisi_sesudahnya' => $kondisiSebelum, // Kondisi tidak berubah saat diarsipkan
                'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum,
                'status_ketersediaan_sesudahnya' => 'Diarsipkan/Dihapus', // Sesuai enum
                'id_ruangan_sebelumnya' => $ruanganSebelum,
                'id_ruangan_sesudahnya' => null,
                'id_pemegang_personal_sebelumnya' => $pemegangSebelum,
                'id_pemegang_personal_sesudahnya' => null,
                'deskripsi_kejadian' => "Unit diarsipkan permanen oleh Admin. Arsip ID: {$arsip->id}",
                'id_arsip_barang_trigger' => $arsip->id,
            ]); // [cite: 1442]

            // Log Aktivitas
            LogAktivitas::create([
                'id_user' => $userActor->id,
                'aktivitas' => 'Arsip Langsung Unit',
                'deskripsi' => "Admin {$userActor->username} mengarsipkan unit: {$barangQrCode->kode_inventaris_sekolah} secara langsung.",
                'model_terkait' => ArsipBarang::class,
                'id_model_terkait' => $arsip->id,
                'data_baru' => $arsip->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect($this->getRedirectUrl("barang/{$barangQrCode->id_barang}"))->with('success', "Unit {$barangQrCode->kode_inventaris_sekolah} berhasil diarsipkan secara langsung.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error archiving BarangQrCode (ID: {$barangQrCode->id}): {$e->getMessage()}");
            return back()->with('error', 'Gagal memproses arsip.')->withInput();
        }
    }


    /**
     * Memulihkan unit BarangQrCode dari arsip.
     */
    public function restore(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('restore', $barangQrCode);
        $userPencatat = Auth::user();
        /** @var \App\Models\User $userPencatat */

        // PENYESUAIAN: Tambahkan penjaga untuk item yang TIDAK diarsipkan
        if (!$barangQrCode->trashed()) {
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)
                ->with('warning', 'Barang ini tidak berada di arsip.');
        }

        $arsip = ArsipBarang::where('id_barang_qr_code', $barangQrCode->id)
            ->where('status_arsip', '!=', ArsipBarang::STATUS_ARSIP_DIPULIHKAN)
            ->first();

        if (!$arsip) {
            return back()->with('error', 'Data arsip aktif untuk unit ini tidak ditemukan atau sudah dipulihkan.');
        }

        // Unit barang mungkin di-soft-delete jika arsipnya disetujui permanen
        // Kita perlu withTrashed untuk memastikan kita mendapatkan objeknya jika trashed
        $unitActual = BarangQrCode::withTrashed()->find($barangQrCode->id);
        if (!$unitActual) {
            return back()->with('error', 'Unit barang fisik tidak ditemukan.');
        }

        DB::beginTransaction();
        try {
            $restoredUnit = $arsip->restoreBarang($userPencatat->id);

            if ($restoredUnit) {
                LogAktivitas::create([
                    'id_user' => $userPencatat->id,
                    'aktivitas' => 'Pemulihan Unit dari Arsip',
                    'deskripsi' => "Memulihkan unit: {$restoredUnit->kode_inventaris_sekolah}",
                    'model_terkait' => ArsipBarang::class,
                    'id_model_terkait' => $arsip->id,
                    'data_baru' => $arsip->toJson(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                DB::commit();
                return redirect()->route('admin.barang-qr-code.show', $restoredUnit->id)->with('success', 'Unit barang berhasil dipulihkan.');
            } else {
                DB::rollBack();
                return back()->with('error', 'Gagal memulihkan unit barang. Proses internal model gagal.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error restoring BarangQrCode (ID: {$barangQrCode->id}): {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Gagal memulihkan dari arsip: ' . (config('app.debug') ? $e->getMessage() : 'Kesalahan sistem.'))->withInput();
        }
    }

    /**
     * Mengunduh file gambar QR Code.
     */
    public function download(BarangQrCode $barangQrCode): BinaryFileResponse
    {
        $this->authorize('downloadQr', $barangQrCode);
        $path = $barangQrCode->qr_path;

        if (!$path || !Storage::disk('public')->exists($path)) {
            $qrContent = $barangQrCode->getQrCodeContent();
            $directory = 'qr_codes';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $filename = $directory . '/' . Str::slug($barangQrCode->kode_inventaris_sekolah ?: ('unit-' . $barangQrCode->id . '-' . Str::random(5))) . '.svg';

            $qrImage = GeneratorQrCode::format('svg')->size(200)->errorCorrection('H')->generate($qrContent);
            Storage::disk('public')->put($filename, $qrImage);
            $barangQrCode->updateQuietly(['qr_path' => $filename]);
            $path = $filename;
        }
        return response()->download(storage_path('app/public/' . $path));
    }

    /**
     * Menampilkan halaman untuk mencetak beberapa QR Code yang dipilih.
     */
    public function printMultiple(Request $request): View|RedirectResponse
    {
        $this->authorize('printQr', BarangQrCode::class);
        $validated = $request->validate([
            'qr_code_ids' => 'required|array|min:1',
            'qr_code_ids.*' => 'integer|exists:barang_qr_codes,id'
        ]);

        $qrCodes = BarangQrCode::with(['barang', 'ruangan', 'pemegangPersonal'])->whereIn('id', $validated['qr_code_ids'])->get();
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole([User::ROLE_OPERATOR])) {
            $ruanganIdsDikelola = $user->ruanganYangDiKelola()->pluck('id')->toArray();
            $qrCodes = $qrCodes->filter(function ($qrCode) use ($ruanganIdsDikelola) {
                return (!is_null($qrCode->id_ruangan) && in_array($qrCode->id_ruangan, $ruanganIdsDikelola)) ||
                    !is_null($qrCode->id_pemegang_personal) ||
                    is_null($qrCode->id_ruangan);
            });
        }

        if ($qrCodes->isEmpty()) {
            return back()->with('error', 'Tidak ada unit barang yang valid untuk dicetak.');
        }
        return view('pages.barang_qr_code.print_multiple', compact('qrCodes'));
    }

    /**
     * Mengekspor daftar unit barang ke format PDF berdasarkan filter.
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('export', BarangQrCode::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $qrCodesQuery = BarangQrCode::with(['barang.kategori', 'ruangan', 'pemegangPersonal'])->filter($request);

        if ($user->hasRole([User::ROLE_OPERATOR])) {
            $ruanganIds = $user->ruanganYangDiKelola()->pluck('id');
            $qrCodesQuery->where(function ($query) use ($ruanganIds) {
                $query->whereIn('id_ruangan', $ruanganIds)
                    ->orWhereNull('id_ruangan')
                    ->orWhereNotNull('id_pemegang_personal');
            });
        }
        $qrCodes = $qrCodesQuery->get();

        if ($qrCodes->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk diekspor.');
        }

        $filterInfo = [
            'search' => $request->search ?? 'Semua',
            'nama_barang_induk' => $request->id_barang ? (Barang::find($request->id_barang)->nama_barang ?? 'N/A') : 'Semua',
            'nama_ruangan' => $request->id_ruangan ? (Ruangan::find($request->id_ruangan)->nama_ruangan ?? 'N/A') : 'Semua',
            'status_unit' => $request->status ?? 'Semua',
            'kondisi_unit' => $request->kondisi ?? 'Semua',
            'tanggal_export' => now()->isoFormat('dddd, D MMMM YYYY HH:mm:ss') . ' WIB',
            'user_export' => $user->username,
        ];

        $pdf = PDF::loadView('admin.exports.barang_qrcode_pdf', compact('qrCodes', 'filterInfo'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('daftar_unit_barang-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Method baru untuk mencari unit barang yang bisa dilaporkan untuk pemeliharaan.
     * Mengembalikan data dalam format JSON untuk Select2.
     */
    /**
     * Method untuk mencari unit barang yang bisa dilaporkan untuk pemeliharaan.
     * Mengembalikan data dalam format JSON untuk Select2.
     */
    public function searchForMaintenance(Request $request): JsonResponse
    {
        try {
            // Otorisasi sederhana, pastikan pengguna yang mengakses adalah pengguna terautentikasi
            // Anda bisa menambahkan policy yang lebih spesifik jika perlu
            $this->authorize('viewAny', Pemeliharaan::class);

            $term = $request->input('q', '');
            $page = $request->input('page', 1);
            $perPage = 15;

            $query = BarangQrCode::with(['barang:id,nama_barang', 'ruangan:id,nama_ruangan', 'pemegangPersonal:id,username'])
                // Kriteria barang yang bisa dilaporkan:
                ->whereNull('deleted_at') // 1. Tidak diarsipkan
                ->where('status', '!=', BarangQrCode::STATUS_DIPINJAM) // 2. Tidak sedang dipinjam
                ->where('kondisi', '!=', BarangQrCode::KONDISI_HILANG); // 3. Tidak hilang

            // Logika pencarian berdasarkan term
            if (!empty($term)) {
                $query->where(function ($q) use ($term) {
                    $q->where('kode_inventaris_sekolah', 'LIKE', "%{$term}%")
                        ->orWhere('no_seri_pabrik', 'LIKE', "%{$term}%")
                        ->orWhereHas('barang', function ($qBarang) use ($term) {
                            $qBarang->where('nama_barang', 'LIKE', "%{$term}%");
                        });
                });
            }

            // Pagination
            $offset = ($page - 1) * $perPage;
            $totalCount = $query->count();
            $items = $query->skip($offset)->take($perPage)->get();

            $results = $items->map(function ($item) {
                $lokasi = '';
                if ($item->ruangan) {
                    $lokasi = $item->ruangan->nama_ruangan;
                } elseif ($item->pemegangPersonal) {
                    $lokasi = 'Pemegang: ' . $item->pemegangPersonal->username;
                } else {
                    $lokasi = 'Tidak Berlokasi';
                }

                $displayText = $item->barang->nama_barang .
                    ' (' . $item->kode_inventaris_sekolah . ')';

                if ($item->no_seri_pabrik) {
                    $displayText .= ' - SN: ' . $item->no_seri_pabrik;
                }

                $displayText .= ' - Kondisi: ' . $item->kondisi .
                    ' - Lokasi: ' . $lokasi;

                return [
                    'id' => $item->id,
                    'text' => $displayText,
                    'nama_barang_induk' => $item->barang->nama_barang,
                    'kode_inventaris_sekolah' => $item->kode_inventaris_sekolah,
                    'no_seri_pabrik' => $item->no_seri_pabrik,
                    'kondisi_saat_ini' => $item->kondisi,
                    'ruangan_saat_ini' => $item->ruangan ? $item->ruangan->nama_ruangan : null,
                    'pemegang_saat_ini' => $item->pemegangPersonal ? $item->pemegangPersonal->username : null
                ];
            });

            return response()->json([
                'items' => $results,
                'total_count' => $totalCount,
                'results' => $results // Untuk kompatibilitas dengan frontend yang sudah ada
            ]);
        } catch (\Exception $e) {
            Log::error('Error in searchForMaintenance: ' . $e->getMessage());

            return response()->json([
                'items' => [],
                'total_count' => 0,
                'results' => [],
                'error' => 'Terjadi kesalahan saat mencari data'
            ], 500);
        }
    }

    // =====================================================================
    //      METHOD BARU UNTUK MENANGANI PEMINDAIAN QR CODE
    // =====================================================================

    /**
     * Menangani permintaan dari hasil pemindaian QR Code.
     * Metode ini akan memeriksa status login pengguna dan mengarahkannya
     * ke halaman yang sesuai.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\BarangQrCode $barangQrCode Instance model yang ditemukan oleh Route Model Binding.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleScan(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        // Cek apakah pengguna sudah login
        if (Auth::check()) {
            $user = Auth::user();
            /** @var \App\Models\User $user */

            // Dapatkan prefix rute berdasarkan peran pengguna (misal: 'admin.', 'guru.')
            $rolePrefix = $user->getRolePrefix();

            // Buat nama rute yang dinamis
            $routeName = $rolePrefix . 'barang-qr-code.show';

            // Cek apakah rute tersebut ada untuk peran pengguna
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                // Arahkan ke halaman detail sesuai dengan peran pengguna
                return redirect()->route($routeName, ['barangQrCode' => $barangQrCode->kode_inventaris_sekolah]);
            } else {
                // Fallback jika peran tidak memiliki rute detail (misal: peran baru)
                // Arahkan ke dashboard mereka dengan pesan info.
                Log::warning("Rute '{$routeName}' tidak ditemukan untuk peran '{$user->role}'.");
                return redirect()->route('redirect-dashboard')->with('info', 'Anda tidak memiliki akses ke halaman detail barang.');
            }
        }

        // Jika pengguna BELUM login:
        // Arahkan ke halaman login. Setelah login berhasil, Laravel akan otomatis
        // mengarahkan pengguna ke URL yang mereka tuju sebelumnya (URL scan ini).
        return redirect()->intended(route('public.scan.detail', ['barangQrCode' => $barangQrCode->kode_inventaris_sekolah]))
            ->with('info', 'Silakan login untuk melihat detail aset.');
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

    private function getViewPathBasedOnRole(string $adminView, string $operatorView, ?string $guruView = null): string
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if (!$user) return $adminView;

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return $adminView;
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            return view()->exists($operatorView) ? $operatorView : $adminView;
        } elseif ($user->hasRole(User::ROLE_GURU) && $guruView) {
            return view()->exists($guruView) ? $guruView : $adminView;
        }
        return $adminView;
    }
}
