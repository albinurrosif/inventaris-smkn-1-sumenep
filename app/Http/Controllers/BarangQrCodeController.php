<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
        $ruanganId = $request->get('id_ruangan');
        $status = $request->get('status');
        $keadaan_barang = $request->get('keadaan_barang');

        // Base query dengan eager loading - UNIT BASED
        $qrCodes = BarangQrCode::with(['barang', 'barang.kategori', 'ruangan'])
            ->when($barangId, function ($query, $barangId) {
                return $query->where('id_barang', $barangId);
            })
            ->when($ruanganId, function ($query, $ruanganId) {
                return $query->where('id_ruangan', $ruanganId);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($keadaan_barang, function ($query, $keadaan_barang) {
                return $query->where('keadaan_barang', $keadaan_barang);
            });

        // Filter berdasarkan role pengguna - UNIT BASED
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            $qrCodes = $qrCodes->whereIn('id_ruangan', $ruanganYangDiKelola);
        }

        $qrCodes = $qrCodes->paginate(15);

        // Data untuk filter
        $barangList = Barang::orderBy('nama_barang')->get();
        $ruanganList = $user->role === 'Operator'
            ? Ruangan::where('id_operator', $user->id)->orderBy('nama_ruangan')->get()
            : Ruangan::orderBy('nama_ruangan')->get();
        $statusOptions = BarangQrCode::getValidStatus();
        $keadaan_barangOptions = BarangQrCode::getValidKeadaanBarang();

        if ($user->role === 'Admin') {
            return view('admin.barang_qr_code.index', compact(
                'qrCodes',
                'barangList',
                'ruanganList',
                'statusOptions',
                'keadaan_barangOptions',
                'barangId',
                'ruanganId',
                'status',
                'keadaan_barang'
            ));
        } else {
            return view('operator.barang_qr_code.index', compact(
                'qrCodes',
                'barangList',
                'ruanganList',
                'statusOptions',
                'keadaan_barangOptions',
                'barangId',
                'ruanganId',
                'status',
                'keadaan_barang'
            ));
        }
    }

    /**
     * Langkah 1: Form input data barang utama (AGREGAT)
     */
    public function create()
    {
        $user = Auth::user();
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $ruanganList = $user->role === 'Operator'
            ? Ruangan::where('id_operator', $user->id)->orderBy('nama_ruangan')->get()
            : Ruangan::orderBy('nama_ruangan')->get();

        if ($user->role === 'Admin') {
            return view('admin.barang.create_step1', compact('kategoriList', 'ruanganList'));
        } else {
            return view('operator.barang.create_step1', compact('kategoriList', 'ruanganList'));
        }
    }


    /**
     * Proses form langkah 1 dan redirect ke langkah 2 untuk input unit
     */
    public function storeStep1(Request $request)
    {
        // Validasi input form langkah 1 - DATA AGREGAT
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

        // Create barang without id_ruangan
        $barangData = $request->except('id_ruangan');
        $barang = Barang::create($barangData);

        // Store ruangan in session for Step 2
        session([
            'incomplete_barang_id' => $barang->id,
            'target_ruangan_id' => $validated['id_ruangan']
        ]);

        // Selalu redirect ke langkah 2 untuk input unit dengan ruangan
        return redirect()->route('barang.create.step2', $barang->id)
            ->with('success', 'Data barang berhasil disimpan. Silahkan lanjutkan dengan pengaturan unit.');
    }

    /**
     * Langkah 2: Form input unit dengan ruangan dan nomor seri
     */
    public function createStep2($barangId)
    {
        $barang = Barang::findOrFail($barangId);
        $user = Auth::user();
        $ruangan = Ruangan::find(session('target_ruangan_id'));

        // Cek apakah sudah ada unit yang dibuat
        $existingUnits = $barang->qrCodes()->count();
        if ($existingUnits >= $barang->jumlah_barang) {
            return redirect()->route('barang.index')
                ->with('info', 'Barang ini sudah memiliki semua unit yang diperlukan.');
        }

        // Ruangan berdasarkan role
        $ruanganList = $user->role === 'Operator'
            ? Ruangan::where('id_operator', $user->id)->orderBy('nama_ruangan')->get()
            : Ruangan::orderBy('nama_ruangan')->get();

        if ($user->role === 'Admin') {
            return view('admin.barang.create_step2', compact('barang', 'ruanganList'));
        } else {
            return view('operator.barang.create_step2', compact('barang', 'ruanganList'));
        }
    }

    /**
     * Proses form langkah 2: Simpan unit dengan ruangan dan generate QR codes
     */
    public function storeStep2(Request $request, $barangId)
    {
        $barang = Barang::findOrFail($barangId);
        $user = Auth::user();

        // Validasi input unit
        $validated = $request->validate([
            'units' => 'required|array|size:' . $barang->jumlah_barang,
            'units.*.id_ruangan' => 'required|exists:ruangan,id',
            'units.*.no_seri_pabrik' => $barang->menggunakan_nomor_seri
                ? 'required|string|max:100|distinct|unique:barang_qr_code,no_seri_pabrik'
                : 'nullable',
        ]);

        // Validasi hak akses untuk operator
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            foreach ($validated['units'] as $unit) {
                if (!in_array($unit['id_ruangan'], $ruanganYangDiKelola)) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menambah unit di salah satu ruangan yang dipilih.');
                }
            }
        }

        DB::transaction(function () use ($barang, $validated) {
            $createdQrCodes = [];

            foreach ($validated['units'] as $unitData) {
                // Generate nomor seri jika tidak menggunakan input manual
                $noSeri = $barang->menggunakan_nomor_seri
                    ? $unitData['no_seri_pabrik']
                    : $barang->generateUniqueSerialNumber();

                // Buat unit QR code
                $qrCode = BarangQrCode::create([
                    'id_barang' => $barang->id,
                    'id_ruangan' => $unitData['id_ruangan'],
                    'no_seri_pabrik' => $noSeri,
                    'keadaan_barang' => $barang->keadaan_barang,
                    'status' => BarangQrCode::STATUS_TERSEDIA,
                ]);

                // Generate QR Code image
                $qrImage = QrCode::format('svg')
                    ->size(300)
                    ->generate($noSeri);

                $filename = 'qr_codes/' . $noSeri . '.svg';
                Storage::disk('public')->put($filename, $qrImage);

                // Update path QR code
                $qrCode->update(['qr_path' => $filename]);
                $createdQrCodes[] = $qrCode;
            }

            // Sync jumlah barang otomatis
            $barang->syncQrCodeCount();
        });

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil ditambahkan dengan ' . count($validated['units']) . ' unit QR code.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $qrCode = BarangQrCode::with(['barang', 'barang.kategori', 'ruangan'])->findOrFail($id);
        $user = Auth::user();

        // Cek izin pengguna - UNIT BASED
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($qrCode->id_ruangan, $ruanganYangDiKelola->toArray())) {
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
        $qrCode = BarangQrCode::with(['barang', 'ruangan'])->findOrFail($id);
        $user = Auth::user();

        // Cek izin pengguna - UNIT BASED
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($qrCode->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengedit QR Code barang ini.');
            }
            $ruanganList = Ruangan::whereIn('id', $ruanganYangDiKelola)->orderBy('nama_ruangan')->get();
        } else {
            $ruanganList = Ruangan::orderBy('nama_ruangan')->get();
        }

        $statusOptions = BarangQrCode::getValidStatus();
        $keadaan_barangOptions = BarangQrCode::getValidKeadaanBarang();

        if ($user->role === 'Admin') {
            return view('admin.barang_qr_code.edit', compact('qrCode', 'ruanganList', 'statusOptions', 'keadaan_barangOptions'));
        } else {
            return view('operator.barang_qr_code.edit', compact('qrCode', 'ruanganList', 'statusOptions', 'keadaan_barangOptions'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);

        $validated = $request->validate([
            'id_ruangan' => 'required|exists:ruangan,id',
            'no_seri_pabrik' => 'required|string|max:100|unique:barang_qr_code,no_seri_pabrik,' . $id,
            'keadaan_barang' => 'required|in:' . implode(',', BarangQrCode::getValidKeadaanBarang()),
            'status' => 'required|in:' . implode(',', BarangQrCode::getValidStatus()),
            'keterangan' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Cek izin pengguna - UNIT BASED
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

            if (
                !in_array($qrCode->id_ruangan, $ruanganYangDiKelola) ||
                !in_array($validated['id_ruangan'], $ruanganYangDiKelola)
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

        return redirect()->route('barang-qr-code.index')->with('success', 'QR Code barang berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage (SOFT DELETE dengan ARSIP)
     */
    public function destroy(Request $request, $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $barang = $qrCode->barang;
        $user = Auth::user();

        // Validasi hak akses - UNIT BASED
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($qrCode->id_ruangan, $ruanganYangDiKelola)) {
                abort(403, 'Anda tidak memiliki izin untuk menghapus QR Code barang ini.');
            }
        }

        // Validasi: tidak sedang dipinjam
        if ($qrCode->status === BarangQrCode::STATUS_DIPINJAM) {
            return redirect()->back()->with('error', 'QR Code barang sedang dipinjam dan tidak dapat dihapus.');
        }

        // Validasi input
        $validated = $request->validate([
            'alasan' => 'required|string|max:500',
            'berita_acara' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'foto_bukti' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        DB::transaction(function () use ($qrCode, $barang, $validated, $user) {
            // Proses upload file
            $beritaAcaraPath = null;
            $fotoBuktiPath = null;

            if (request()->hasFile('berita_acara')) {
                $beritaAcaraPath = request()->file('berita_acara')->store('arsip/berita_acara', 'public');
            }

            if (request()->hasFile('foto_bukti')) {
                $fotoBuktiPath = request()->file('foto_bukti')->store('arsip/foto_bukti', 'public');
            }

            // Update data unit sebelum soft delete
            $qrCode->update([
                'alasan_penghapusan' => $validated['alasan'],
                'berita_acara' => $beritaAcaraPath,
                'foto_pendukung' => $fotoBuktiPath,
                'deleted_by' => $user->id,
            ]);

            // Arsipkan unit
            ArsipBarang::create([
                'id_barang_qr_code' => $qrCode->id,
                'id_barang' => $qrCode->id_barang,
                'id_user' => $user->id,
                'alasan' => $validated['alasan'],
                'tanggal_dihapus' => now(),
                'berita_acara_path' => $beritaAcaraPath,
                'foto_bukti_path' => $fotoBuktiPath,
            ]);

            // Soft delete unit
            $qrCode->delete();

            // Sync jumlah barang
            $barang->refresh();
            $remainingUnits = $barang->qrCodes()->whereNull('deleted_at')->count();
            $barang->update(['jumlah_barang' => $remainingUnits]);

            // Jika semua unit dihapus, soft delete agregat juga
            if ($remainingUnits === 0) {
                // Arsipkan agregat
                ArsipBarang::create([
                    'id_barang' => $barang->id,
                    'id_barang_qr_code' => null,
                    'id_user' => $user->id,
                    'alasan' => 'Otomatis: Semua unit telah dihapus',
                    'tanggal_dihapus' => now(),
                ]);

                // Soft delete agregat
                $barang->update(['deleted_by' => $user->id]);
                $barang->delete();
            }
        });

        return redirect()->back()->with('success', 'Unit berhasil dihapus dan diarsipkan.');
    }

    /**
     * Restore unit yang sudah dihapus (untuk Admin)
     */
    public function restore($id)
    {
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Hanya Admin yang dapat melakukan restore.');
        }

        $qrCode = BarangQrCode::onlyTrashed()->findOrFail($id);
        $barang = $qrCode->barang()->withTrashed()->first();

        DB::transaction(function () use ($qrCode, $barang) {
            // Restore unit
            $qrCode->restore();
            $qrCode->update([
                'alasan_penghapusan' => null,
                'berita_acara' => null,
                'foto_pendukung' => null,
                'deleted_by' => null,
            ]);

            // Restore agregat jika terhapus
            if ($barang->trashed()) {
                $barang->restore();
                $barang->update(['deleted_by' => null]);
            }

            // Sync jumlah barang
            $barang->syncQrCodeCount();

            // Hapus dari arsip
            ArsipBarang::where('id_barang_qr_code', $qrCode->id)->delete();
        });

        return redirect()->back()->with('success', 'Unit berhasil dipulihkan.');
    }

    /**
     * Mutasi unit ke ruangan lain
     */
    public function mutasi(Request $request, $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $user = Auth::user();

        $validated = $request->validate([
            'id_ruangan_tujuan' => 'required|exists:ruangan,id|different:id_ruangan_asal',
            'keterangan_mutasi' => 'nullable|string|max:500',
        ]);

        // Validasi hak akses
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (
                !in_array($qrCode->id_ruangan, $ruanganYangDiKelola) ||
                !in_array($validated['id_ruangan_tujuan'], $ruanganYangDiKelola)
            ) {
                abort(403, 'Anda tidak memiliki izin untuk memutasi unit ini.');
            }
        }

        // Validasi status unit
        if ($qrCode->status === BarangQrCode::STATUS_DIPINJAM) {
            return redirect()->back()->with('error', 'Unit sedang dipinjam dan tidak dapat dimutasi.');
        }

        $ruanganAsal = $qrCode->ruangan;
        $ruanganTujuan = Ruangan::findOrFail($validated['id_ruangan_tujuan']);

        // Update ruangan unit
        $qrCode->update([
            'id_ruangan' => $validated['id_ruangan_tujuan'],
            'keterangan' => $validated['keterangan_mutasi'] ?? $qrCode->keterangan,
        ]);

        // Log mutasi bisa ditambahkan di sini jika diperlukan

        return redirect()->back()->with(
            'success',
            "Unit berhasil dimutasi dari {$ruanganAsal->nama_ruangan} ke {$ruanganTujuan->nama_ruangan}."
        );
    }

    /**
     * Download QR Code as SVG image
     */
    public function download(string $id)
    {
        $qrCode = BarangQrCode::findOrFail($id);
        $user = Auth::user();

        // Cek izin pengguna - UNIT BASED
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id');
            if (!in_array($qrCode->id_ruangan, $ruanganYangDiKelola->toArray())) {
                abort(403, 'Anda tidak memiliki izin untuk mengunduh QR Code barang ini.');
            }
        }

        if (!$qrCode->qr_path || !Storage::disk('public')->exists($qrCode->qr_path)) {
            // Generate QR code jika belum ada
            $qrImage = QrCode::format('svg')
                ->size(300)
                ->generate($qrCode->no_seri_pabrik);

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
     * Print multiple QR codes
     */
    public function printMultiple(Request $request)
    {
        $qrCodeIds = $request->get('qr_code_ids', []);

        if (empty($qrCodeIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu QR Code untuk dicetak.');
        }

        $qrCodes = BarangQrCode::with(['barang', 'ruangan'])->whereIn('id', $qrCodeIds)->get();
        $user = Auth::user();

        // Cek izin pengguna - UNIT BASED
        if ($user->role === 'Operator') {
            $ruanganYangDiKelola = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

            foreach ($qrCodes as $qrCode) {
                if (!in_array($qrCode->id_ruangan, $ruanganYangDiKelola)) {
                    abort(403, 'Anda tidak memiliki izin untuk mencetak beberapa QR Code barang.');
                }
            }
        }

        // Generate QR code yang belum ada
        foreach ($qrCodes as $qrCode) {
            if (!$qrCode->qr_path || !Storage::disk('public')->exists($qrCode->qr_path)) {
                $qrImage = QrCode::format('svg')
                    ->size(300)
                    ->generate($qrCode->no_seri_pabrik);

                $filename = 'qr_codes/' . $qrCode->no_seri_pabrik . '.svg';
                Storage::disk('public')->put($filename, $qrImage);

                $qrCode->update(['qr_path' => $filename]);
            }
        }

        return view('qr_code.print_multiple', compact('qrCodes'));
    }

    /**
     * Export Excel dengan filter unit-based
     */
    public function exportExcel(Request $request)
    {
        $filters = [
            'id_ruangan' => $request->get('id_ruangan'),
            'id_kategori' => $request->get('id_kategori'),
            'status' => $request->get('status'),
            'keadaan_barang' => $request->get('keadaan_barang'),
            'tahun' => $request->get('tahun'),
        ];

        return Excel::download(new BarangQrCodeExportExcel($filters), 'barang_qr_codes.xlsx');
    }

    /**
     * Export PDF dengan filter unit-based
     */
    public function exportPdf(Request $request)
    {
        $filters = [
            'ruangan' => $request->filled('id_ruangan') ? Ruangan::find($request->id_ruangan) : null,
            'kategori' => $request->filled('id_kategori') ? KategoriBarang::find($request->id_kategori) : null,
            'status' => $request->status,
            'keadaan_barang' => $request->keadaan_barang,
            'tahun' => $request->tahun,
        ];

        $qrCodes = (new BarangQrCodeExportPdf($request))->getFilteredData();
        $groupByRuangan = $request->boolean('pisah_per_ruangan');

        return PDF::loadView('exports.barang_qrcode_pdf', compact('qrCodes', 'groupByRuangan', 'filters'))
            ->setPaper([0, 0, 612, 792], 'landscape')
            ->download('barang_qr_codes.pdf');
    }
}
