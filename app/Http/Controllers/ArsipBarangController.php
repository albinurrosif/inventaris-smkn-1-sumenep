<?php

namespace App\Http\Controllers;

use App\Models\ArsipBarang;
use App\Models\BarangQrCode;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Models\BarangStatus; // Ditambahkan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ArsipBarangController extends Controller
{
    use AuthorizesRequests;

    /**
     * Menampilkan daftar arsip barang yang dihapus/diarsipkan.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ArsipBarang::class); // [cite: 715]

        $searchTerm = $request->input('search'); // [cite: 716]
        $statusArsipFilter = $request->input('status_arsip', 'semua'); // Default 'semua' agar pengajuan terlihat
        $jenisPenghapusanFilter = $request->input('jenis_penghapusan'); // [cite: 716]
        $tanggalMulaiFilter = $request->input('tanggal_mulai'); // [cite: 716]
        $tanggalSelesaiFilter = $request->input('tanggal_selesai'); // [cite: 716]

        $query = ArsipBarang::query()
            ->with([
                'barangQrCode' => function ($q) {
                    $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']);
                },
                // PENYESUAIAN NAMA RELASI DI SINI
                'pengaju',    // Ganti dari 'userPengaju' menjadi 'pengaju'
                'penyetuju',  // Ganti dari 'userPenyetuju' menjadi 'penyetuju'
            ]);

        if ($statusArsipFilter && $statusArsipFilter !== 'semua') {
            $query->where('status_arsip', $statusArsipFilter);
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('barangQrCode.barang', function ($qBarang) use ($searchTerm) {
                    $qBarang->where('nama_barang', 'LIKE', "%{$searchTerm}%");
                })->orWhereHas('barangQrCode', function ($qQr) use ($searchTerm) {
                    $qQr->withTrashed(); // Pastikan mencari juga di barang QR yang sudah di-soft-delete
                    $qQr->where('kode_inventaris_sekolah', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('no_seri_pabrik', 'LIKE', "%{$searchTerm}%");
                    // ->orWhere('no_seri_internal', 'LIKE', "%{$searchTerm}%"); // Jika ada field ini
                });
            });
        }

        if ($jenisPenghapusanFilter) {
            $query->where('jenis_penghapusan', $jenisPenghapusanFilter); // [cite: 722]
        }

        if ($tanggalMulaiFilter) {
            // Filter berdasarkan tanggal pengajuan atau tanggal penghapusan resmi, sesuaikan
            $query->whereDate('tanggal_pengajuan_arsip', '>=', $tanggalMulaiFilter);
        }
        if ($tanggalSelesaiFilter) {
            $query->whereDate('tanggal_pengajuan_arsip', '<=', $tanggalSelesaiFilter);
        }

        $arsipList = $query->latest('tanggal_pengajuan_arsip')->paginate(15)->withQueryString();

        $jenisPenghapusanList = ArsipBarang::getValidJenisPenghapusan(); // [cite: 726]
        $statusArsipList = ArsipBarang::getValidStatusArsip(); // Ambil dari model ArsipBarang

        return view('admin.arsip-barang.index', compact(
            'arsipList',
            'searchTerm',
            'statusArsipFilter', // Tambahkan ini
            'statusArsipList', // Tambahkan ini
            'jenisPenghapusanFilter',
            'tanggalMulaiFilter',
            'tanggalSelesaiFilter',
            'jenisPenghapusanList'
        )); // [cite: 727]
    }

    /**
     * Menampilkan detail spesifik dari entri arsip barang.
     */
    public function show($id): View
    {
        $arsip = ArsipBarang::with([
            'barangQrCode' => function ($q) {
                $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']);
            },
            'pengaju',    // Ganti dari 'userPengaju' menjadi 'pengaju'
            'penyetuju',  // Ganti dari 'userPenyetuju' menjadi 'penyetuju'
            'dipulihkanOlehUser'

        ])->findOrFail($id);

        $this->authorize('view', $arsip); // [cite: 731]

        return view('admin.arsip-barang.show', compact('arsip')); // [cite: 731]
    }

    /**
     * Menyetujui pengajuan arsip barang.
     */
    public function approve(Request $request, ArsipBarang $arsipBarang): RedirectResponse
    {
        $this->authorize('approve', $arsipBarang);

        if ($arsipBarang->status_arsip !== ArsipBarang::STATUS_ARSIP_DIAJUKAN) {
            return redirect()->route('admin.arsip-barang.index')->with('error', 'Pengajuan arsip ini tidak dalam status "Diajukan" atau sudah diproses.');
        }

        // Validasi input tambahan jika ada (misal: catatan persetujuan)
        // $validated = $request->validate(['catatan_persetujuan' => 'nullable|string|max:1000']);

        DB::beginTransaction();
        try {
            $userPenyetuju = Auth::user();
            $barangQrCode = $arsipBarang->barangQrCode()->withTrashed()->first();

            if (!$barangQrCode) {
                DB::rollBack();
                return redirect()->route('admin.arsip-barang.index')->with('error', 'Unit barang terkait tidak ditemukan.');
            }

            // Simpan data sebelum diubah untuk log BarangStatus
            $kondisiSebelum = $barangQrCode->kondisi;
            $statusKetersediaanSebelum = $barangQrCode->status;
            $ruanganSebelum = $barangQrCode->id_ruangan;
            $pemegangSebelum = $barangQrCode->id_pemegang_personal;

            // Update status arsip
            $arsipBarang->status_arsip = ArsipBarang::STATUS_ARSIP_DISETUJUI_PERMANEN;
            $arsipBarang->id_user_penyetuju = $userPenyetuju->id;
            $arsipBarang->tanggal_penghapusan_resmi = now();
            // $arsipBarang->catatan_persetujuan = $validated['catatan_persetujuan'] ?? null;
            $arsipBarang->save();

            // Soft delete BarangQrCode jika belum terhapus
            if (!$barangQrCode->trashed()) {
                $barangQrCode->delete(); // Ini akan memicu event 'deleted' di BarangQrCode (decrement total_jumlah_unit)
            }

            // Update status ketersediaan BarangQrCode secara eksplisit jika perlu (untuk tampilan/log saja, karena sudah soft-deleted)
            // Jika model BarangQrCode Anda punya event listener untuk 'deleted' yang mengubah status, ini mungkin tidak perlu.
            // Tapi untuk BarangStatus, kita catat status sesudahnya sebagai 'Diarsipkan/Dihapus'.

            BarangStatus::create([
                'id_barang_qr_code' => $barangQrCode->id,
                'id_user_pencatat' => $userPenyetuju->id,
                'tanggal_pencatatan' => now(),
                'kondisi_sebelumnya' => $kondisiSebelum,
                'kondisi_sesudahnya' => $kondisiSebelum, // Kondisi tidak berubah saat diarsipkan, hanya status
                'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum,
                'status_ketersediaan_sesudahnya' => BarangQrCode::STATUS_DIARSIPKAN, // Gunakan konstanta jika ada
                'id_ruangan_sebelumnya' => $ruanganSebelum,
                'id_ruangan_sesudahnya' => null, // Setelah diarsipkan, lokasi tidak relevan lagi
                'id_pemegang_personal_sebelumnya' => $pemegangSebelum,
                'id_pemegang_personal_sesudahnya' => null, // Setelah diarsipkan, tidak ada pemegang
                'deskripsi_kejadian' => "Pengajuan arsip disetujui. Unit barang {$barangQrCode->kode_inventaris_sekolah} diarsipkan permanen.",
                'id_arsip_barang_trigger' => $arsipBarang->id,
            ]);

            LogAktivitas::create([
                'id_user' => $userPenyetuju->id,
                'aktivitas' => 'Setujui Arsip Barang',
                'deskripsi' => "Menyetujui pengarsipan untuk unit {$barangQrCode->kode_inventaris_sekolah}. Arsip ID: {$arsipBarang->id}",
                'model_terkait' => ArsipBarang::class,
                'id_model_terkait' => $arsipBarang->id,
                'data_lama' => json_encode(['status_arsip' => ArsipBarang::STATUS_ARSIP_DIAJUKAN]),
                'data_baru' => json_encode($arsipBarang->fresh()->toArray()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            return redirect()->route('admin.arsip-barang.index')->with('success', "Pengajuan arsip untuk unit {$barangQrCode->kode_inventaris_sekolah} berhasil disetujui dan unit telah diarsipkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyetujui arsip barang ID {$arsipBarang->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('admin.arsip-barang.index')->with('error', 'Gagal menyetujui pengajuan arsip: ' . (config('app.debug') ? $e->getMessage() : 'Kesalahan sistem.'));
        }
    }

    /**
     * Menolak pengajuan arsip barang.
     */
    public function reject(Request $request, ArsipBarang $arsipBarang): RedirectResponse
    {
        $this->authorize('reject', $arsipBarang);

        if ($arsipBarang->status_arsip !== ArsipBarang::STATUS_ARSIP_DIAJUKAN) {
            return redirect()->route('admin.arsip-barang.index')->with('error', 'Pengajuan arsip ini tidak dalam status "Diajukan" atau sudah diproses.');
        }

        // Validasi input tambahan jika ada (misal: alasan penolakan)
        $validated = $request->validate(['catatan_penolakan' => 'required|string|max:1000']);

        DB::beginTransaction();
        try {
            $userPenyetuju = Auth::user();
            $barangQrCode = $arsipBarang->barangQrCode()->withTrashed()->first(); // withTrashed untuk jaga-jaga

            // Update status arsip
            $arsipBarang->status_arsip = ArsipBarang::STATUS_ARSIP_DITOLAK;
            $arsipBarang->id_user_penyetuju = $userPenyetuju->id;
            // $arsipBarang->catatan_penolakan = $validated['catatan_penolakan']; // Jika ada field ini di DB
            $arsipBarang->save();

            // Jika status BarangQrCode diubah saat pengajuan (misal jadi 'Dalam Proses Arsip'), kembalikan.
            // Saat ini, BarangQrCodeController@archive tidak mengubah status barang, jadi tidak ada yang perlu dikembalikan.
            // Namun, jika ada logika seperti itu, ini tempatnya.

            LogAktivitas::create([
                'id_user' => $userPenyetuju->id,
                'aktivitas' => 'Tolak Arsip Barang',
                'deskripsi' => "Menolak pengarsipan untuk unit " . ($barangQrCode->kode_inventaris_sekolah ?? 'N/A') . ". Alasan: " . $validated['catatan_penolakan'] . ". Arsip ID: {$arsipBarang->id}",
                'model_terkait' => ArsipBarang::class,
                'id_model_terkait' => $arsipBarang->id,
                'data_lama' => json_encode(['status_arsip' => ArsipBarang::STATUS_ARSIP_DIAJUKAN]),
                'data_baru' => json_encode($arsipBarang->fresh()->toArray()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            return redirect()->route('admin.arsip-barang.index')->with('success', "Pengajuan arsip untuk unit " . ($barangQrCode->kode_inventaris_sekolah ?? 'N/A') . " berhasil ditolak.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menolak arsip barang ID {$arsipBarang->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('admin.arsip-barang.index')->with('error', 'Gagal menolak pengajuan arsip: ' . (config('app.debug') ? $e->getMessage() : 'Kesalahan sistem.'));
        }
    }


    /**
     * Memulihkan BarangQrCode yang terkait dengan entri ArsipBarang.
     */
    public function restore(Request $request, $arsipBarangId): RedirectResponse
    {
        $arsipBarang = ArsipBarang::findOrFail($arsipBarangId);
        $userPencatat = Auth::user();

        $this->authorize('restore', $arsipBarang); // Menggunakan ArsipBarangPolicy@restore

        $barangQrCodeForPolicyCheck = BarangQrCode::withTrashed()->find($arsipBarang->id_barang_qr_code);
        if (!$barangQrCodeForPolicyCheck) {
            return redirect()->route('admin.arsip-barang.index', ['status' => 'arsip'])
                ->with('error', 'Unit barang terkait tidak ditemukan dalam arsip.');
        }
        // Otorisasi pada unit barang spesifik yang akan dipulihkan
        $this->authorize('restore', $barangQrCodeForPolicyCheck); // Menggunakan BarangQrCodePolicy@restore

        DB::beginTransaction();
        try {
            $restoredUnit = $arsipBarang->restoreBarang($userPencatat->id);

            if ($restoredUnit) {
                LogAktivitas::create([
                    'id_user' => $userPencatat->id,
                    'aktivitas' => 'Pulihkan Unit Barang dari Arsip (via Arsip ID)',
                    'deskripsi' => "Unit barang {$restoredUnit->kode_inventaris_sekolah} ({$restoredUnit->barang->nama_barang}) berhasil dipulihkan. Arsip ID: {$arsipBarang->id}",
                    'model_terkait' => BarangQrCode::class,
                    'id_model_terkait' => $restoredUnit->id,
                    'data_baru' => $arsipBarang->load('barangQrCode')->toJson(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                DB::commit();
                return redirect()->route('admin.arsip-barang.index', ['status' => 'arsip'])->with('success', "Unit barang '{$restoredUnit->kode_inventaris_sekolah}' berhasil dipulihkan.");
            } else {
                DB::rollBack();
                return back()->with('error', 'Gagal memulihkan unit barang. Proses internal model gagal atau unit tidak dalam kondisi yang dapat dipulihkan.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal memulihkan dari ArsipBarang ID {$arsipBarang->id}: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Gagal memulihkan dari arsip: ' . (config('app.debug') ? $e->getMessage() : 'Kesalahan sistem.'))->withInput();
        }
    }
}
