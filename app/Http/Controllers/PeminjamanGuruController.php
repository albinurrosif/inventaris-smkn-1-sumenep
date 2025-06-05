<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Models\User;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PeminjamanGuruController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        // Middleware untuk memastikan hanya guru yang bisa akses
        // $this->middleware('isGuru');
    }

    /**
     * Menampilkan daftar peminjaman yang dibuat oleh guru yang sedang login.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAnyGuru', Peminjaman::class);

        $user = Auth::user();
        /** @var \App\Models\User $user */

        $query = Peminjaman::with([
            'detailPeminjaman.barangQrCode.barang.kategori',
            'detailPeminjaman.barangQrCode.ruangan',
            'operatorProses'
        ])
            ->where('id_guru', $user->id)
            ->orderBy('tanggal_pengajuan', 'desc');

        // Perbaiki nama kolom status
        if ($request->filled('status_peminjaman')) {
            $query->where('status', $request->status_peminjaman);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tujuan_peminjaman', 'like', "%{$searchTerm}%")
                    ->orWhereHas('detailPeminjaman.barangQrCode.barang', function ($subq) use ($searchTerm) {
                        $subq->where('nama_barang', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('detailPeminjaman.barangQrCode', function ($subq) use ($searchTerm) {
                        $subq->where('kode_inventaris_sekolah', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $peminjamans = $query->paginate(10)->withQueryString();
        $statusOptions = Peminjaman::getPossibleStatuses();

        return view('guru.peminjaman.index', compact('peminjamans', 'statusOptions'));
    }

    /**
     * Menampilkan form untuk membuat pengajuan peminjaman baru.
     */
    public function create(): View
    {
        $this->authorize('create', Peminjaman::class);

        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();
        return view('guru.peminjaman.create', compact('ruanganList'));
    }

    /**
     * Menyimpan pengajuan peminjaman baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Peminjaman::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $validated = $request->validate([
            'tujuan_peminjaman' => 'required|string|max:1000',
            'tanggal_rencana_pinjam' => 'required|date|after_or_equal:today',
            'tanggal_rencana_kembali' => 'required|date|after_or_equal:tanggal_rencana_pinjam',
            'id_ruangan_tujuan_peminjaman' => 'required|exists:ruangans,id',
            'catatan_peminjam' => 'nullable|string|max:500',
            'selected_items' => 'required|array|min:1',
            'selected_items.*' => 'required|integer|exists:barang_qr_codes,id',
        ], [
            'tujuan_peminjaman.required' => 'Tujuan peminjaman harus diisi.',
            'tanggal_rencana_pinjam.required' => 'Tanggal rencana pinjam harus diisi.',
            'tanggal_rencana_pinjam.after_or_equal' => 'Tanggal rencana pinjam tidak boleh sebelum hari ini.',
            'tanggal_rencana_kembali.required' => 'Tanggal rencana kembali harus diisi.',
            'tanggal_rencana_kembali.after_or_equal' => 'Tanggal rencana kembali harus setelah atau sama dengan tanggal pinjam.',
            'id_ruangan_tujuan_peminjaman.required' => 'Ruangan tujuan peminjaman harus dipilih.',
            'selected_items.required' => 'Minimal satu unit barang harus dipilih untuk dipinjam.',
            'selected_items.*.exists' => 'Salah satu unit barang yang dipilih tidak valid.',
        ]);

        try {
            DB::beginTransaction();

            // Cek ketersediaan semua unit yang dipilih
            $selectedUnits = BarangQrCode::whereIn('id', $validated['selected_items'])->get();
            foreach ($selectedUnits as $unit) {
                if ($unit->status !== BarangQrCode::STATUS_TERSEDIA) {
                    throw new \Exception("Unit barang '{$unit->kode_inventaris_sekolah} ({$unit->barang->nama_barang})' tidak tersedia saat ini (Status: {$unit->status}).");
                }
            }

            $peminjaman = Peminjaman::create([
                'id_guru' => $user->id,
                'tanggal_pengajuan' => now(),
                'tujuan_peminjaman' => $validated['tujuan_peminjaman'],
                'status' => Peminjaman::STATUS_DIAJUKAN, // Gunakan kolom status yang benar
                'catatan_peminjam' => $validated['catatan_peminjam'],
                'tanggal_rencana_pinjam' => $validated['tanggal_rencana_pinjam'],
                'tanggal_rencana_kembali' => $validated['tanggal_rencana_kembali'],
                'id_ruangan_tujuan_peminjaman' => $validated['id_ruangan_tujuan_peminjaman'],
            ]);

            foreach ($validated['selected_items'] as $barangQrCodeId) {
                $unit = $selectedUnits->find($barangQrCodeId);
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman->id,
                    'id_barang_qr_code' => $barangQrCodeId,
                    'status_item' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                    'kondisi_saat_pinjam' => $unit->kondisi,
                ]);
            }

            // Log aktivitas
            LogAktivitas::create([
                'user_id' => $user->id,
                'aktivitas' => 'Mengajukan peminjaman baru',
                'deskripsi' => "Peminjaman ID {$peminjaman->id} - {$peminjaman->tujuan_peminjaman}",
                'created_at' => now()
            ]);

            DB::commit();
            return redirect()->route('guru.peminjaman.index')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengajukan peminjaman oleh guru: ' . $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . $e->getFile());
            return redirect()->back()->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan detail peminjaman.
     */
    public function show(Peminjaman $peminjaman): View
    {
        $this->authorize('view', $peminjaman);

        $peminjaman->load([
            'detailPeminjaman.barangQrCode.barang.kategori',
            'detailPeminjaman.barangQrCode.ruangan',
            'detailPeminjaman.operatorSetuju',
            'detailPeminjaman.operatorVerifikasiKembali',
            'operatorProses',
            'guru',
            'ruanganTujuanPeminjaman' // Tambahan relasi yang diperlukan
        ]);

        return view('guru.peminjaman.show', compact('peminjaman'));
    }

    /**
     * Membatalkan pengajuan peminjaman (oleh Guru, jika status masih 'Diajukan').
     */
    // public function destroy(Peminjaman $peminjaman, Request $request): RedirectResponse
    // {
    //     $this->authorize('delete', $peminjaman);

    //     // Perbaiki pengecekan status
    //     if ($peminjaman->status !== Peminjaman::STATUS_DIAJUKAN) {
    //         return redirect()->back()->with('error', 'Peminjaman tidak dapat dibatalkan karena sudah diproses atau statusnya bukan diajukan.');
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $peminjaman->detailPeminjaman()->update(['status_item' => DetailPeminjaman::STATUS_ITEM_DIBATALKAN]);
    //         $peminjaman->status = Peminjaman::STATUS_DIBATALKAN; // Gunakan kolom status yang benar
    //         $peminjaman->save();

    //         // Log aktivitas
    //         LogAktivitas::create([
    //             'user_id' => Auth::id(),
    //             'aktivitas' => 'Membatalkan peminjaman',
    //             'deskripsi' => "Peminjaman ID {$peminjaman->id} dibatalkan oleh peminjam",
    //             'created_at' => now()
    //         ]);

    //         DB::commit();
    //         return redirect()->route('guru.peminjaman.index')->with('success', 'Pengajuan peminjaman berhasil dibatalkan.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Gagal membatalkan peminjaman: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Gagal membatalkan peminjaman: ' . $e->getMessage());
    //     }
    // }

    /**
     * Menampilkan daftar peminjaman yang sedang berlangsung untuk guru.
     */
    public function peminjamanBerlangsung(): View
    {
        $this->authorize('viewAnyGuru', Peminjaman::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $peminjamanBerlangsung = Peminjaman::where('id_guru', $user->id)
            ->where('status', Peminjaman::STATUS_SEDANG_DIPINJAM) // Perbaiki nama kolom
            ->with(['detailPeminjaman.barangQrCode.barang', 'ruanganTujuanPeminjaman'])
            ->orderBy('tanggal_rencana_kembali', 'asc')
            ->paginate(10);

        return view('guru.peminjaman.sedang-berlangsung', compact('peminjamanBerlangsung'));
    }

    /**
     * Guru mengajukan pengembalian untuk semua item dalam satu peminjaman.
     */
    // public function ajukanPengembalian(Request $request, Peminjaman $peminjaman): RedirectResponse
    // {
    //     $this->authorize('ajukanPengembalian', $peminjaman);

    //     if ($peminjaman->status !== Peminjaman::STATUS_SEDANG_DIPINJAM) {
    //         return redirect()->back()->with('error', 'Pengembalian hanya dapat diajukan untuk peminjaman yang sedang berlangsung.');
    //     }

    //     $validated = $request->validate([
    //         'catatan_pengembalian_guru' => 'nullable|string|max:500',
    //         'kondisi_item_kembali.*' => ['required', Rule::in(BarangQrCode::getValidKondisi())],
    //     ]);

    //     try {
    //         DB::beginTransaction();
    //         $semuaItemDiajukanKembali = true;

    //         foreach ($peminjaman->detailPeminjaman as $detail) {
    //             if (in_array($detail->status_item, [DetailPeminjaman::STATUS_ITEM_DIAMBIL, DetailPeminjaman::STATUS_ITEM_TERLAMBAT])) {
    //                 $kondisiSetelahDipakai = $validated['kondisi_item_kembali'][$detail->id_barang_qr_code] ?? $detail->barangQrCode->kondisi;

    //                 $detail->status_item = DetailPeminjaman::STATUS_ITEM_MENUNGGU_VERIFIKASI_KEMBALI;
    //                 $detail->kondisi_saat_kembali_diajukan = $kondisiSetelahDipakai;
    //                 $detail->catatan_pengembalian_peminjam = $request->catatan_pengembalian_guru;
    //                 $detail->tanggal_diajukan_kembali = now();
    //                 $detail->save();
    //             } elseif (!in_array($detail->status_item, [DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN, DetailPeminjaman::STATUS_ITEM_DITOLAK])) {
    //                 $semuaItemDiajukanKembali = false;
    //             }
    //         }

    //         if ($semuaItemDiajukanKembali && $peminjaman->detailPeminjaman()->whereIn('status_item', [DetailPeminjaman::STATUS_ITEM_DIAMBIL, DetailPeminjaman::STATUS_ITEM_TERLAMBAT])->count() == 0) {
    //             $peminjaman->status = Peminjaman::STATUS_MENUNGGU_VERIFIKASI_KEMBALI;
    //             $peminjaman->save();
    //         } else if ($peminjaman->detailPeminjaman()->where('status_item', DetailPeminjaman::STATUS_ITEM_MENUNGGU_VERIFIKASI_KEMBALI)->exists()) {
    //             $peminjaman->status = Peminjaman::STATUS_SEBAGIAN_DIAJUKAN_KEMBALI;
    //             $peminjaman->save();
    //         }

    //         // Log aktivitas
    //         LogAktivitas::create([
    //             'user_id' => Auth::id(),
    //             'aktivitas' => 'Mengajukan pengembalian',
    //             'deskripsi' => "Peminjaman ID {$peminjaman->id} - pengajuan pengembalian",
    //             'created_at' => now()
    //         ]);

    //         DB::commit();
    //         return redirect()->route('guru.peminjaman.show', $peminjaman->id)->with('success', 'Pengajuan pengembalian berhasil dikirim.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Gagal mengajukan pengembalian oleh guru: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Gagal mengajukan pengembalian: ' . $e->getMessage());
    //     }
    // }

    /**
     * Menampilkan riwayat peminjaman guru.
     */
    public function riwayat(Request $request): View
    {
        $this->authorize('viewAnyGuru', Peminjaman::class);
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $query = Peminjaman::where('id_guru', $user->id)
            ->whereIn('status', [Peminjaman::STATUS_SELESAI, Peminjaman::STATUS_DITOLAK, Peminjaman::STATUS_DIBATALKAN]) // Perbaiki nama kolom
            ->with(['detailPeminjaman.barangQrCode.barang', 'operatorProses'])
            ->orderBy('tanggal_proses', 'desc');

        if ($request->filled('status_peminjaman')) {
            $query->where('status', $request->status_peminjaman); // Perbaiki nama kolom
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tujuan_peminjaman', 'like', "%{$searchTerm}%")
                    ->orWhereHas('detailPeminjaman.barangQrCode.barang', function ($subq) use ($searchTerm) {
                        $subq->where('nama_barang', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $riwayatPeminjaman = $query->paginate(10)->withQueryString();
        $statusOptions = Peminjaman::getPossibleStatuses();

        return view('guru.peminjaman.riwayat', compact('riwayatPeminjaman', 'statusOptions'));
    }

    /**
     * Mengambil daftar barang (unit) yang tersedia berdasarkan ruangan untuk form create peminjaman.
     */
    public function getBarangUnitsByRuangan(Request $request, Ruangan $ruangan): JsonResponse
    {
        $searchTerm = $request->input('q', '');

        $units = BarangQrCode::where('id_ruangan', $ruangan->id)
            ->where('status', BarangQrCode::STATUS_TERSEDIA)
            ->whereNull('deleted_at')
            ->whereHas('barang', function ($query) use ($searchTerm) {
                $query->where('nama_barang', 'like', "%{$searchTerm}%")
                    ->orWhere('kode_barang', 'like', "%{$searchTerm}%")
                    ->orWhere('merk_model', 'like', "%{$searchTerm}%");
            })
            ->with('barang:id,nama_barang,kode_barang,merk_model')
            ->select('id', 'kode_inventaris_sekolah', 'no_seri_pabrik', 'id_barang')
            ->take(20)
            ->get()
            ->map(function ($unit) {
                $merk_model_display = $unit->barang->merk_model ?? 'N/A';
                $no_seri_display = $unit->no_seri_pabrik ? " (SN: {$unit->no_seri_pabrik})" : "";

                return [
                    'id' => $unit->id,
                    // Gabungkan string dengan cara yang lebih aman
                    'text' => "{$unit->kode_inventaris_sekolah} - {$unit->barang->nama_barang} ({$merk_model_display}){$no_seri_display}",
                    'nama_barang' => $unit->barang->nama_barang,
                    'kode_inventaris' => $unit->kode_inventaris_sekolah,
                    'no_seri' => $unit->no_seri_pabrik,
                    'kondisi' => $unit->kondisi, // Mengirimkan kondisi unit
                    'merk_model' => $merk_model_display // Mengirimkan merk/model yang sudah diproses
                ];
            });

        return response()->json($units);
    }
}
