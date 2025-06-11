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

        $filterOptions = [
            'aktif' => 'Semua Unit Aktif',
            'tersedia' => 'Tersedia',
            'dipesan' => 'Dipesan (Proses Peminjaman)', // <-- OPSI FILTER BARU
            'dipinjam' => 'Dipinjam',
            'pemeliharaan' => 'Dalam Pemeliharaan',
            'rusak_berat_aktif' => 'Rusak Berat (Aktif)',
            'hilang' => 'Hilang (Diarsipkan)',
            'diarsipkan_lain' => 'Diarsipkan (Alasan Lain)',
            'semua' => 'Tampilkan Semua (Aktif & Diarsipkan)',
        ];

        $filterUtama = $request->input('filter_utama', 'aktif');

        // --- PENYESUAIAN: Tambahkan Eager Loading untuk peminjaman aktif ---
        $qrCodesQuery = BarangQrCode::query()->with([
            'barang.kategori',
            'ruangan',
            'pemegangPersonal',
            'arsip',
            // Eager load relasi detail peminjaman HANYA jika peminjamannya aktif
            'peminjamanDetails' => function ($q) {
                $q->whereHas('peminjaman', function ($p) {
                    $p->whereNotIn('status', [
                        Peminjaman::STATUS_SELESAI,
                        Peminjaman::STATUS_DITOLAK,
                        Peminjaman::STATUS_DIBATALKAN,
                    ]);
                });
            }
        ]);

        // Logika filter utama berdasarkan status
        switch ($filterUtama) {
            case 'tersedia':
                $qrCodesQuery->where('status', BarangQrCode::STATUS_TERSEDIA)->whereNull('deleted_at');
                break;
            case 'dipesan':
                $qrCodesQuery->whereNull('deleted_at')
                    ->where('status', BarangQrCode::STATUS_TERSEDIA) // Barang yang dipesan statusnya masih tersedia
                    ->whereHas('peminjamanDetails', function ($q) {
                        $q->whereHas('peminjaman', function ($p) {
                            $p->whereIn('status', [
                                Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                Peminjaman::STATUS_DISETUJUI
                            ]);
                        });
                    });
                break;
            case 'dipinjam':
                $qrCodesQuery->where('status', BarangQrCode::STATUS_DIPINJAM)->whereNull('deleted_at'); // [cite: 1685]
                break;
            case 'pemeliharaan':
                $qrCodesQuery->where('status', BarangQrCode::STATUS_DALAM_PEMELIHARAAN)->whereNull('deleted_at'); // [cite: 1686]
                break;
            case 'rusak_berat_aktif':
                $qrCodesQuery->where('kondisi', BarangQrCode::KONDISI_RUSAK_BERAT)->whereNull('deleted_at'); // [cite: 1687]
                break;
            case 'hilang':
                $qrCodesQuery->onlyTrashed()->where('kondisi', BarangQrCode::KONDISI_HILANG); // [cite: 1688]
                break;
            case 'diarsipkan_lain':
                $qrCodesQuery->onlyTrashed()->whereHas('arsip', function ($q) {
                    $q->where('jenis_penghapusan', '!=', 'Hilang');
                }); // [cite: 1689]
                break;
            case 'semua':
                $qrCodesQuery->withTrashed(); // [cite: 1690]
                break;
            default: // 'aktif'
                $qrCodesQuery->whereNull('deleted_at'); // [cite: 1691]
                break;
        }

        // Terapkan filter sekunder (pencarian, ruangan, dll)
        if (method_exists($qrCodesQuery, 'filter')) {
            $qrCodesQuery->filter($request); // [cite: 1692]
        }


        // PENYESUAIAN FINAL: Logika pembatasan data untuk Operator
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id'); // [cite: 1693]

            $qrCodesQuery->where(function ($query) use ($ruanganOperatorIds, $user) {
                // Kondisi 1: Barang ada di ruangan yang SAYA kelola
                if ($ruanganOperatorIds->isNotEmpty()) {
                    $query->whereIn('id_ruangan', $ruanganOperatorIds); // [cite: 1694]
                }
                // Kondisi 2: ATAU barang dipegang oleh SAYA SENDIRI
                $query->orWhere('id_pemegang_personal', $user->id); // [cite: 1695]
                // Kondisi 3: ATAU barang "mengambang" (tidak punya lokasi)
                $query->orWhere(function ($qFloating) {
                    $qFloating->whereNull('id_ruangan')->whereNull('id_pemegang_personal');
                }); // [cite: 1696]
            });
        }

        $qrCodes = $qrCodesQuery->latest('id')->paginate(15)->withQueryString(); // [cite: 1697]

        // Data untuk dropdown filter
        $barangList = Barang::orderBy('nama_barang')->get(); // [cite: 1698]
        $ruanganList = $user->hasRole(User::ROLE_ADMIN)
            ? Ruangan::orderBy('nama_ruangan')->get()
            : $user->ruanganYangDiKelola()->orderBy('nama_ruangan')->get(); // [cite: 1699, 1700]

        $rolePrefix = $this->getRolePrefix(); // [cite: 1701]

        return view('pages.barang_qr_code.index', compact(
            'qrCodes',
            'ruanganList',
            'barangList',
            'request',
            'rolePrefix',
            'filterOptions',
            'filterUtama'
        )); // [cite: 1701, 1702]
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
        $jumlahUnit = filter_var($request->query('jumlah_unit', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);

        return view('admin.barang_qr_code.create_units', compact(
            'barang',
            'jumlahUnit',
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
            'rolePrefix'
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
     * Memproses penyerahan unit barang ke pemegang personal.
     */
    public function assignPersonal(Request $request, BarangQrCode $barangQrCode): RedirectResponse|JsonResponse
    {
        $this->authorize('assignPersonal', $barangQrCode);

        // PENYESUAIAN: Tambahkan penjaga untuk item yang diarsipkan
        if ($barangQrCode->trashed()) {
            return back()->with('error', 'Barang yang sudah diarsipkan tidak dapat diserahkan.');
        }

        // PENAMBAHAN: Penjaga Status
        if ($barangQrCode->status === \App\Models\BarangQrCode::STATUS_DIPINJAM) {
            return back()->with('error', 'Gagal menyerahkan: barang sedang dipinjam.');
        }
        if ($barangQrCode->status !== \App\Models\BarangQrCode::STATUS_TERSEDIA) {
            return back()->with('error', 'Hanya barang dengan status "Tersedia" yang dapat diserahkan.');
        }

        $validated = $request->validate([
            'id_pemegang_personal' => [
                'required',
                'exists:users,id',
                Rule::notIn([$barangQrCode->id_pemegang_personal])
            ],
            'catatan_penyerahan_personal' => 'nullable|string|max:1000',
        ], ['id_pemegang_personal.not_in' => 'Pemegang personal yang dipilih sama dengan pemegang saat ini.']);

        $userPenerima = User::find($validated['id_pemegang_personal']);
        $catatanPenyerahan = $validated['catatan_penyerahan_personal'] ?? null;

        // Tanggal penyerahan akan dicatat sebagai now() di dalam model/BarangStatus
        if ($barangQrCode->assignToPersonal($validated['id_pemegang_personal'], Auth::id())) {
            $deskripsiLog = "Menyerahkan unit: {$barangQrCode->kode_inventaris_sekolah} ke {$userPenerima->username}.";
            if ($catatanPenyerahan) {
                $deskripsiLog .= " Catatan: " . $catatanPenyerahan;
            }

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Serah Terima ke Personal',
                'deskripsi' => $deskripsiLog,
                'model_terkait' => BarangQrCode::class,
                'id_model_terkait' => $barangQrCode->id,
                'data_baru' => $barangQrCode->fresh()->toJson(), // Ambil data terbaru
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang berhasil diserahkan ke: ' . $userPenerima->username,
                    'redirect_url' => route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id) // Opsional, untuk JS redirect
                ]);
            }
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)->with('success', 'Barang berhasil diserahkan ke: ' . $userPenerima->username);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyerahkan barang.'
            ], 500); // Kode status error server
        }
        return back()->with('error', 'Gagal menyerahkan barang.')->withInput();
    }

    /**
     * Memproses pengembalian barang dari pemegang personal ke ruangan.
     */
    public function returnFromPersonal(Request $request, BarangQrCode $barangQrCode): RedirectResponse|JsonResponse
    {
        $this->authorize('returnPersonal', $barangQrCode);

        // PENYESUAIAN: Tambahkan penjaga untuk item yang diarsipkan
        if ($barangQrCode->trashed()) {
            return back()->with('error', 'Barang yang sudah diarsipkan tidak dapat diserahkan.');
        }

        // PENAMBAHAN: Penjaga Status
        if ($barangQrCode->status === \App\Models\BarangQrCode::STATUS_DIPINJAM) {
            return back()->with('error', 'Gagal mengembalikan: barang sedang dipinjam oleh pihak lain.');
        }

        $validated = $request->validate([
            'id_ruangan_tujuan' => 'required|exists:ruangans,id',
            'catatan_pengembalian_ruangan' => 'nullable|string|max:1000',
        ]);

        $ruanganTujuan = Ruangan::find($validated['id_ruangan_tujuan']);
        $pemegangLama = $barangQrCode->pemegangPersonal; // Bisa null jika tidak ada relasi atau tidak dimuat
        $namaPemegangLama = $pemegangLama ? $pemegangLama->username : 'N/A';


        $userPencatat = Auth::user();
        /** @var \App\Models\User $userPencatat */

        if ($userPencatat->hasRole(User::ROLE_OPERATOR)) {
            if (!$userPencatat->ruanganYangDiKelola()->where('id', $ruanganTujuan->id)->exists()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Anda tidak diizinkan mengembalikan barang ke ruangan yang dipilih.'], 403);
                }
                return back()->with('error', 'Anda tidak diizinkan mengembalikan barang ke ruangan yang dipilih.')->withInput();
            }
        }

        $catatanPengembalian = $validated['catatan_pengembalian_ruangan'] ?? null;
        // Tanggal pengembalian akan dicatat sebagai now() di dalam model/BarangStatus

        if ($barangQrCode->returnFromPersonalToRoom($validated['id_ruangan_tujuan'], Auth::id())) {
            $deskripsiLog = "Mengembalikan unit: {$barangQrCode->kode_inventaris_sekolah} dari {$namaPemegangLama} ke ruangan {$ruanganTujuan->nama_ruangan}.";
            if ($catatanPengembalian) {
                $deskripsiLog .= " Catatan: " . $catatanPengembalian;
            }

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Pengembalian dari Personal ke Ruangan',
                'deskripsi' => $deskripsiLog,
                'model_terkait' => BarangQrCode::class,
                'id_model_terkait' => $barangQrCode->id,
                'data_baru' => $barangQrCode->fresh()->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang berhasil dikembalikan ke ruangan: ' . $ruanganTujuan->nama_ruangan,
                    'redirect_url' => route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)
                ]);
            }
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)->with('success', 'Barang berhasil dikembalikan ke ruangan: ' . $ruanganTujuan->nama_ruangan);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Gagal mengembalikan barang.'], 500);
        }
        return back()->with('error', 'Gagal mengembalikan barang.')->withInput();
    }

    /**
     * Memproses transfer barang antar pemegang personal.
     */
    public function transferPersonal(Request $request, BarangQrCode $barangQrCode): RedirectResponse|JsonResponse
    {
        $this->authorize('transferPersonal', $barangQrCode);

        // PENYESUAIAN: Tambahkan penjaga untuk item yang diarsipkan
        if ($barangQrCode->trashed()) {
            return back()->with('error', 'Barang yang sudah diarsipkan tidak dapat diserahkan.');
        }

        // PENAMBAHAN: Penjaga Status
        if ($barangQrCode->status === \App\Models\BarangQrCode::STATUS_DIPINJAM) {
            return back()->with('error', 'Gagal mentransfer: barang sedang dipinjam oleh pihak lain.');
        }

        $validated = $request->validate([
            'new_id_pemegang_personal' => [
                'required',
                'exists:users,id',
                Rule::notIn([$barangQrCode->id_pemegang_personal])
            ],
            'catatan_transfer_personal' => 'nullable|string|max:1000',
        ], ['new_id_pemegang_personal.not_in' => 'Pemegang personal baru tidak boleh sama dengan pemegang saat ini.']);

        $pemegangBaru = User::find($validated['new_id_pemegang_personal']);
        $pemegangLama = $barangQrCode->pemegangPersonal; // Bisa null jika tidak ada relasi atau tidak dimuat
        $namaPemegangLama = $pemegangLama ? $pemegangLama->username : 'N/A';

        $catatanTransfer = $validated['catatan_transfer_personal'] ?? null;
        // Tanggal transfer akan dicatat sebagai now() di dalam model/BarangStatus

        if ($barangQrCode->transferPersonalHolder($validated['new_id_pemegang_personal'], Auth::id())) {
            $deskripsiLog = "Transfer unit: {$barangQrCode->kode_inventaris_sekolah} dari {$namaPemegangLama} ke {$pemegangBaru->username}.";
            if ($catatanTransfer) {
                $deskripsiLog .= " Catatan: " . $catatanTransfer;
            }

            LogAktivitas::create([
                'id_user' => Auth::id(),
                'aktivitas' => 'Transfer Pemegang Personal',
                'deskripsi' => $deskripsiLog,
                'model_terkait' => BarangQrCode::class,
                'id_model_terkait' => $barangQrCode->id,
                'data_lama' => json_encode(['id_pemegang_personal' => $pemegangLama->id ?? null, 'username_pemegang_lama' => $namaPemegangLama]),
                'data_baru' => $barangQrCode->fresh()->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang berhasil ditransfer ke: ' . $pemegangBaru->username,
                    'redirect_url' => route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)
                ]);
            }
            return redirect()->route($this->getRolePrefix() . 'barang-qr-code.show', $barangQrCode->id)->with('success', 'Barang berhasil ditransfer ke: ' . $pemegangBaru->username);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Gagal mentransfer pemegang personal.'], 500);
        }
        return back()->with('error', 'Gagal mentransfer pemegang personal.')->withInput();
    }


    public function archive(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('archive', $barangQrCode);
        $userActor = Auth::user();
        /** @var \App\Models\User $userActor **/

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
            'jenis_penghapusan' => ['required', 'string', Rule::in(array_keys(ArsipBarang::getValidJenisPenghapusan()))],
            'alasan_penghapusan' => 'required|string|max:1000',
            'berita_acara_path' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'foto_bukti_path' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'konfirmasi_arsip_unit' => 'required|in:ARSIPKAN',
        ], ['konfirmasi_arsip_unit.in' => "Mohon ketik 'ARSIPKAN' untuk konfirmasi."]);

        DB::beginTransaction();
        try {
            $kondisiSebelum = $barangQrCode->kondisi;
            $statusKetersediaanSebelum = $barangQrCode->status;
            $ruanganSebelum = $barangQrCode->id_ruangan;
            $pemegangSebelum = $barangQrCode->id_pemegang_personal;

            $beritaAcaraPath = $request->hasFile('berita_acara_path') ? $request->file('berita_acara_path')->store('arsip/berita_acara_unit', 'public') : null;
            $fotoBuktiPath = $request->hasFile('foto_bukti_path') ? $request->file('foto_bukti_path')->store('arsip/foto_bukti_unit', 'public') : null;

            $arsipData = [
                'id_user_pengaju' => $userActor->id,
                'jenis_penghapusan' => $validated['jenis_penghapusan'],
                'alasan_penghapusan' => $validated['alasan_penghapusan'],
                'data_unit_snapshot' => $barangQrCode->toArray(),
                'tanggal_pengajuan_arsip' => now(),
                'berita_acara_path' => $beritaAcaraPath,
                'foto_bukti_path' => $fotoBuktiPath,
            ];

            $statusKetersediaanSesudah = $statusKetersediaanSebelum;

            // PENYESUAIAN: Inisialisasi variabel log di sini
            $logAktivitasJenis = '';
            $logAktivitasDeskripsi = '';
            $redirectMessage = '';

            if ($userActor->hasRole(User::ROLE_ADMIN)) {
                $arsipData['status_arsip'] = ArsipBarang::STATUS_ARSIP_DISETUJUI_PERMANEN;
                $arsipData['id_user_penyetuju'] = $userActor->id;
                $arsipData['tanggal_penghapusan_resmi'] = now();
                if (!$barangQrCode->trashed()) {
                    $barangQrCode->delete();
                }
                $statusKetersediaanSesudah = 'Diarsipkan/Dihapus';
                $logAktivitasJenis = 'Arsip Langsung Unit';
                $logAktivitasDeskripsi = "Admin {$userActor->username} langsung mengarsipkan unit: {$barangQrCode->kode_inventaris_sekolah}";
                $redirectMessage = "Unit {$barangQrCode->kode_inventaris_sekolah} berhasil diarsipkan secara langsung.";
            } else {
                $arsipData['status_arsip'] = ArsipBarang::STATUS_ARSIP_DIAJUKAN;
                $logAktivitasJenis = 'Pengajuan Arsip Unit';
                $logAktivitasDeskripsi = "Pengajuan arsip untuk unit {$barangQrCode->kode_inventaris_sekolah} oleh {$userActor->username}";
                $redirectMessage = "Pengajuan arsip unit {$barangQrCode->kode_inventaris_sekolah} berhasil.";
            }

            $arsip = ArsipBarang::updateOrCreate(
                ['id_barang_qr_code' => $barangQrCode->id],
                $arsipData
            );

            BarangStatus::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'id_user_pencatat' => $userActor->id,
                'tanggal_pencatatan' => now(),
                'kondisi_sebelumnya' => $kondisiSebelum,
                'kondisi_sesudahnya' => $kondisiSebelum,
                'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum,
                'status_ketersediaan_sesudahnya' => $statusKetersediaanSesudah,
                'id_ruangan_sebelumnya' => $ruanganSebelum,
                'id_ruangan_sesudahnya' => ($userActor->hasRole(User::ROLE_ADMIN)) ? null : $ruanganSebelum,
                'id_pemegang_personal_sebelumnya' => $pemegangSebelum,
                'id_pemegang_personal_sesudahnya' => ($userActor->hasRole(User::ROLE_ADMIN)) ? null : $pemegangSebelum,
                'deskripsi_kejadian' => $userActor->hasRole(User::ROLE_ADMIN) ? "Unit diarsipkan permanen. Arsip ID: {$arsip->id}" : "Pengajuan arsip unit. Arsip ID: {$arsip->id}",
                'id_arsip_barang_trigger' => $arsip->id,
            ]);

            LogAktivitas::create([
                'id_user' => $userActor->id,
                'aktivitas' => $logAktivitasJenis,
                'deskripsi' => $logAktivitasDeskripsi,
                'model_terkait' => ArsipBarang::class,
                'id_model_terkait' => $arsip->id,
                'data_baru' => $arsip->toJson(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            // Redirect ke halaman detail barang induknya
            return redirect($this->getRedirectUrl("barang/{$barangQrCode->id_barang}"))->with('success', $redirectMessage);
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
     * Memproses mutasi (perpindahan) unit BarangQrCode ke ruangan lain.
     * Metode ini HANYA untuk perpindahan Ruangan -> Ruangan.
     * Untuk Personal -> Ruangan, gunakan returnFromPersonal().
     */
    /**
     * Memproses penempatan atau mutasi unit BarangQrCode ke ruangan lain.
     */
    public function mutasi(Request $request, BarangQrCode $barangQrCode): RedirectResponse
    {
        $this->authorize('mutasi', $barangQrCode);
        $userPencatat = Auth::user();

        // Penjaga umum
        if ($barangQrCode->trashed()) {
            return back()->with('error', 'Barang yang sudah diarsipkan tidak dapat dipindahkan.');
        }
        if ($barangQrCode->status === BarangQrCode::STATUS_DIPINJAM) {
            return back()->with('error', 'Unit sedang dipinjam dan tidak dapat dipindahkan.');
        }
        if ($barangQrCode->id_pemegang_personal !== null) {
            return back()->with('error', 'Unit sedang dipegang personal. Gunakan fitur "Kembalikan ke Ruangan" terlebih dahulu.');
        }

        $validated = $request->validate([
            'id_ruangan_tujuan' => ['required', 'exists:ruangans,id', Rule::notIn([$barangQrCode->id_ruangan])],
            'alasan_pemindahan' => 'required|string|max:1000',
            'surat_pemindahan_path' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ], ['id_ruangan_tujuan.not_in' => 'Ruangan tujuan tidak boleh sama dengan ruangan saat ini.']);

        DB::beginTransaction();
        try {
            $ruanganTujuan = Ruangan::find($validated['id_ruangan_tujuan']);
            $ruanganAsalIdSebelum = $barangQrCode->id_ruangan;
            $aktivitasLog = "";
            $deskripsiLog = "";

            // Logika untuk Penempatan (jika barang mengambang)
            if ($ruanganAsalIdSebelum === null) {
                $barangQrCode->id_ruangan = $ruanganTujuan->id;
                $barangQrCode->save();
                $aktivitasLog = 'Penempatan Unit Barang';
                $deskripsiLog = "Menempatkan unit {$barangQrCode->kode_inventaris_sekolah} ke ruangan {$ruanganTujuan->nama_ruangan}.";
            }
            // Logika untuk Mutasi (jika barang sudah punya ruangan)
            else {
                $suratPath = $request->hasFile('surat_pemindahan_path') ? $request->file('surat_pemindahan_path')->store('mutasi/dokumen_unit', 'public') : null;
                $mutasi = MutasiBarang::create([
                    'id_barang_qr_code' => $barangQrCode->id,
                    'id_ruangan_asal' => $ruanganAsalIdSebelum,
                    'id_ruangan_tujuan' => $ruanganTujuan->id,
                    'alasan_pemindahan' => $validated['alasan_pemindahan'],
                    'surat_pemindahan_path' => $suratPath,
                    'id_user_admin' => $userPencatat->id,
                ]);
                // Event 'created' di model MutasiBarang akan mengupdate lokasi BarangQrCode
                $aktivitasLog = 'Mutasi Unit Barang';
                $deskripsiLog = "Memutasi unit {$barangQrCode->kode_inventaris_sekolah} dari '{$mutasi->ruanganAsal->nama_ruangan}' ke '{$ruanganTujuan->nama_ruangan}'.";
            }

            LogAktivitas::create([
                'id_user' => $userPencatat->id,
                'aktivitas' => $aktivitasLog,
                'deskripsi' => $deskripsiLog,
                'model_terkait' => BarangQrCode::class,
                'id_model_terkait' => $barangQrCode->id,
                'data_lama' => json_encode(['id_ruangan' => $ruanganAsalIdSebelum]),
                'data_baru' => json_encode(['id_ruangan' => $ruanganTujuan->id]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            return redirect($this->getRedirectUrl("barang-qr-code/{$barangQrCode->id}"))
                ->with('success', "Unit barang berhasil ditempatkan/dipindahkan ke: {$ruanganTujuan->nama_ruangan}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error mutating BarangQrCode (ID: {$barangQrCode->id}): {$e->getMessage()}");
            return back()->with('error', 'Gagal memproses perpindahan unit.')->withInput();
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
        return view('admin.barang_qr_code.print_multiple', compact('qrCodes'));
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
    public function searchForMaintenance(Request $request): JsonResponse
    {
        // Otorisasi sederhana, pastikan pengguna yang mengakses adalah pengguna terautentikasi
        // Anda bisa menambahkan policy yang lebih spesifik jika perlu
        $this->authorize('viewAny', Pemeliharaan::class);

        $term = $request->input('q', '');

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

        $items = $query->limit(20)->get();

        $results = $items->map(function ($item) {
            $lokasi = optional($item->ruangan)->nama_ruangan ?? (optional($item->pemegangPersonal)->username ? 'Pemegang: ' . $item->pemegangPersonal->username : 'Tidak Berlokasi');
            $displayText = "{$item->barang->nama_barang} ({$item->kode_inventaris_sekolah}) - Kondisi: {$item->kondisi} - Lokasi: {$lokasi}";

            return [
                'id' => $item->id,
                'text' => $displayText
            ];
        });

        return response()->json(['results' => $results]);
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
