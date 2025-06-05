<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller Peminjaman terpadu yang menangani semua alur kerja
 * untuk Guru, Operator, dan Admin, dengan hak akses yang dikontrol oleh PeminjamanPolicy.
 */
class PeminjamanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Menampilkan daftar semua peminjaman.
     * Data yang ditampilkan akan difilter berdasarkan peran pengguna.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Peminjaman::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $query = Peminjaman::with('guru', 'operatorProses', 'detailPeminjaman')->latest();

        // Jika Guru, hanya tampilkan peminjaman miliknya.
        if ($user->hasRole('Guru')) {
            $query->where('id_guru', $user->id);
        }

        // TODO: Tambahkan filter untuk Operator jika diperlukan,
        // misalnya hanya menampilkan peminjaman yang itemnya ada di ruangannya.
        // Untuk saat ini, Operator & Admin melihat semua, Policy akan membatasi aksi mereka.

        $peminjamans = $query->paginate(15);
        $statusOptions = Peminjaman::getValidStatuses();

        // View bisa dibedakan berdasarkan role jika diperlukan
        // if ($user->hasRole('Guru')) return view('guru.peminjaman.index', ...);
        return view('peminjaman.index', compact('peminjamans', 'statusOptions'));
    }

    /**
     * Menampilkan detail satu transaksi peminjaman.
     */
    public function show(Peminjaman $peminjaman): View
    {
        $this->authorize('view', $peminjaman);

        $peminjaman->load(
            'detailPeminjaman.barangQrCode.barang.kategori',
            'detailPeminjaman.barangQrCode.ruangan',
            'guru',
            'operatorProses',
            'ruanganTujuanPeminjaman'
        );

        return view('peminjaman.show', compact('peminjaman'));
    }


    //==============================================
    //           METODE UNTUK PERAN GURU
    //==============================================

    /**
     * Menampilkan form untuk membuat pengajuan peminjaman baru (untuk Guru).
     */
    public function create(): View
    {
        $this->authorize('create', Peminjaman::class);
        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();
        return view('peminjaman.create', compact('ruanganList'));
    }

    /**
     * Menyimpan pengajuan peminjaman baru dari Guru.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Peminjaman::class);
        $validated = $request->validate([
            'tujuan_peminjaman' => 'required|string|max:1000',
            'tanggal_rencana_pinjam' => 'required|date|after_or_equal:today',
            'tanggal_rencana_kembali' => 'required|date|after_or_equal:tanggal_rencana_pinjam',
            'id_ruangan_tujuan_peminjaman' => 'nullable|exists:ruangans,id',
            'selected_items' => 'required|array|min:1',
            'selected_items.*' => 'required|integer|exists:barang_qr_codes,id',
        ]);

        DB::beginTransaction();
        try {
            $selectedUnits = BarangQrCode::whereIn('id', $validated['selected_items'])->get();
            foreach ($selectedUnits as $unit) {
                if ($unit->status !== BarangQrCode::STATUS_TERSEDIA) { // [cite: 745]
                    throw new \Exception("Unit barang '{$unit->barang->nama_barang}' dengan kode '{$unit->kode_inventaris_sekolah}' tidak tersedia.");
                }
            }

            $peminjaman = Peminjaman::create([
                'id_guru' => Auth::id(),
                'tujuan_peminjaman' => $validated['tujuan_peminjaman'],
                'status' => Peminjaman::STATUS_MENUNGGU_PERSETUJUAN, // [cite: 305]
                'tanggal_rencana_pinjam' => $validated['tanggal_rencana_pinjam'],
                'tanggal_rencana_kembali' => $validated['tanggal_rencana_kembali'],
                'tanggal_harus_kembali' => $validated['tanggal_rencana_kembali'],
                'id_ruangan_tujuan_peminjaman' => $validated['id_ruangan_tujuan_peminjaman'],
            ]);

            foreach ($validated['selected_items'] as $barangQrCodeId) {
                $unit = $selectedUnits->find($barangQrCodeId);
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman->id,
                    'id_barang_qr_code' => $barangQrCodeId,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN, // [cite: 597]
                    'kondisi_sebelum' => $unit->kondisi,
                ]);
            }
            DB::commit();
            return redirect()->route('peminjaman.index')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengajukan peminjaman: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Membatalkan pengajuan peminjaman (oleh Guru).
     */
    public function destroy(Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('cancel', $peminjaman);

        $peminjaman->status = Peminjaman::STATUS_DIBATALKAN; // [cite: 307]
        $peminjaman->save();

        return redirect()->route('peminjaman.index')->with('success', 'Pengajuan peminjaman berhasil dibatalkan.');
    }


    //=====================================================
    //        METODE UNTUK PERAN OPERATOR & ADMIN
    //=====================================================

    /**
     * Menyetujui pengajuan peminjaman.
     */
    public function approve(Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('manage', $peminjaman);

        $peminjaman->status = Peminjaman::STATUS_DISETUJUI; // [cite: 306]
        $peminjaman->disetujui_oleh = Auth::id();
        $peminjaman->tanggal_disetujui = now();
        $peminjaman->save();

        $peminjaman->detailPeminjaman()->update(['status_unit' => DetailPeminjaman::STATUS_ITEM_DISETUJUI]); // [cite: 598]

        return back()->with('success', 'Peminjaman berhasil disetujui.');
    }

    /**
     * Menolak pengajuan peminjaman.
     */
    public function reject(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->authorize('manage', $peminjaman);
        $request->validate(['catatan_operator' => 'nullable|string|max:255']);

        $peminjaman->status = Peminjaman::STATUS_DITOLAK; // [cite: 306]
        $peminjaman->ditolak_oleh = Auth::id();
        $peminjaman->tanggal_ditolak = now();
        $peminjaman->catatan_operator = $request->catatan_operator;
        $peminjaman->save();

        return back()->with('success', 'Peminjaman berhasil ditolak.');
    }

    /**
     * Mengonfirmasi pengambilan unit barang oleh peminjam.
     */
    public function konfirmasiPengambilan(DetailPeminjaman $detail): RedirectResponse
    {
        $this->authorize('processHandover', $detail);

        try {
            // Logika kompleks untuk mengubah status barang dan mencatat histori sudah ada di dalam model
            $detail->konfirmasiPengambilan(Auth::id()); // [cite: 632]
            return back()->with('success', 'Barang telah dikonfirmasi diambil.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Memverifikasi pengembalian unit barang dari peminjam.
     */
    public function verifikasiPengembalian(Request $request, DetailPeminjaman $detail): RedirectResponse
    {
        $this->authorize('processReturn', $detail);
        $request->validate(['kondisi_aktual' => 'required|string', 'catatan' => 'nullable|string']);

        try {
            // Logika kompleks untuk mengubah status barang, membuat entri pemeliharaan/arsip jika perlu,
            // dan mencatat histori sudah ada di dalam model.
            $detail->verifikasiPengembalian(Auth::id(), $request->kondisi_aktual, $request->catatan); // [cite: 649]
            return back()->with('success', 'Pengembalian barang telah diverifikasi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    //=====================================================
    //            FUNGSI BANTU (MISALNYA AJAX)
    //=====================================================

    /**
     * Mengambil daftar barang (unit) yang tersedia untuk form peminjaman via AJAX.
     */
    public function getAvailableUnits(): JsonResponse
    {
        // Fungsi ini bisa diperluas dengan parameter search atau filter ruangan
        $units = BarangQrCode::where('status', BarangQrCode::STATUS_TERSEDIA) // [cite: 745]
            ->whereNull('deleted_at')
            ->with('barang:id,nama_barang,merk_model', 'ruangan:id,nama_ruangan')
            ->get()
            ->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'text' => "{$unit->kode_inventaris_sekolah} - {$unit->barang->nama_barang} (Lokasi: {$unit->ruangan->nama_ruangan})"
                ];
            });

        return response()->json($units);
    }
}
