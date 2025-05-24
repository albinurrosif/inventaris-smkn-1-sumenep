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
use Illuminate\Foundation\Configuration\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class BarangController extends Controller
{
    // =========================================================================
    //  Barang Management
    // =========================================================================

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ruanganId = $request->get('id_ruangan');
        $ruanganList = Ruangan::all();
        $user = Auth::user();
        $kategoriList = KategoriBarang::all();

        $barang = Barang::with(['ruangan', 'kategori'])
            ->when($ruanganId, function ($query, $ruanganId) {
                return $query->where('id_ruangan', $ruanganId);
            })
            ->when($user->role === 'Operator', function ($query) use ($user) {
                // Hanya tampilkan barang di ruangan yang dikelola operator
                $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
                return $query->whereIn('id_ruangan', $ruanganYangDiKelola);
            })
            ->get();

        // Membedakan view berdasarkan peran pengguna
        if (Auth::user()->role == 'Admin') {
            return view('admin.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'kategoriList'));
        } elseif (Auth::user()->role == 'Operator') {
            return view('operator.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'kategoriList'));
        }

        // Default fallback
        return view('admin.barang.index', compact('barang', 'ruanganList', 'ruanganId', 'kategoriList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ruanganList = Ruangan::all();
        $kategoriList = KategoriBarang::all();
        $user = Auth::user();

        // Jika user adalah operator, filter ruangan yang bisa dipilih
        if ($user->role === 'Operator') {
            $ruanganList = Ruangan::where('id_operator', $user->id)->get();
        }

        // Arahkan ke view sesuai role
        if ($user->role === 'Admin') {
            return view('admin.barang.create', compact('ruanganList', 'kategoriList'));
        } else {
            return view('operator.barang.create', compact('ruanganList', 'kategoriList'));
        }
    }

    /**
     * Store a newly created resource in storage.
     * Langkah 1: Input data barang utama
     */
    public function store(Request $request)
    {
        // Validasi data yang diterima dari form
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan_pembelian' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kode_barang' => 'required|string|max:50',
            'jumlah_barang' => 'required|integer|min:1', // Minimal 1 barang
            'harga_beli' => 'nullable|numeric|min:0',
            'sumber' => 'nullable|string|max:100',
            'keadaan_barang' => 'required|in:Baik,Kurang Baik,Rusak Berat',
            'id_ruangan' => 'required|exists:ruangan,id',
            'id_kategori' => 'required|exists:kategori_barang,id',
            'menggunakan_nomor_seri' => 'boolean', // Field baru untuk menentukan input nomor seri
        ]);

        // Ambil ruangan dan user yang sedang login
        $ruangan = Ruangan::findOrFail($request->id_ruangan);
        $user = Auth::user();

        // Cek apakah user memiliki izin untuk menambah barang di ruangan ini
        if ($user->role === 'Operator' && $ruangan->id_operator != $user->id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menambah barang di ruangan ini.');
        }

        try {
            // Membuat barang baru
            $barang = Barang::create($validated);

            // Simpan ID barang dalam session sebagai "incomplete"
            session(['incomplete_barang_id' => $barang->id, 'incomplete_started_at' => now()]);


            // Cek apakah perlu input manual nomor seri
            if ($barang->menggunakan_nomor_seri) {
                // Langkah 2: Redirect ke form input nomor seri (wizard step 2)
                return redirect()->route('barang.input-serial', ['id' => $barang->id])
                    ->with('success', 'Data barang berhasil disimpan. Silakan input nomor seri untuk setiap unit.');
            }

            // Jika tidak perlu input manual, generate QR Code otomatis
            // dan redirect ke halaman index dengan pesan sukses
            $barang->createQrCodes(); // Method dari model Barang

            return redirect()->route('barang.index')
                ->with('success', 'Barang berhasil ditambahkan dan QR Code telah di-generate otomatis.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan barang: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan barang.');
        }
    }

    /**
     * Menampilkan form input nomor seri (Wizard Step 2)
     */
    public function inputSerialForm($id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Cek otorisasi
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses barang ini.');
            }
        }

        // Jika barang tidak menggunakan nomor seri manual, redirect ke halaman index
        if (!$barang->menggunakan_nomor_seri) {
            return redirect()->route('barang.index')
                ->with('error', 'Barang ini tidak memerlukan input nomor seri manual.');
        }

        // Siapkan array kosong untuk jumlah unit yang perlu diinput
        $serialInputs = array_fill(0, $barang->jumlah_barang, '');

        // Redirect ke view sesuai role
        if ($user->role === 'Admin') {
            return view('admin.barang.input_serial', compact('barang', 'serialInputs'));
        } else {
            return view('operator.barang.input_serial', compact('barang', 'serialInputs'));
        }
    }

    /**
     * Menyimpan nomor seri dari form input (Wizard Step 2)
     */
    public function storeSerialNumbers(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Cek otorisasi
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses barang ini.');
            }
        }

        // Validasi input nomor seri
        $request->validate([
            'serial_numbers' => 'required|array|size:' . $barang->jumlah_barang,
            'serial_numbers.*' => 'required|string|distinct|max:100|unique:barang_qr_code,no_seri_pabrik',
        ], [
            'serial_numbers.size' => 'Jumlah nomor seri harus sesuai dengan jumlah barang (' . $barang->jumlah_barang . ')',
            'serial_numbers.*.required' => 'Semua nomor seri harus diisi',
            'serial_numbers.*.distinct' => 'Nomor seri tidak boleh sama',
        ]);

        // Simpan nomor seri ke database
        $serialNumbers = $request->serial_numbers;
        $barang->createQrCodes($serialNumbers);

        session()->forget(['incomplete_barang_id', 'incomplete_started_at']);

        return redirect()->route('barang.index')
            ->with('success', 'Nomor seri barang berhasil disimpan dan QR Code telah dibuat.');
    }

    /**
     * Generate saran nomor seri untuk barang
     */
    public function suggestSerials($id)
    {
        $barang = Barang::findOrFail($id);
        $jumlah = $barang->jumlah_barang;

        return response()->json($barang->generateSerialNumbers($jumlah));
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
     * Batalkan proses pembuatan barang
     */
    public function cancel($id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Cek akses
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk menghapus barang ini.');
            }
        }

        // Hapus session incomplete
        session()->forget(['incomplete_barang_id', 'incomplete_started_at']);

        // Hapus QR Code yang mungkin sudah dibuat
        foreach ($barang->qrCodes as $qrCode) {
            if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
                Storage::disk('public')->delete($qrCode->qr_path);
            }
            $qrCode->delete();
        }

        // Hapus barang
        $barang->delete();

        return redirect()->route('barang.index')
            ->with('success', 'Proses pembuatan barang dibatalkan.');
    }

    public function cancelCreate($id)
    {
        $barang = Barang::findOrFail($id);

        // Hapus barang jika sesuai dengan session incomplete_barang_id
        if (session('incomplete_barang_id') == $barang->id) {
            // Hapus QR Codes jika ada
            $barang->qrCodes()->delete();
            $barang->delete();

            // Hapus session incomplete
            session()->forget(['incomplete_barang_id', 'incomplete_started_at']);

            return redirect()->route('barang.index')
                ->with('info', 'Pembuatan barang dibatalkan');
        }

        return redirect()->route('barang.index')
            ->with('error', 'Tidak dapat membatalkan pembuatan barang');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $barang = Barang::with(['ruangan', 'kategori', 'qrCodes'])->findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk melihat detail barang ini.');
            }
        }

        if ($user->role === 'Admin') {
            return view('admin.barang.show', compact('barang'));
        } else {
            return view('operator.barang.show', compact('barang'));
        }
    }

    /**
     * Update the specified resource in storage.
     * Method ini hanya untuk update data informatif
     */
    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        // Validasi hanya kolom informatif
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
        ]);

        $barang->update($validated);

        return back()->with('success', 'Data barang berhasil diperbarui.');
    }

    


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $barang = Barang::findOrFail($id);
        $user = Auth::user();

        // Validasi akses operator
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk menghapus barang ini.');
            }
        }

        // Cek apakah ada QR Code yang masih dipinjam atau bermasalah
        $qrAktif = $barang->qrCodes()->whereIn('status', ['Dipinjam', 'Rusak'])->count();
        if ($qrAktif > 0) {
            return redirect()->route('barang.index')->with('error', 'Barang tidak dapat dihapus karena ada unit yang masih dipinjam atau rusak.');
        }

        // Hapus semua QR Code terkait (dan file-nya)
        foreach ($barang->qrCodes as $qrCode) {
            if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
                Storage::disk('public')->delete($qrCode->qr_path);
            }
            $qrCode->delete();
        }

        // Hapus barang
        $barang->delete();

        return redirect()->route('barang.index')->with('success', 'Barang berhasil dihapus.');
    }

    /**
     * Download QR Code untuk satu unit barang
     */
    public function downloadQrCode($id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $user = Auth::user();
        $barang = $qrCode->barang;

        // Validasi akses
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses QR Code ini.');
            }
        }

        // Cek apakah file QR Code ada
        if (!$qrCode->qr_path || !Storage::disk('public')->exists($qrCode->qr_path)) {
            // Generate QR Code jika belum ada atau hilang
            $qr_image = QrCode::format('svg')->size(300)->generate($qrCode->no_seri_pabrik);
            $filename = 'qr_codes/' . $qrCode->no_seri_pabrik . '.svg';
            Storage::disk('public')->put($filename, $qr_image);

            // Update path di database
            $qrCode->qr_path = $filename;
            $qrCode->save();
        }

        $path = Storage::disk('public')->path($qrCode->qr_path);
        return response()->download($path, $barang->nama_barang . '_' . $qrCode->no_seri_pabrik . '.svg');
    }

    /**
     * Cetak semua QR Code untuk barang tertentu
     */
    public function printAllQrCodes($id)
    {
        $barang = Barang::with('qrCodes')->findOrFail($id);
        $user = Auth::user();

        // Validasi akses
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mencetak QR Code barang ini.');
            }
        }

        return view('qrcode.print', compact('barang'));
    }
}
