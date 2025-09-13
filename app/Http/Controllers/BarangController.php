<?php

namespace App\Http\Controllers;

// use App\Http\Controllers\Controller; // Sudah ada di class
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use App\Models\User;
use App\Models\LogAktivitas;
use App\Models\ArsipBarang;
// use App\Models\MutasiBarang; // Uncomment jika digunakan
use App\Models\BarangStatus;
use App\Models\KategoriBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Route; // Diperlukan untuk getRedirectRouteName
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode as GeneratorQrCode;
// use Illuminate\Pagination\LengthAwarePaginator; // Uncomment jika digunakan

class BarangController extends Controller
{
    use AuthorizesRequests;


    public function index(Request $request): View
    {
        $this->authorize('viewAny', Barang::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $ruanganId = $request->get('id_ruangan');
        $kategoriId = $request->get('id_kategori');
        $searchTerm = $request->get('search');

        $ruanganList = $user->hasRole(User::ROLE_OPERATOR)
            ? $user->ruanganYangDiKelola()->orderBy('nama_ruangan')->get()
            : Ruangan::orderBy('nama_ruangan')->get();
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();

        $operatorTidakAdaRuangan = false;
        $query = Barang::with('kategori')
            ->withCount(['qrCodes as active_qr_codes_count' => function ($q) use ($user) {
                // Jika operator, hitung hanya unit yang relevan dengannya
                if ($user->hasRole(User::ROLE_OPERATOR)) {
                    $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
                    $q->where(function ($subQuery) use ($ruanganOperatorIds, $user) {
                        $subQuery->whereIn('id_ruangan', $ruanganOperatorIds)
                            ->orWhere('id_pemegang_personal', $user->id);
                    });
                }
                $q->whereNull('deleted_at');
            }]);

        if ($kategoriId) {
            $query->where('id_kategori', $kategoriId);
        }
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama_barang', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('kode_barang', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('merk_model', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Logika filter berdasarkan peran
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $operatorRuanganIds = $user->ruanganYangDiKelola()->pluck('id');

            // --- BLOK KODE YANG DIPERBAIKI ---
            $query->where(function ($qSub) use ($operatorRuanganIds, $user) {
                // Kondisi 1: Barang memiliki unit di salah satu ruangan yang dikelola operator
                $qSub->whereHas('qrCodes', function ($qUnit) use ($operatorRuanganIds) {
                    $qUnit->whereIn('id_ruangan', $operatorRuanganIds)->whereNull('deleted_at');
                })
                    // Kondisi 2: ATAU barang memiliki unit yang dipegang secara personal oleh operator
                    ->orWhereHas('qrCodes', function ($qUnit) use ($user) {
                        $qUnit->where('id_pemegang_personal', $user->id)->whereNull('deleted_at');
                    });
            });
            // --- AKHIR BLOK KODE YANG DIPERBAIKI ---

            if ($operatorRuanganIds->isEmpty() && !$user->barangQrCodesYangDipegang()->exists()) {
                $operatorTidakAdaRuangan = true;
            }

            // Filter tambahan jika operator memilih ruangan spesifik
            if ($ruanganId) {
                if ($ruanganId === 'tanpa-ruangan') {
                    $query->whereHas('qrCodes', fn($q) => $q->where('id_pemegang_personal', $user->id)->whereNull('deleted_at'));
                } elseif ($operatorRuanganIds->contains($ruanganId)) {
                    $query->whereHas('qrCodes', fn($q) => $q->where('id_ruangan', $ruanganId)->whereNull('deleted_at'));
                } else {
                    $query->whereRaw('0=1');
                }
            }
        } elseif ($user->hasRole(User::ROLE_ADMIN) && $ruanganId) { // Ini untuk Admin
            if ($ruanganId === 'tanpa-ruangan') {
                $query->whereHas('qrCodes', fn($q) => $q->whereNotNull('id_pemegang_personal')->whereNull('deleted_at'));
            } else {
                $query->whereHas('qrCodes', fn($q) => $q->where('id_ruangan', $ruanganId)->whereNull('deleted_at'));
            }
        }

        $barangs = $query->latest('updated_at')->paginate(15)->withQueryString();

        $rolePrefix = $this->getRolePrefix();
        $viewPath = $this->getViewPathBasedOnRole('pages.barang.index', 'pages.barang.index');

        return view($viewPath, compact(
            'barangs',
            'ruanganList',
            'ruanganId',
            'kategoriList',
            'kategoriId',
            'searchTerm',
            'operatorTidakAdaRuangan',
            'rolePrefix' // Variabel ditambahkan di sini
        ));
    }


    public function create(): View
    {
        $this->authorize('create', Barang::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $ruanganList = $user->hasRole(User::ROLE_OPERATOR)
            ? $user->ruanganYangDiKelola()->orderBy('nama_ruangan')->get()
            : Ruangan::orderBy('nama_ruangan')->get();
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $kondisiOptions = BarangQrCode::getValidKondisi();
        $pemegangListAll = User::whereIn('role', [User::ROLE_GURU, User::ROLE_OPERATOR, User::ROLE_ADMIN])->orderBy('username')->get();

        // View create.blade.php sekarang adalah view wizard
        $rolePrefix = $this->getRolePrefix();
        $viewPath = $this->getViewPathBasedOnRole('pages.barang.create', 'pages.barang.create');
        return view($viewPath, compact(
            'ruanganList',
            'kategoriList',
            'kondisiOptions',
            'pemegangListAll',
            'rolePrefix' // Variabel ditambahkan di sini
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Barang::class);

        $rules = [
            // Step 1: Jenis Barang
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required',
            'string',
            'max:50',
            'id_kategori' => 'required|exists:kategori_barangs,id',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'harga_perolehan_induk' => 'nullable|numeric|min:0',
            'sumber_perolehan_induk' => 'nullable|string|max:100',
            'menggunakan_nomor_seri' => 'required|boolean', // Dari radio button di wizard

            // Step 2: Rencana Unit Awal
            'jumlah_unit_awal' => 'required|integer|min:1',
            'id_ruangan_awal' => ['nullable', 'required_without:id_pemegang_personal_awal', 'exists:ruangans,id'],
            'id_pemegang_personal_awal' => [
                'nullable',
                'required_without:id_ruangan_awal',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->input('id_ruangan_awal')) {
                        $fail('Unit awal tidak bisa ditempatkan di ruangan dan dipegang personal sekaligus.');
                    }
                }
            ],
            'kondisi_unit_awal' => ['required', Rule::in(BarangQrCode::getValidKondisi())],
            'harga_perolehan_unit_awal' => 'nullable|numeric|min:0',
            'tanggal_perolehan_unit_awal' => 'required|date|before_or_equal:today',
            'sumber_dana_unit_awal' => 'nullable|string|max:255',
            'no_dokumen_unit_awal' => 'nullable|string|max:255',
            'deskripsi_unit_awal' => 'nullable|string',
        ];

        // Validasi kondisional untuk nomor seri
        if ($request->boolean('menggunakan_nomor_seri')) {
            $rules['serial_numbers'] = ['required', 'array', 'min:' . $request->input('jumlah_unit_awal', 1)];
            // Pastikan jumlah_unit_awal adalah integer untuk validasi min
            if ($request->input('jumlah_unit_awal') > 0) {
                $rules['serial_numbers'] = ['required', 'array', 'size:' . $request->input('jumlah_unit_awal')];
            }

            $rules['serial_numbers.*'] = [
                'required', // Setiap item dalam array wajib jika 'menggunakan_nomor_seri' true
                'string',
                'max:100',
                'distinct', // Unik dalam array request
                Rule::unique('barang_qr_codes', 'no_seri_pabrik')->whereNull('deleted_at')
            ];
        }

        $messages = [
            'jumlah_unit_awal.min' => 'Jumlah unit awal minimal 1.',
            'id_ruangan_awal.required_without' => 'Pilih ruangan atau pemegang personal untuk unit awal.',
            'id_pemegang_personal_awal.required_without' => 'Pilih pemegang personal atau ruangan untuk unit awal.',
            'harga_perolehan_unit_awal.required' => 'Harga perolehan per unit wajib diisi.',
            'tanggal_perolehan_unit_awal.required' => 'Tanggal perolehan unit wajib diisi.',
            'serial_numbers.required' => 'Nomor seri wajib diisi jika barang menggunakan nomor seri.',
            'serial_numbers.array' => 'Format nomor seri tidak valid.',
            'serial_numbers.min' => 'Jumlah nomor seri harus sesuai dengan Jumlah Unit Awal.',
            'serial_numbers.size' => 'Jumlah nomor seri harus tepat sesuai dengan Jumlah Unit Awal.',
            'serial_numbers.*.required' => 'Setiap nomor seri unit wajib diisi.',
            'serial_numbers.*.distinct' => 'Terdapat duplikasi nomor seri dalam input Anda.',
            'serial_numbers.*.unique' => 'Nomor seri ":input" sudah terdaftar di sistem.',
        ];

        $validated = $request->validate($rules, $messages);

        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole(User::ROLE_OPERATOR) && !empty($validated['id_ruangan_awal'])) {
            $ruanganAwal = Ruangan::find($validated['id_ruangan_awal']);
            if (!$ruanganAwal || !$user->ruanganYangDiKelola()->where('id', $ruanganAwal->id)->exists()) {
                return redirect()->back()->with('error', 'Anda tidak diizinkan menambah barang di ruangan yang dipilih.')->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $barangData = [
                'nama_barang' => $validated['nama_barang'],
                'kode_barang' => $validated['kode_barang'],
                'id_kategori' => $validated['id_kategori'],
                'merk_model' => $validated['merk_model'] ?? null,
                'ukuran' => $validated['ukuran'] ?? null,
                'bahan' => $validated['bahan'] ?? null,
                'tahun_pembuatan' => $validated['tahun_pembuatan'] ?? null,
                'harga_perolehan_induk' => $validated['harga_perolehan_induk'] ?? null,
                'sumber_perolehan_induk' => $validated['sumber_perolehan_induk'] ?? null,
                'menggunakan_nomor_seri' => $validated['menggunakan_nomor_seri'],
            ];
            $barang = Barang::create($barangData);

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Tambah Jenis Barang (Wizard)',
                'deskripsi' => "Menambahkan jenis barang: {$barang->nama_barang} (Kode: {$barang->kode_barang})",
                'model_terkait' => Barang::class,
                'id_model_terkait' => $barang->id,
                'data_baru' => json_encode($barang->getAttributes()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $unitDetails = [
                'id_ruangan' => $validated['id_ruangan_awal'] ?? null,
                'id_pemegang_personal' => $validated['id_pemegang_personal_awal'] ?? null,
                'kondisi' => $validated['kondisi_unit_awal'],
                'harga_perolehan_unit' => $validated['harga_perolehan_unit_awal'],
                'tanggal_perolehan_unit' => $validated['tanggal_perolehan_unit_awal'],
                'sumber_dana_unit' => $validated['sumber_dana_unit_awal'] ?? null,
                'no_dokumen_perolehan_unit' => $validated['no_dokumen_unit_awal'] ?? null,
                'deskripsi_unit' => $validated['deskripsi_unit_awal'] ?? 'Unit awal untuk ' . $barang->nama_barang,
            ];

            $this->createUnitsForBarang(
                $barang,
                (int)$validated['jumlah_unit_awal'],
                $unitDetails,
                $validated['menggunakan_nomor_seri'] ? ($validated['serial_numbers'] ?? []) : null,
                $request,
                $user->id
            );

            DB::commit();

            // Menggunakan helper baru untuk mendapatkan prefix route
            $prefix = $this->getRolePrefix();
            // Redirect ke route admin karena hanya admin yang bisa store
            return redirect()->route($prefix . 'barang.show', $barang->id)
                ->with('success', "Jenis barang '{$barang->nama_barang}' dan {$validated['jumlah_unit_awal']} unit fisik berhasil ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan barang (wizard): {$e->getMessage()}", ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal menyimpan barang: ' . (config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem.'))->withInput();
        }
    }

    private function createUnitsForBarang(Barang $barang, int $quantity, array $unitDetails, ?array $serialNumbers = null, Request $requestForLog, int $pencatatId): void
    {
        for ($i = 0; $i < $quantity; $i++) {
            $unit = BarangQrCode::createWithQrCodeImage(
                idBarang: $barang->id,
                idRuangan: $unitDetails['id_ruangan'], // Sudah nullable di createWithQrCodeImage
                noSeriPabrik: $serialNumbers[$i] ?? null,
                hargaPerolehanUnit: $unitDetails['harga_perolehan_unit'],
                tanggalPerolehanUnit: $unitDetails['tanggal_perolehan_unit'],
                sumberDanaUnit: $unitDetails['sumber_dana_unit'],
                noDokumenPerolehanUnit: $unitDetails['no_dokumen_perolehan_unit'],
                kondisi: $unitDetails['kondisi'],
                status: BarangQrCode::STATUS_TERSEDIA,
                deskripsiUnit: $unitDetails['deskripsi_unit'] . ($quantity > 1 ? ' - Unit ' . ($i + 1) : ''),
                idPemegangPersonal: $unitDetails['id_pemegang_personal'], // Sudah nullable
                idPemegangPencatat: $pencatatId
            );

            LogAktivitas::create([
                'id_user' => $pencatatId,
                'aktivitas' => 'Tambah Unit Barang Otomatis',
                'deskripsi' => "Menambahkan unit barang: {$unit->kode_inventaris_sekolah} untuk jenis barang {$barang->nama_barang}",
                'model_terkait' => BarangQrCode::class,
                'id_model_terkait' => $unit->id,
                'data_baru' => $unit->toJson(),
                'ip_address' => $requestForLog->ip(),
                'user_agent' => $requestForLog->userAgent(),
            ]);
        }
    }

    // Method ini sudah benar, tidak perlu diubah.
    public function suggestSerialsForNew(Request $request): JsonResponse
    {
        $request->validate([
            'kode_barang_input' => 'nullable|string|max:50',
            'jumlah_unit' => 'required|integer|min:1',
        ]);

        $kodeBarangInput = strtoupper(str_replace(' ', '-', $request->input('kode_barang_input', 'BRG')));
        $jumlahUnit = (int) $request->input('jumlah_unit');
        $suggestions = [];

        $batchSuffix = strtoupper(Str::random(4));

        for ($i = 0; $i < $jumlahUnit; $i++) {
            $suggestions[] = $kodeBarangInput . '-RS' . $batchSuffix . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        }
        return response()->json($suggestions);
    }

    public function show(Barang $barang): View
    {
        $this->authorize('view', $barang);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $barang->load('kategori')->loadCount(['qrCodes as active_qr_codes_count' => fn($q) => $q->whereNull('deleted_at')]);

        $qrCodesQuery = $barang->qrCodes()
            ->whereNull('deleted_at') // Hanya unit yang aktif
            ->with(['ruangan', 'pemegangPersonal', 'arsip']) // Tambahkan 'arsip'
            ->latest('kode_inventaris_sekolah'); // Urutan default, bisa diubah

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $operatorRuanganIds = $user->ruanganYangDiKelola()->pluck('id');
            if ($operatorRuanganIds->isNotEmpty()) {
                $qrCodesQuery->where(function ($q) use ($operatorRuanganIds, $user) {
                    $q->whereIn('id_ruangan', $operatorRuanganIds) // Unit di ruangan operator
                        ->orWhere('id_pemegang_personal', $user->id) // Unit yang dipegang operator itu sendiri
                        ->orWhere(fn($subQ) => $subQ->whereNull('id_ruangan')->whereNull('id_pemegang_personal')); // Unit mengambang (jika diizinkan)
                });
            } else {
                $qrCodesQuery->whereRaw('0=1'); // Operator tidak punya ruangan, tidak bisa lihat unit
            }
        }

        $qrCodes = $qrCodesQuery->paginate(15)->withQueryString(); // Menggunakan paginasi

        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $ruanganListAll = Ruangan::orderBy('nama_ruangan')->get();
        $userListAll = User::whereIn('role', [User::ROLE_GURU, User::ROLE_OPERATOR, User::ROLE_ADMIN])->orderBy('username')->get();
        $kondisiOptionsAll = BarangQrCode::getValidKondisi();
        $statusOptionsAll = BarangQrCode::getValidStatus();
        $jenisPenghapusanOptions = ArsipBarang::getValidJenisPenghapusan();

        $rolePrefix = $this->getRolePrefix();
        $viewPath = $this->getViewPathBasedOnRole('pages.barang.show', 'pages.barang.show');

        return view($viewPath, compact(
            'barang',
            'qrCodes',
            'kategoriList',
            'ruanganListAll',
            'userListAll',
            'kondisiOptionsAll',
            'statusOptionsAll',
            'jenisPenghapusanOptions',
            'rolePrefix' // Variabel ditambahkan di sini
        ));
    }

    public function edit(Barang $barang): View
    {
        $this->authorize('update', $barang);
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $barang->loadCount(['qrCodes as qr_codes_count' => fn($q) => $q->whereNull('deleted_at')]);

        $rolePrefix = $this->getRolePrefix();
        $viewPath = $this->getViewPathBasedOnRole('pages.barang.edit', 'pages.barang.edit');

        return view($viewPath, compact(
            'barang',
            'kategoriList',
            'rolePrefix' // Variabel ditambahkan di sini
        ));
    }

    public function update(Request $request, Barang $barang): RedirectResponse
    {
        $this->authorize('update', $barang);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $oldData = $barang->getRawOriginal();
        $hasActiveUnits = $barang->qrCodes()->whereNull('deleted_at')->exists();

        $rules = [
            'nama_barang' => ['required', 'string', 'max:255', Rule::unique('barangs', 'nama_barang')->ignore($barang->id)->whereNull('deleted_at')],
            'id_kategori' => 'required|exists:kategori_barangs,id',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:' . (date('Y') + 5),
            'harga_perolehan_induk' => 'nullable|numeric|min:0',
            'sumber_perolehan_induk' => 'nullable|string|max:100',
        ];

        // Hanya izinkan ubah kode_barang dan menggunakan_nomor_seri jika BELUM ada unit aktif
        if (!$hasActiveUnits) {
            $rules['kode_barang'] = ['required', 'string', 'max:50', Rule::unique('barangs', 'kode_barang')->ignore($barang->id)->whereNull('deleted_at')];
            $rules['menggunakan_nomor_seri'] = 'required|boolean';
        } else {
            // Jika sudah ada unit aktif, field ini tidak boleh diubah dari nilai yang sudah ada
            if ($request->filled('kode_barang') && $request->input('kode_barang') !== $barang->kode_barang) {
                return back()->withErrors(['kode_barang' => 'Kode Barang tidak dapat diubah jika sudah ada unit fisik terkait.'])->withInput();
            }
            if ($request->has('menggunakan_nomor_seri') && $request->boolean('menggunakan_nomor_seri') !== $barang->menggunakan_nomor_seri) {
                return back()->withErrors(['menggunakan_nomor_seri' => 'Opsi "Menggunakan Nomor Seri" tidak dapat diubah jika sudah ada unit fisik terkait.'])->withInput();
            }
        }
        $validatedData = $request->validate($rules);

        // Ambil data yang akan diupdate, kecualikan field yang mungkin tidak diizinkan diubah jika sudah ada unit
        $updateData = collect($validatedData);
        if ($hasActiveUnits) {
            $updateData = $updateData->except(['kode_barang', 'menggunakan_nomor_seri']);
        }

        $barang->fill($updateData->toArray());

        if ($barang->isDirty()) {
            $changedAttributes = $barang->getDirty();
            $barang->save();

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Update Jenis Barang',
                'deskripsi' => "Memperbarui jenis barang: {$barang->nama_barang}",
                'model_terkait' => Barang::class,
                'id_model_terkait' => $barang->id,
                'data_lama' => json_encode(array_intersect_key($oldData, $changedAttributes)),
                'data_baru' => json_encode($changedAttributes),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return redirect()->route('admin.barang.show', $barang->id)->with('success', 'Data jenis barang berhasil diperbarui.');
        }

        // --- PERUBAHAN DI SINI ---
        return redirect()->route('admin.barang.show', $barang->id)->with('info', 'Tidak ada perubahan data.');
    }

    public function destroy(Request $request, Barang $barang): RedirectResponse
    {
        $this->authorize('delete', $barang);
        $user = Auth::user();

        $validatedModal = $request->validate([
            'jenis_penghapusan' => ['required', Rule::in(array_keys(ArsipBarang::getValidJenisPenghapusan()))],
            'alasan_penghapusan' => 'required|string|max:1000',
            'berita_acara_path' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'foto_bukti_path' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'konfirmasi_hapus_semua' => 'required|in:HAPUS SEMUA',
        ]);

        if ($barang->qrCodes()->where('status', BarangQrCode::STATUS_DIPINJAM)->whereNull('deleted_at')->exists()) {
            return back()->with('error', 'Gagal menghapus. Terdapat unit dari jenis barang ini yang sedang dalam status dipinjam.');
        }

        DB::beginTransaction();
        try {
            $beritaAcaraPath = $request->hasFile('berita_acara_path') ? $request->file('berita_acara_path')->store('arsip/berita_acara_induk', 'public') : null;
            $fotoBuktiPath = $request->hasFile('foto_bukti_path') ? $request->file('foto_bukti_path')->store('arsip/foto_bukti_induk', 'public') : null;
            $dataLamaBarang = $barang->getAttributes();
            $listUnitYangDiarsipkan = [];

            foreach ($barang->qrCodes()->whereNull('deleted_at')->get() as $unit) {

                $unit->load('barang.kategori', 'ruangan', 'pemegangPersonal');

                $arsip = ArsipBarang::create([
                    'id_barang_qr_code' => $unit->id,
                    'id_user_pengaju' => $user->id,
                    'id_user_penyetuju' => $user->id,
                    'jenis_penghapusan' => $validatedModal['jenis_penghapusan'],
                    'alasan_penghapusan' => $validatedModal['alasan_penghapusan'] . " (Penghapusan massal dari jenis barang: {$barang->nama_barang})",
                    'berita_acara_path' => $beritaAcaraPath,
                    'foto_bukti_path' => $fotoBuktiPath,
                    'tanggal_pengajuan_arsip' => now(),
                    'tanggal_penghapusan_resmi' => now(),
                    'status_arsip' => ArsipBarang::STATUS_ARSIP_DISETUJUI_PERMANEN,
                    'data_unit_snapshot' => $unit->toArray(),
                ]);

                BarangStatus::create([
                    'id_barang_qr_code' => $unit->id,
                    'id_user_pencatat' => $user->id,
                    'tanggal_pencatatan' => now(),
                    'kondisi_sebelumnya' => $unit->kondisi,
                    'status_ketersediaan_sebelumnya' => $unit->status,
                    'kondisi_sesudahnya' => $unit->kondisi,
                    'status_ketersediaan_sesudahnya' => 'Diarsipkan/Dihapus',
                    'id_ruangan_sebelumnya' => $unit->id_ruangan,
                    'id_pemegang_personal_sebelumnya' => $unit->id_pemegang_personal,
                    'deskripsi_kejadian' => "Unit diarsipkan dan di-soft-delete karena penghapusan jenis barang. Arsip ID: {$arsip->id}",
                    'id_arsip_barang_trigger' => $arsip->id,
                ]);
                $unit->delete();
                $listUnitYangDiarsipkan[] = $unit->kode_inventaris_sekolah;
            }
            $barang->delete();

            LogAktivitas::create([
                'id_user' => $user->id,
                'aktivitas' => 'Hapus Jenis Barang & Arsip Unit',
                'deskripsi' => "Menghapus jenis: {$barang->nama_barang}. Unit terarsip: " . implode(', ', $listUnitYangDiarsipkan),
                'model_terkait' => Barang::class,
                'id_model_terkait' => $barang->id,
                'data_lama' => json_encode($dataLamaBarang),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            DB::commit();
            // Kode Perbaikan
            return redirect($this->getRedirectUrl('barang'))->with('success', 'Jenis barang dan semua unit aktifnya berhasil dihapus (diarsipkan).');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal hapus jenis barang (ID: {$barang->id}): {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Gagal hapus jenis barang: ' . (config('app.debug') ? $e->getMessage() : 'Kesalahan sistem.'));
        }
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

    // --- HELPER BARU UNTUK MENGGANTIKAN getRedirectRouteName ---
    private function getRolePrefix(): string
    {
        $user = Auth::user();
        if (!$user) return ''; // Fallback

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
     * Download QR Code for a specific BarangQrCode.
     *
     * @param BarangQrCode $barangQrCode
     * @return BinaryFileResponse|RedirectResponse
     */
    public function downloadQrCode(BarangQrCode $barangQrCode): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('view', $barangQrCode->barang);

        if (!$barangQrCode->qr_path || !Storage::disk('public')->exists($barangQrCode->qr_path)) {
            Log::warning("File QR tidak ada untuk unit: {$barangQrCode->kode_inventaris_sekolah}, mencoba generate ulang.");
            try {
                $qrContent = $barangQrCode->getQrCodeContent();
                $directory = 'qr_codes';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                $filename = $directory . '/' . Str::slug($barangQrCode->kode_inventaris_sekolah ?: ('unit-' . $barangQrCode->id . '-' . Str::random(5))) . '.svg';

                $qrImage = GeneratorQrCode::format('svg')->size(200)->errorCorrection('H')->generate($qrContent);
                Storage::disk('public')->put($filename, $qrImage);
                $barangQrCode->updateQuietly(['qr_path' => $filename]);

                return response()->download(Storage::disk('public')->path($filename));
            } catch (\Exception $e) {
                Log::error("Gagal generate QR untuk unit {$barangQrCode->id}: " . $e->getMessage());
                return redirect()->back()->with('error', 'File QR Code tidak ditemukan dan gagal dibuat ulang.');
            }
        }
        return response()->download(Storage::disk('public')->path($barangQrCode->qr_path));
    }

    public function printAllQrCodes(Barang $barang): View|RedirectResponse
    {
        $this->authorize('view', $barang);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $qrCodesQuery = $barang->qrCodes()->whereNull('deleted_at')->with(['ruangan', 'pemegangPersonal']);

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $operatorRuanganIds = $user->ruanganYangDiKelola()->pluck('id');
            if ($operatorRuanganIds->isNotEmpty()) {
                $qrCodesQuery->where(function ($q) use ($operatorRuanganIds) {
                    $q->whereIn('id_ruangan', $operatorRuanganIds)
                        ->orWhereNull('id_ruangan')
                        ->orWhereNotNull('id_pemegang_personal');
                });
            } else {
                return back()->with('error', 'Anda tidak memiliki akses ke ruangan manapun untuk mencetak QR Code.');
            }
        }
        $qrCodesToPrint = $qrCodesQuery->get();

        if ($qrCodesToPrint->isEmpty()) {
            return back()->with('error', 'Tidak ada unit aktif dari jenis barang ini yang dapat dicetak QR Code-nya (sesuai filter ruangan Anda jika operator).');
        }
        $rolePrefix = $this->getRolePrefix();
        $viewPath = $this->getViewPathBasedOnRole('pages.barang.print_qrcodes', 'pages.barang.print_qrcodes');

        return view($viewPath, compact(
            'barang',
            'qrCodesToPrint',
            'rolePrefix' // Variabel ditambahkan di sini
        ));
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
