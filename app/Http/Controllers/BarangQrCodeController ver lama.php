<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Exports\Barang\BarangQrCodeExportExcel;
use App\Exports\Barang\BarangQrCodeExportPdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\KategoriBarang;
use App\Models\ArsipBarang;
use Barryvdh\DomPDF\Facade\Pdf;

class BarangQrCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $barangId = $request->get('id_barang');
        $status = $request->get('status');
        $kondisi = $request->get('kondisi');

        // Base query dengan eager loading
        $qrCodes = BarangQrCode::with(['barang', 'barang.ruangan', 'barang.kategori'])
            ->when($barangId, function ($query, $barangId) {
                return $query->where('id_barang', $barangId);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($kondisi, function ($query, $kondisi) {
                return $query->where('kondisi', $kondisi);
            });

        // Filter berdasarkan role pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $qrCodes = $qrCodes->whereHas('barang', function ($query) use ($ruanganYangDiKelola) {
                $query->whereIn('id_ruangan', $ruanganYangDiKelola);
            });
        }

        $qrCodes = $qrCodes->paginate(15);
        $barangList = Barang::orderBy('nama_barang')->get();
        $statusOptions = BarangQrCode::getValidStatus();
        $kondisiOptions = BarangQrCode::getValidKondisi();

        if ($user->role === 'Admin') {
            return view('admin.barang_qr_code.index', compact('qrCodes', 'barangList', 'statusOptions', 'kondisiOptions', 'barangId', 'status', 'kondisi'));
        } else {
            return view('operator.barang_qr_code.index', compact('qrCodes', 'barangList', 'statusOptions', 'kondisiOptions', 'barangId', 'status', 'kondisi'));
        }
    }

    /**
     * Langkah 1: Form input data barang utama
     */
    public function create()
    {
        $user = Auth::user();
        $ruanganList = [];
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();

        // Filter ruangan berdasarkan role
        if ($user->role === 'Operator') {
            $ruanganList = Ruangan::where('id_operator', $user->id)->orderBy('nama_ruangan')->get();
        } else {
            $ruanganList = Ruangan::orderBy('nama_ruangan')->get();
        }

        if ($user->role === 'Admin') {
            return view('admin.barang.create_step1', compact('ruanganList', 'kategoriList'));
        } else {
            return view('operator.barang.create_step1', compact('ruanganList', 'kategoriList'));
        }
    }

    /**
     * Proses form langkah 1 dan redirect ke langkah 2 jika perlu
     */
    public function storeStep1(Request $request)
    {
        // Validasi input form langkah 1
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:50',
            'merk_model' => 'nullable|string|max:100',
            'ukuran' => 'nullable|string|max:50',
            'bahan' => 'nullable|string|max:50',
            'tahun_pembuatan_pembelian' => 'nullable|integer',
            'jumlah_barang' => 'required|integer|min:1',
            'harga_beli' => 'nullable|numeric',
            'sumber' => 'nullable|string|max:100',
            'keadaan_barang' => 'required|string|in:Baik,Kurang Baik,Rusak Berat',
            'menggunakan_nomor_seri' => 'required|boolean',
            'id_kategori' => 'required|exists:kategori_barang,id',
            'id_ruangan' => 'required|exists:ruangan,id',
        ]);

        $user = Auth::user();

        // Validasi hak akses untuk operator
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($validated['id_ruangan'], $ruanganYangDiKelola)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menambah barang di ruangan ini.');
            }
        }

        // Simpan data barang
        $barang = Barang::create($validated);

        // Cek apakah perlu ke langkah 2 (input nomor seri manual)
        if ($barang->menggunakan_nomor_seri) {
            // Redirect ke langkah 2 untuk input nomor seri manual
            return redirect()->route('barang.create.step2', $barang->id)
                ->with('success', 'Data barang berhasil disimpan. Silahkan lanjutkan dengan input nomor seri.');
        } else {
            // Jika tidak menggunakan nomor seri manual, langsung generate QR codes otomatis
            $createdQrCodes = $barang->createQrCodes();

            // Generate file QR code untuk setiap BarangQrCode
            foreach ($createdQrCodes as $qrCode) {
                // Generate QR Code image
                $qrImage = QrCode::format('svg')
                    ->size(300)
                    ->generate($qrCode->no_seri_pabrik);

                $filename = 'qr_codes/' . $qrCode->no_seri_pabrik . '.svg';
                Storage::disk('public')->put($filename, $qrImage);

                // Update path QR code
                $qrCode->update(['qr_path' => $filename]);
            }

            return redirect()->route('barang.index')
                ->with('success', 'Barang berhasil ditambahkan dengan ' . count($createdQrCodes) . ' unit QR code.');
        }
    }

    /**
     * Langkah 2: Form input nomor seri manual
     */
    public function createStep2($barangId)
    {
        $barang = Barang::findOrFail($barangId);
        $user = Auth::user();

        // Validasi bahwa barang menggunakan nomor seri manual
        if (!$barang->menggunakan_nomor_seri) {
            return redirect()->route('barang.index')
                ->with('error', 'Barang ini tidak menggunakan nomor seri manual.');
        }

        // Validasi hak akses untuk operator
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengelola barang di ruangan ini.');
            }
        }

        if ($user->role === 'Admin') {
            return view('admin.barang.create_step2', compact('barang'));
        } else {
            return view('operator.barang.create_step2', compact('barang'));
        }
    }

    /**
     * Proses form langkah 2: Simpan nomor seri manual dan generate QR codes
     */
    public function storeStep2(Request $request, $barangId)
    {
        $barang = Barang::findOrFail($barangId);
        $user = Auth::user();

        // Validasi bahwa barang menggunakan nomor seri manual
        if (!$barang->menggunakan_nomor_seri) {
            return redirect()->route('barang.index')
                ->with('error', 'Barang ini tidak menggunakan nomor seri manual.');
        }

        // Validasi hak akses untuk operator
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengelola barang di ruangan ini.');
            }
        }

        // Validasi input nomor seri
        $validated = $request->validate([
            'nomor_seri' => 'required|array|size:' . $barang->jumlah_barang,
            'nomor_seri.*' => 'required|string|max:100|distinct|unique:barang_qr_code,no_seri_pabrik',
        ]);

        // Buat QR codes dengan nomor seri yang diinput
        $createdQrCodes = $barang->createQrCodes($validated['nomor_seri']);

        // Generate file QR code untuk setiap BarangQrCode
        foreach ($createdQrCodes as $qrCode) {
            // Generate QR Code image
            $qrImage = QrCode::format('svg')
                ->size(300)
                ->generate($qrCode->no_seri_pabrik);

            $filename = 'qr_codes/' . $qrCode->no_seri_pabrik . '.svg';
            Storage::disk('public')->put($filename, $qrImage);

            // Update path QR code
            $qrCode->update(['qr_path' => $filename]);
        }

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil ditambahkan dengan ' . count($createdQrCodes) . ' unit QR code.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $qrCode = BarangQrCode::with(['barang', 'barang.ruangan', 'barang.kategori'])->findOrFail($id);
        $user = Auth::user();

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($qrCode->barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk melihat QR Code barang ini.');
            }
        }

        if ($user->role === 'Admin') {
            return view('admin.barang_qr_code.show', compact('qrCode'));
        } else {
            return view('operator.barang_qr_code.show', compact('qrCode'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $user = Auth::user();

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $barang = Barang::findOrFail($qrCode->id_barang);

            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengedit QR Code barang ini.');
            }

            $barangList = Barang::whereIn('id_ruangan', $ruanganYangDiKelola)->orderBy('nama_barang')->get();
        } else {
            $barangList = Barang::orderBy('nama_barang')->get();
        }

        $statusOptions = BarangQrCode::getValidStatus();
        $kondisiOptions = BarangQrCode::getValidKondisi();

        if ($user->role === 'Admin') {
            return view('admin.barang_qr_code.edit', compact('qrCode', 'barangList', 'statusOptions', 'kondisiOptions'));
        } else {
            return view('operator.barang_qr_code.edit', compact('qrCode', 'barangList', 'statusOptions', 'kondisiOptions'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);

        $validated = $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'no_seri_pabrik' => 'required|string|max:100|unique:barang_qr_code,no_seri_pabrik,' . $id,
            'kondisi' => 'required|in:' . implode(',', BarangQrCode::getValidKondisi()),
            'status' => 'required|in:' . implode(',', BarangQrCode::getValidStatus()),
            'keterangan' => 'nullable|string',
        ]);

        $user = Auth::user();
        $barangBaru = Barang::findOrFail($request->id_barang);
        $barangLama = Barang::findOrFail($qrCode->id_barang);

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');

            if (
                !in_array($barangLama->id_ruangan, $ruanganYangDiKelola->toArray()) ||
                !in_array($barangBaru->id_ruangan, $ruanganYangDiKelola->toArray())
            ) {
                abort(403, 'Anda tidak memiliki izin untuk mengubah QR Code barang ini.');
            }
        }

        // Cek apakah nomor seri berubah
        if ($qrCode->no_seri_pabrik !== $validated['no_seri_pabrik']) {
            // Hapus file QR code lama
            if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
                Storage::disk('public')->delete($qrCode->qr_path);
            }

            // Generate QR code baru
            $qrImage = QrCode::format('svg')
                ->size(300)
                ->generate($validated['no_seri_pabrik']);

            $filename = 'qr_codes/' . $validated['no_seri_pabrik'] . '.svg';
            Storage::disk('public')->put($filename, $qrImage);
            $validated['qr_path'] = $filename;
        }

        $qrCode->update($validated);

        // Update jumlah barang jika pindah ke barang lain
        if ($barangLama->id !== $barangBaru->id) {
            // Sinkronkan jumlah QR code untuk kedua barang
            $barangLama->syncQrCodeCount();
            $barangBaru->syncQrCodeCount();
        }

        return redirect()->route('barang-qr-code.index')->with('success', 'QR Code barang berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $barang = Barang::findOrFail($qrCode->id_barang);
        $user = Auth::user();

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');

            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk menghapus QR Code barang ini.');
            }
        }

        // Cek apakah QR code sedang dipinjam
        if ($qrCode->status === BarangQrCode::STATUS_DIPINJAM) {
            return redirect()->back()->with('error', 'QR Code barang sedang dipinjam dan tidak dapat dihapus.');
        }

        // Hapus file QR code
        if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
            Storage::disk('public')->delete($qrCode->qr_path);
        }

        // Simpan ke arsip
        $arsipData = [
            'id_barang_qr_code' => $qrCode->id,
            'id_barang' => $qrCode->id_barang,
            'id_user' => Auth::id(),
            'alasan' => $request->input('alasan'),
            'tanggal_dihapus' => now(),
        ];

        if ($request->hasFile('foto_bukti')) {
            $fotoPath = $request->file('foto_bukti')->store('arsip_foto', 'public');
            $arsipData['foto_bukti'] = $fotoPath;
        }

        ArsipBarang::create($arsipData);

        $barang = $qrCode->barang;
        $qrCode->delete();
        $barang->syncQrCodeCount();

        return back()->with('success', 'Unit berhasil dihapus dan dicatat dalam arsip.');
    }


    /**
     * Download QR Code as PNG image
     */
    public function download(string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $user = Auth::user();

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $barang = Barang::findOrFail($qrCode->id_barang);

            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengunduh QR Code barang ini.');
            }
        }

        if (!$qrCode->qr_path || !Storage::disk('public')->exists($qrCode->qr_path)) {
            // Generate QR code jika belum ada
            $qrImage = QrCode::format('svg')
                ->size(300)
                ->generate($qrCode->getQrCodeContent());

            $filename = 'qr_codes/' . $qrCode->no_seri_pabrik . '.svg';
            Storage::disk('public')->put($filename, $qrImage);

            $qrCode->update(['qr_path' => $filename]);
        }

        return response()->download(
            storage_path('app/public/' . $qrCode->qr_path),
            $qrCode->no_seri_pabrik . '.svg'
        );
    }

    /**
     * Generate all QR codes for a barang
     */
    public function generateAllForBarang(string $barangId)
    {
        $barang = Barang::findOrFail($barangId);
        $user = Auth::user();

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');

            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk membuat QR Code barang ini.');
            }
        }

        // Hitung berapa QR code yang sudah ada
        $existingQrCount = $barang->qrCodes()->count();

        // Jika sudah memiliki cukup QR code
        if ($existingQrCount >= $barang->jumlah_barang) {
            return redirect()->back()->with('info', 'Barang sudah memiliki semua QR Code yang diperlukan.');
        }

        // Tentukan berapa QR code yang perlu dibuat
        $qrToGenerate = $barang->jumlah_barang - $existingQrCount;

        // Generate QR codes baru menggunakan method dari model Barang
        $serialNumbers = $barang->generateSerialNumbers($qrToGenerate);
        $createdQrCodes = [];

        foreach ($serialNumbers as $serialNumber) {
            // Generate QR Code image
            $qrImage = QrCode::format('svg')
                ->size(300)
                ->generate($serialNumber);

            $filename = 'qr_codes/' . $serialNumber . '.svg';
            Storage::disk('public')->put($filename, $qrImage);

            // Simpan ke database
            $qrCode = BarangQrCode::create([
                'id_barang' => $barang->id,
                'no_seri_pabrik' => $serialNumber,
                'kondisi' => $barang->keadaan_barang,
                'status' => BarangQrCode::STATUS_TERSEDIA,
                'qr_path' => $filename,
                'keterangan' => null,
            ]);

            $createdQrCodes[] = $qrCode;
        }

        // Sync jumlah barang
        $barang->syncQrCodeCount();

        return redirect()->back()->with('success', "Berhasil membuat $qrToGenerate QR Code untuk barang ini.");
    }

    /**
     * Update kondisi barang QR code
     */
    public function updateKondisi(Request $request, string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $user = Auth::user();

        $validated = $request->validate([
            'kondisi' => 'required|in:' . implode(',', BarangQrCode::getValidKondisi()),
            'keterangan' => 'nullable|string',
        ]);

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $barang = Barang::findOrFail($qrCode->id_barang);

            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengubah kondisi QR Code barang ini.');
            }
        }

        $qrCode->update([
            'kondisi' => $validated['kondisi'],
            'keterangan' => $validated['keterangan'] ?? $qrCode->keterangan,
        ]);

        return redirect()->back()->with('success', 'Kondisi QR Code barang berhasil diperbarui.');
    }

    /**
     * Update status barang QR code
     */
    public function updateStatus(Request $request, string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $user = Auth::user();

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', BarangQrCode::getValidStatus()),
            'keterangan' => 'nullable|string',
        ]);

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $barang = Barang::findOrFail($qrCode->id_barang);

            if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengubah status QR Code barang ini.');
            }
        }

        $qrCode->update([
            'status' => $validated['status'],
            'keterangan' => $validated['keterangan'] ?? $qrCode->keterangan,
        ]);

        return redirect()->back()->with('success', 'Status QR Code barang berhasil diperbarui.');
    }

    /**
     * Print multiple QR codes
     */
    public function printMultiple(Request $request)
    {
        $qrCodeIds = $request->get('qr_code_ids', []);

        if (empty($qrCodeIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu QR Code untuk dicetak.');
        }

        $qrCodes = BarangQrCode::with('barang')->whereIn('id', $qrCodeIds)->get();
        $user = Auth::user();

        // Cek izin pengguna
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');

            foreach ($qrCodes as $qrCode) {
                $barang = $qrCode->barang;
                if (!in_array($barang->id_ruangan, $ruanganYangDiKelola->toArray())) {
                    abort(403, 'Anda tidak memiliki izin untuk mencetak beberapa QR Code barang.');
                }
            }
        }

        // Generate QR code yang belum ada
        foreach ($qrCodes as $qrCode) {
            if (!$qrCode->qr_path || !Storage::disk('public')->exists($qrCode->qr_path)) {
                // Generate QR code
                $qrImage = QrCode::format('svg')
                    ->size(300)
                    ->generate($qrCode->getQrCodeContent());

                $filename = 'qr_codes/' . $qrCode->no_seri_pabrik . '.svg';
                Storage::disk('public')->put($filename, $qrImage);

                $qrCode->update(['qr_path' => $filename]);
            }
        }

        // Return view untuk mencetak multiple QR code
        return view('qr_code.print_multiple', compact('qrCodes'));
    }

    public function exportExcel(Request $request)
    {
        $filters = [
            'id_ruangan' => $request->get('id_ruangan'),
            'id_kategori' => $request->get('id_kategori'),
            'status' => $request->get('status'),
            'kondisi' => $request->get('kondisi'),
            'tahun' => $request->get('tahun'),
        ];

        return Excel::download(new BarangQrCodeExportExcel($filters), 'barang_qr_codes.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $filters = [
            'ruangan' => $request->filled('id_ruangan') ? Ruangan::find($request->id_ruangan) : null,
            'kategori' => $request->filled('id_kategori') ? KategoriBarang::find($request->id_kategori) : null,
            'status' => $request->status,
            'kondisi' => $request->kondisi,
            'tahun' => $request->tahun,
        ];

        $qrCodes = (new BarangQrCodeExportPdf($request))->getFilteredData(); // reuse filter
        $groupByRuangan = $request->boolean('pisah_per_ruangan');

        return PDF::loadView('exports.barang_qrcode_pdf', compact('qrCodes', 'groupByRuangan', 'filters'))
            ->setPaper([0, 0, 612, 792], 'landscape')
            ->download('barang_qr_codes.pdf');
    }
}
