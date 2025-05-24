<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang;
use App\Models\Ruangan;
use App\Models\ArsipBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Configuration\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarangController extends Controller
{
    // =========================================================================
    //  Barang Management - Unit Based Architecture
    // =========================================================================

    /**
     * Display a listing of active barang (agregat dengan unit aktif)
     */
    public function index(Request $request)
    {
        $ruanganId = $request->get('id_ruangan');
        $ruanganList = Ruangan::all();
        $user = Auth::user();
        $kategoriList = KategoriBarang::all();

        // Query barang yang memiliki unit aktif (tidak soft deleted)
        $barang = Barang::with(['kategori'])
            ->whereHas('qrCodes', function ($query) use ($ruanganId, $user) {
                // Unit aktif (tidak soft deleted)
                $query->whereNull('deleted_at');

                if ($ruanganId) {
                    $query->where('id_ruangan', $ruanganId);
                }

                if ($user->role === 'Operator') {
                    $query->whereIn('id_ruangan', Ruangan::where('id_operator', $user->id)->pluck('id'));
                }
            })
            ->withCount(['qrCodes as jumlah_unit_aktif' => function ($query) use ($ruanganId, $user) {
                $query->whereNull('deleted_at');

                if ($ruanganId) {
                    $query->where('id_ruangan', $ruanganId);
                }

                if ($user->role === 'Operator') {
                    $query->whereIn('id_ruangan', Ruangan::where('id_operator', $user->id)->pluck('id'));
                }
            }])
            ->get();

        // Load unit aktif untuk setiap barang
        foreach ($barang as $item) {
            $item->load(['qrCodes' => function ($query) use ($ruanganId, $user) {
                $query->whereNull('deleted_at')->with('ruangan');

                if ($ruanganId) {
                    $query->where('id_ruangan', $ruanganId);
                }

                if ($user->role === 'Operator') {
                    $query->whereIn('id_ruangan', Ruangan::where('id_operator', $user->id)->pluck('id'));
                }
            }]);
        }

        // Sinkronisasi jumlah_barang dengan unit aktif
        $this->syncJumlahBarang();

        if (Auth::user()->role == 'Admin') {
            return view('admin.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'kategoriList'));
        } elseif (Auth::user()->role == 'Operator') {
            return view('operator.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'kategoriList'));
        }

        return view('admin.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'kategoriList'));
    }

    /**
     * Sinkronisasi jumlah_barang dengan jumlah unit aktif
     */
    private function syncJumlahBarang()
    {
        DB::statement("
            UPDATE barang 
            SET jumlah_barang = (
                SELECT COUNT(*) 
                FROM barang_qr_code 
                WHERE barang_qr_code.id_barang = barang.id 
                AND barang_qr_code.deleted_at IS NULL
            )
        ");

        // Soft delete barang yang tidak memiliki unit aktif
        DB::statement("
            UPDATE barang 
            SET deleted_at = NOW(), deleted_by = ?
            WHERE id NOT IN (
                SELECT DISTINCT id_barang 
                FROM barang_qr_code 
                WHERE deleted_at IS NULL
            )
            AND deleted_at IS NULL
        ", [Auth::id()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ruanganList = Ruangan::all();
        $kategoriList = KategoriBarang::all();
        $user = Auth::user();

        if ($user->role === 'Operator') {
            $ruanganList = Ruangan::where('id_operator', $user->id)->get();
        }

        if ($user->role === 'Admin') {
            return view('admin.barang.create', compact('ruanganList', 'kategoriList'));
        } else {
            return view('operator.barang.create', compact('ruanganList', 'kategoriList'));
        }
    }

    /**
     * Store a newly created resource in storage.
     * Langkah 1: Input data barang agregat
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan_pembelian' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kode_barang' => 'required|string|max:50',
            'jumlah_barang' => 'required|integer|min:1',
            'harga_beli' => 'nullable|numeric|min:0',
            'sumber' => 'nullable|string|max:100',
            'keadaan_barang' => 'required|in:Baik,Kurang Baik,Rusak Berat',
            'id_kategori' => 'required|exists:kategori_barang,id',
            'id_ruangan' => 'required|exists:ruangan,id', // Unit akan ditempatkan di ruangan ini
            'menggunakan_nomor_seri' => 'boolean',
        ]);

        $ruangan = Ruangan::findOrFail($request->id_ruangan);
        $user = Auth::user();

        // Cek akses operator
        if ($user->role === 'Operator' && $ruangan->id_operator != $user->id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menambah barang di ruangan ini.');
        }

        try {
            // Buat barang agregat (tanpa id_ruangan di tabel barang)
            $barangData = $validated;
            unset($barangData['id_ruangan']); // Hapus id_ruangan dari data barang
            $barang = Barang::create($barangData);

            // Simpan data sesi untuk wizard
            session([
                'incomplete_barang_id' => $barang->id,
                'incomplete_started_at' => now(),
                'target_ruangan_id' => $request->id_ruangan
            ]);

            if ($barang->menggunakan_nomor_seri) {
                return redirect()->route('barang.input-serial', ['id' => $barang->id])
                    ->with('success', 'Data barang berhasil disimpan. Silakan input nomor seri untuk setiap unit.');
            }

            // Generate unit otomatis
            $this->createUnitsForBarang($barang, $request->id_ruangan);

            return redirect()->route('barang.index')
                ->with('success', 'Barang berhasil ditambahkan dan unit telah dibuat otomatis.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan barang: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan barang.');
        }
    }

    /**
     * Menampilkan form edit dalam wizard (Step 1)
     * Method baru untuk edit di Step 1 saat masih dalam proses wizard
     */
    public function editStep1($id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Cek akses operator
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengedit barang ini.');
            }
        }

        // Cek apakah barang sudah memiliki QR Code
        $hasQrCodes = $barang->qrCodes()->exists();

        // Load data yang diperlukan
        $ruanganList = Ruangan::all();
        if ($user->role === 'Operator') {
            $ruanganList = Ruangan::where('id_operator', $user->id)->get();
        }
        $kategoriList = KategoriBarang::all();

        // View sesuai role
        $viewPath = $user->role === 'Admin' ? 'admin.barang.create' : 'operator.barang.create';

        return view($viewPath, [
            'barang' => $barang,
            'ruanganList' => $ruanganList,
            'kategoriList' => $kategoriList,
            'editMode' => true,
            'wizardStep' => 1, // Menandakan bahwa ini adalah edit dalam wizard step 1
            'hasQrCodes' => $hasQrCodes
        ]);
    }

    /**
     * Update data barang dari Step 1 wizard
     * Method baru untuk update dari wizard Step 1
     */
    public function updateStep1(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Akses validasi
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin.');
            }
        }

        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:50',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan_pembelian' => 'nullable|integer|min:1900|max:' . date('Y'),
            'jumlah_barang' => 'required|integer|min:1',
            'harga_beli' => 'nullable|numeric|min:0',
            'sumber' => 'nullable|string|max:100',
            'keadaan_barang' => 'required|in:Baik,Kurang Baik,Rusak Berat',
            'id_ruangan' => 'required|exists:ruangan,id',
            'id_kategori' => 'required|exists:kategori_barang,id',
            'menggunakan_nomor_seri' => 'nullable|boolean',
        ]);

        $isWizard = $request->has('wizard_step');
        $wasUsingSerial = $barang->menggunakan_nomor_seri;
        $willUseSerial = $validated['menggunakan_nomor_seri'] ?? false;

        // Cek jumlah QR eksisting
        $existingQrCount = $barang->qrCodes()->count();
        if ($existingQrCount > 0 && $validated['jumlah_barang'] < $existingQrCount) {
            return back()->withErrors(['jumlah_barang' => "Jumlah tidak boleh kurang dari QR yang sudah ada ($existingQrCount)"]);
        }

        // Update dulu sebelum logic lanjut
        $barang->update($validated);

        // === CASE A: User ubah menjadi TANPA nomor seri (harus generate QR sekarang)
        if ($isWizard && !$willUseSerial && $wasUsingSerial) {
            Log::info('createQrCodes dipanggil di updateStep1', [
                'barang_id' => $barang->id,
                'isWizard' => $isWizard,
                'wasUsingSerial' => $wasUsingSerial,
                'willUseSerial' => $willUseSerial,
                'qr_count' => $barang->qrCodes()->count(),
                'jumlah_barang' => $validated['jumlah_barang'],
            ]);

            $barang->createQrCodes();
            return redirect()->route('barang.index')
                ->with('success', 'Barang berhasil diperbarui dan QR Code telah digenerate otomatis.');
        }

        // === CASE B: Tetap pakai nomor seri → lanjut ke step2
        if ($isWizard && $willUseSerial) {
            session()->forget(['incomplete_barang_id', 'incomplete_started_at']);
            return redirect()->route('barang.input-serial', $barang->id);
        }

        // === CASE C: Edit biasa, bukan wizard
        if (!$wasUsingSerial && !$barang->qrCodes()->exists()) {
            session()->forget(['incomplete_barang_id', 'incomplete_started_at']);
            $barang->createQrCodes();
        }

        return redirect()->route('barang.index')->with('success', 'Barang berhasil diperbarui.');
    }

    /**
     * Buat unit-unit untuk barang
     */
    private function createUnitsForBarang($barang, $serialNumbers = null)
    {
        $id_ruangan = session('target_ruangan_id'); // Get from session
        for ($i = 0; $i < $barang->jumlah_barang; $i++) {
            $noSeri = $serialNumbers ? $serialNumbers[$i] : $this->generateAutoSerial($barang, $i + 1);

            // Buat unit di ruangan yang ditentukan
            $unit = BarangQrCode::create([
                'id_barang' => $barang->id,
                'id_ruangan' => $id_ruangan,
                'no_seri_pabrik' => $noSeri,
                'keadaan_barang' => $barang->keadaan_barang,
                'status' => 'Tersedia',
                'qr_path' => null, // Akan diisi saat generate QR
            ]);

            // Generate QR Code
            $this->generateQrCodeForUnit($unit);
        }

        // Clear session setelah berhasil
        session()->forget(['incomplete_barang_id', 'incomplete_started_at', 'target_ruangan_id']);
    }

    /**
     * Generate nomor seri otomatis
     */
    private function generateAutoSerial($barang, $sequence)
    {
        return $barang->kode_barang . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT) . '-' . date('Y');
    }

    /**
     * Generate QR Code untuk unit
     */
    private function generateQrCodeForUnit($unit)
    {
        $qr_image = QrCode::format('svg')->size(300)->generate($unit->no_seri_pabrik);
        $filename = 'qr_codes/' . $unit->no_seri_pabrik . '.svg';
        Storage::disk('public')->put($filename, $qr_image);

        $unit->update(['qr_path' => $filename]);
    }

    /**
     * Form input nomor seri (Wizard Step 2)
     */
    public function inputSerialForm($id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();
        $targetRuanganId = session('target_ruangan_id');

        // Validasi akses
        if ($user->role === 'Operator') {
            $ruangan = Ruangan::find($targetRuanganId);
            if (!$ruangan || $ruangan->id_operator != $user->id) {
                abort(403, 'Anda tidak memiliki akses ke ruangan target.');
            }
        }

        if (!$barang->menggunakan_nomor_seri) {
            return redirect()->route('barang.index')
                ->with('error', 'Barang ini tidak memerlukan input nomor seri manual.');
        }

        $serialInputs = array_fill(0, $barang->jumlah_barang, '');

        if ($user->role === 'Admin') {
            return view('admin.barang.input_serial', compact('barang', 'serialInputs'));
        } else {
            return view('operator.barang.input_serial', compact('barang', 'serialInputs'));
        }
    }

    /**
     * Store nomor seri dan buat unit (Wizard Step 2)
     */
    public function storeSerialNumbers(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();
        $targetRuanganId = session('target_ruangan_id');

        // Validasi akses
        if ($user->role === 'Operator') {
            $ruangan = Ruangan::find($targetRuanganId);
            if (!$ruangan || $ruangan->id_operator != $user->id) {
                abort(403, 'Anda tidak memiliki akses ke ruangan target.');
            }
        }

        $request->validate([
            'serial_numbers' => 'required|array|size:' . $barang->jumlah_barang,
            'serial_numbers.*' => 'required|string|distinct|max:100|unique:barang_qr_code,no_seri_pabrik',
        ], [
            'serial_numbers.size' => 'Jumlah nomor seri harus sesuai dengan jumlah barang (' . $barang->jumlah_barang . ')',
            'serial_numbers.*.required' => 'Semua nomor seri harus diisi',
            'serial_numbers.*.distinct' => 'Nomor seri tidak boleh sama',
            'serial_numbers.*.unique' => 'Nomor seri sudah digunakan',
        ]);

        $this->createUnitsForBarang($barang, $targetRuanganId, $request->serial_numbers);

        return redirect()->route('barang.index')
            ->with('success', 'Nomor seri barang berhasil disimpan dan unit telah dibuat.');
    }

    /**
     * Generate saran nomor seri
     */
    public function suggestSerials($id)
    {
        $barang = Barang::findOrFail($id);
        $suggestions = [];

        for ($i = 1; $i <= $barang->jumlah_barang; $i++) {
            $suggestions[] = $this->generateAutoSerial($barang, $i);
        }

        return response()->json($suggestions);
    }

    /**
     * Cancel wizard process
     */
    public function cancelCreate($id)
    {
        $barang = Barang::findOrFail($id);

        if (session('incomplete_barang_id') == $barang->id) {
            // Hapus unit yang mungkin sudah dibuat
            foreach ($barang->qrCodes as $unit) {
                if ($unit->qr_path && Storage::disk('public')->exists($unit->qr_path)) {
                    Storage::disk('public')->delete($unit->qr_path);
                }
                $unit->delete();
            }

            $barang->delete();
            session()->forget(['incomplete_barang_id', 'incomplete_started_at', 'target_ruangan_id']);

            return redirect()->route('barang.index')
                ->with('info', 'Pembuatan barang dibatalkan');
        }

        return redirect()->route('barang.index')
            ->with('error', 'Tidak dapat membatalkan pembuatan barang');
    }

    /**
     * Show barang detail dengan semua unit-nya
     */
    public function show(string $id)
    {
        $barang = Barang::with(['kategori'])->findOrFail($id);
        $user = Auth::user();

        // Load unit aktif saja
        $barang->load(['qrCodes' => function ($query) use ($user) {
            $query->whereNull('deleted_at')->with('ruangan');

            if ($user->role === 'Operator') {
                $query->whereIn('id_ruangan', Ruangan::where('id_operator', $user->id)->pluck('id'));
            }
        }]);

        // Cek akses untuk operator
        if ($user->role === 'Operator') {
            $accessibleUnits = $barang->qrCodes->filter(function ($unit) use ($user) {
                return in_array($unit->id_ruangan, Ruangan::where('id_operator', $user->id)->pluck('id')->toArray());
            });

            if ($accessibleUnits->isEmpty()) {
                abort(403, 'Akses ditolak');
            }
        }

        if ($user->role === 'Admin') {
            return view('admin.barang.show', compact('barang'));
        } else {
            return view('operator.barang.show', compact('barang'));
        }
    }

    /**
     * Update barang agregat (hanya info umum)
     */
    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan_pembelian' => 'nullable|integer|min:1900|max:' . date('Y'),
            'harga_beli' => 'nullable|numeric|min:0',
            'sumber' => 'nullable|string|max:100',
            'id_kategori' => 'required|exists:kategori_barang,id',
        ]);

        $barang->update($validated);
        return back()->with('success', 'Data barang berhasil diperbarui.');
    }

    /**
     * Soft delete semua unit barang (akan auto-delete agregat jika semua unit terhapus)
     */
    public function destroy(string $id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Validasi akses operator
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $unitTidakBisaAkses = $barang->qrCodes()->whereNotIn('id_ruangan', $ruanganYangDiKelola)->count();

            if ($unitTidakBisaAkses > 0) {
                abort(403, 'Anda tidak memiliki izin untuk menghapus semua unit barang ini.');
            }
        }

        // Cek apakah ada unit yang sedang dipinjam
        $unitDipinjam = $barang->qrCodes()->whereNull('deleted_at')->where('status', 'Dipinjam')->count();
        if ($unitDipinjam > 0) {
            return redirect()->route('barang.index')
                ->with('error', 'Barang tidak dapat dihapus karena ada unit yang masih dipinjam.');
        }

        try {
            DB::transaction(function () use ($barang, $user) {
                // Soft delete semua unit aktif
                $barang->qrCodes()->whereNull('deleted_at')->update([
                    'deleted_at' => now(),
                    'deleted_by' => $user->id,
                    'alasan_hapus' => 'Dihapus bersama agregat barang',
                ]);

                // Agregat akan otomatis ter-soft delete melalui syncJumlahBarang()
                $this->syncJumlahBarang();
            });

            return redirect()->route('barang.index')
                ->with('success', 'Barang dan semua unitnya berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus barang: ' . $e->getMessage());
            return redirect()->route('barang.index')
                ->with('error', 'Terjadi kesalahan saat menghapus barang.');
        }
    }

    /**
     * Download QR Code untuk satu unit
     */
    public function downloadQrCode($id)
    {
        $qrCode = BarangQrCode::with('barang')->findOrFail($id);
        $user = Auth::user();

        // Validasi akses
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($qrCode->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses QR Code ini.');
            }
        }

        // Generate QR jika belum ada
        if (!$qrCode->qr_path || !Storage::disk('public')->exists($qrCode->qr_path)) {
            $this->generateQrCodeForUnit($qrCode);
        }

        $path = Storage::disk('public')->path($qrCode->qr_path);
        return response()->download($path, $qrCode->barang->nama_barang . '_' . $qrCode->no_seri_pabrik . '.svg');
    }

    /**
     * Print semua QR Code untuk unit aktif barang
     */
    public function printAllQrCodes($id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Load unit aktif saja
        $barang->load(['qrCodes' => function ($query) use ($user) {
            $query->whereNull('deleted_at');

            if ($user->role === 'Operator') {
                $query->whereIn('id_ruangan', Ruangan::where('id_operator', $user->id)->pluck('id'));
            }
        }]);

        // Validasi akses
        if ($user->role === 'Operator' && $barang->qrCodes->isEmpty()) {
            abort(403, 'Anda tidak memiliki izin untuk mencetak QR Code barang ini.');
        }

        return view('qrcode.print', compact('barang'));
    }

    // =========================================================================
    //  Unit Management Methods (akan dipindah ke BarangQrCodeController)
    // =========================================================================

    /**
     * Method ini sebaiknya dipindah ke BarangQrCodeController
     * Menampilkan unit-unit dari barang tertentu
     */
    public function showUnits($barangId)
    {
        $barang = Barang::findOrFail($barangId);
        $user = Auth::user();

        $units = $barang->qrCodes()->whereNull('deleted_at')->with('ruangan');

        if ($user->role === 'Operator') {
            $units = $units->whereIn('id_ruangan', Ruangan::where('id_operator', $user->id)->pluck('id'));
        }

        $units = $units->get();

        if ($user->role === 'Admin') {
            return view('admin.barang.units', compact('barang', 'units'));
        } else {
            return view('operator.barang.units', compact('barang', 'units'));
        }
    }
}
