<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Ruangan; // Untuk filter ruangan
use App\Models\User;    // Untuk filter peminjam
use App\Models\DetailPeminjaman; // Tambahkan ini untuk mengimpor model DetailPeminjaman
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf; // Jika menggunakan DomPDF
// use Maatwebsite\Excel\Facades\Excel; // Jika menggunakan Maatwebsite/Excel
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PeminjamanAdminController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        // Menerapkan policy untuk semua metode resource controller jika sesuai
        // $this->authorizeResource(Peminjaman::class, 'peminjaman');
        // Atau per metode jika lebih spesifik
    }

    /**
     * Menampilkan daftar semua peminjaman untuk admin dengan filter dan pencarian.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Peminjaman::class); // Policy: Admin bisa lihat semua

        $query = Peminjaman::with([
            'guru', // Relasi ke user peminjam (guru)
            'operatorProses', // Relasi ke user operator yang memproses
            'ruanganTujuanPeminjaman', // Relasi ke ruangan tujuan
            'detailPeminjaman.barangQrCode.barang.kategori', // Detail item yang dipinjam
            'detailPeminjaman.barangQrCode.ruanganAsalPeminjaman', // Ruangan asal unit saat dipinjam
        ]);

        // Filter berdasarkan status peminjaman utama
        if ($request->filled('status_peminjaman')) {
            $query->where('status_peminjaman', $request->status_peminjaman);
        }

        // Filter berdasarkan peminjam (guru)
        if ($request->filled('id_guru')) {
            $query->where('id_guru', $request->id_guru);
        }

        // Filter berdasarkan barang yang dipinjam (nama atau kode inventaris)
        if ($request->filled('search_barang')) {
            $searchTerm = $request->search_barang;
            $query->whereHas('detailPeminjaman.barangQrCode', function ($q) use ($searchTerm) {
                $q->where('kode_inventaris_sekolah', 'like', "%{$searchTerm}%")
                    ->orWhereHas('barang', function ($sq) use ($searchTerm) {
                        $sq->where('nama_barang', 'like', "%{$searchTerm}%")
                            ->orWhere('kode_barang', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Filter berdasarkan ruangan tujuan peminjaman
        if ($request->filled('id_ruangan_tujuan_peminjaman')) {
            $query->where('id_ruangan_tujuan_peminjaman', $request->id_ruangan_tujuan_peminjaman);
        }

        // Filter berdasarkan rentang tanggal pengajuan
        if ($request->filled('tanggal_pengajuan_mulai')) {
            $query->whereDate('tanggal_pengajuan', '>=', Carbon::parse($request->tanggal_pengajuan_mulai));
        }
        if ($request->filled('tanggal_pengajuan_akhir')) {
            $query->whereDate('tanggal_pengajuan', '<=', Carbon::parse($request->tanggal_pengajuan_akhir));
        }

        // Filter untuk peminjaman yang terlambat (status 'Sedang Dipinjam' dan tanggal rencana kembali sudah lewat)
        if ($request->boolean('filter_terlambat')) {
            $query->where('status_peminjaman', Peminjaman::STATUS_SEDANG_DIPINJAM)
                ->whereDate('tanggal_rencana_kembali', '<', now());
        }

        // Pengurutan data
        $sortField = $request->input('sort_by', 'tanggal_pengajuan');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSortFields = ['id', 'tanggal_pengajuan', 'status_peminjaman', 'tanggal_rencana_pinjam', 'tanggal_rencana_kembali'];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('tanggal_pengajuan', 'desc'); // Default
        }

        $perPage = $request->input('per_page', 15);
        $peminjamans = $query->paginate($perPage)->appends($request->query());

        // Data untuk dropdown filter
        $statusOptions = Peminjaman::getPossibleStatuses(); // Asumsi ada metode ini di model Peminjaman
        $guruList = User::where('role', User::ROLE_GURU)->orderBy('username')->get(['id', 'username']);
        $ruanganList = Ruangan::orderBy('nama_ruangan')->get(['id', 'nama_ruangan', 'kode_ruangan']);

        // Ringkasan data (contoh)
        $summary = [
            'total' => Peminjaman::count(),
            'diajukan' => Peminjaman::where('status_peminjaman', Peminjaman::STATUS_DIAJUKAN)->count(),
            // 'disetujui_penuh' => Peminjaman::where('status_peminjaman', Peminjaman::STATUS_DISETUJUI_PENUH)->count(),
            'sedang_dipinjam' => Peminjaman::where('status_peminjaman', Peminjaman::STATUS_SEDANG_DIPINJAM)->count(),
            'selesai' => Peminjaman::where('status_peminjaman', Peminjaman::STATUS_SELESAI)->count(),
            'terlambat' => Peminjaman::where('status_peminjaman', Peminjaman::STATUS_SEDANG_DIPINJAM)
                ->whereDate('tanggal_rencana_kembali', '<', now())->count(),
        ];

        return view('admin.peminjaman.index', compact(
            'peminjamans',
            'statusOptions',
            'guruList',
            'ruanganList',
            'summary'
        ));
    }

    /**
     * Menampilkan detail peminjaman untuk admin.
     */
    public function show(Peminjaman $peminjaman): View // Menggunakan Route Model Binding
    {
        $this->authorize('view', $peminjaman);

        $peminjaman->load([
            'guru',
            'operatorProses',
            'ruanganTujuanPeminjaman',
            'detailPeminjaman.barangQrCode.barang.kategori',
            'detailPeminjaman.barangQrCode.ruanganAsalPeminjaman', // Ruangan asal unit saat dipinjam
            'detailPeminjaman.operatorSetujuItem',
            'detailPeminjaman.operatorTolakItem',
            'detailPeminjaman.operatorSerah',
            'detailPeminjaman.operatorVerifikasiKembali',
        ]);

        // Hitung keterlambatan untuk setiap item jika relevan
        // foreach ($peminjaman->detailPeminjaman as $detail) {
        //     if (
        //         in_array($detail->status_item, [DetailPeminjaman::STATUS_ITEM_DIAMBIL, DetailPeminjaman::STATUS_ITEM_TERLAMBAT]) &&
        //         $peminjaman->tanggal_rencana_kembali &&
        //         Carbon::parse($peminjaman->tanggal_rencana_kembali)->isPast() &&
        //         !$detail->tanggal_item_diverifikasi_kembali
        //     ) { // Belum diverifikasi kembali
        //         $detail->is_terlambat_item = true;
        //         $detail->jumlah_hari_terlambat_item = Carbon::parse($peminjaman->tanggal_rencana_kembali)->diffInDays(now());
        //     } else {
        //         $detail->is_terlambat_item = false;
        //         $detail->jumlah_hari_terlambat_item = 0;
        //     }
        // }

        return view('admin.peminjaman.show', compact('peminjaman'));
    }

    /**
     * Menampilkan halaman laporan peminjaman dengan filter.
     */
    public function report(Request $request): View
    {
        $this->authorize('viewReport', Peminjaman::class); // Policy baru untuk laporan

        // Logika query dan filter bisa mirip dengan index()
        $query = Peminjaman::with([
            'guru',
            'operatorProses',
            'ruanganTujuanPeminjaman',
            'detailPeminjaman.barangQrCode.barang.kategori',
            'detailPeminjaman.barangQrCode.ruanganAsalPeminjaman'
        ]);

        // Terapkan filter dari request (contoh)
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_pengajuan', '>=', Carbon::parse($request->tanggal_mulai));
        }
        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal_pengajuan', '<=', Carbon::parse($request->tanggal_akhir));
        }
        if ($request->filled('status_peminjaman_report')) {
            $query->where('status_peminjaman', $request->status_peminjaman_report);
        }
        // Tambahkan filter lain jika perlu

        $peminjamanList = $query->orderBy('tanggal_pengajuan', 'desc')->get();

        // Data untuk ringkasan laporan
        $summary = [
            'total_peminjaman_difilter' => $peminjamanList->count(),
            // 'total_item_dipinjam' => $peminjamanList->sum(fn($p) => $p->detailPeminjaman()->where('status_item', DetailPeminjaman::STATUS_ITEM_DIAMBIL)->orWhere('status_item', DetailPeminjaman::STATUS_ITEM_TERLAMBAT)->count()),
            // // Tambahkan statistik lain yang relevan
        ];

        $statusOptions = Peminjaman::getPossibleStatuses();
        // Tambahkan data lain untuk filter di halaman laporan
        $guruList = User::where('role', User::ROLE_GURU)->orderBy('username')->get(['id', 'username']);
        $ruanganList = Ruangan::orderBy('nama_ruangan')->get(['id', 'nama_ruangan']);


        return view('admin.peminjaman.report', compact(
            'peminjamanList',
            'summary',
            'statusOptions',
            'guruList',
            'ruanganList'
        ));
    }

    /**
     * Menampilkan daftar peminjaman yang terlambat.
     */
    public function overdue(Request $request): View
    {
        $this->authorize('viewOverdue', Peminjaman::class); // Policy baru

        $query = Peminjaman::where('status_peminjaman', Peminjaman::STATUS_SEDANG_DIPINJAM)
            ->whereDate('tanggal_rencana_kembali', '<', now())
            ->with(['guru', 'detailPeminjaman.barangQrCode.barang', 'ruanganTujuanPeminjaman']);

        if ($request->filled('peminjam_overdue')) {
            $query->where('id_guru', $request->peminjam_overdue);
        }
        // Tambahkan filter lain jika perlu (misal, berdasarkan lama keterlambatan)

        $peminjamanTerlambat = $query->orderBy('tanggal_rencana_kembali', 'asc')->paginate(10)->appends($request->query());

        foreach ($peminjamanTerlambat as $p) {
            $p->lama_terlambat_hari = Carbon::parse($p->tanggal_rencana_kembali)->diffInDays(now());
        }
        $guruList = User::where('role', User::ROLE_GURU)->orderBy('username')->get(['id', 'username']);


        return view('admin.peminjaman.overdue', compact('peminjamanTerlambat', 'guruList'));
    }

    /**
     * Export laporan peminjaman ke PDF.
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('exportReport', Peminjaman::class); // Policy baru

        // Logika query dan filter mirip dengan report() atau index()
        $query = Peminjaman::with([
            'guru',
            'operatorProses',
            'ruanganTujuanPeminjaman',
            'detailPeminjaman.barangQrCode.barang.kategori',
            'detailPeminjaman.barangQrCode.ruanganAsalPeminjaman'
        ]);
        // Terapkan filter dari $request
        // ... (logika filter sama seperti di report() atau index()) ...
        if ($request->filled('tanggal_mulai')) $query->whereDate('tanggal_pengajuan', '>=', Carbon::parse($request->tanggal_mulai));
        if ($request->filled('tanggal_akhir')) $query->whereDate('tanggal_pengajuan', '<=', Carbon::parse($request->tanggal_akhir));
        if ($request->filled('status_peminjaman_export')) $query->where('status_peminjaman', $request->status_peminjaman_export);
        // ...

        $peminjamans = $query->orderBy('tanggal_pengajuan', 'desc')->get();
        $filterInfo = $request->all(); // Kirim semua filter ke view PDF

        $title = 'Laporan Peminjaman Barang';
        $date = Carbon::now()->isoFormat('DD MMMM YYYY');

        $pdf = Pdf::loadView('admin.peminjaman.pdf.laporan_peminjaman', compact(
            'peminjamans',
            'filterInfo', // Untuk menampilkan filter yang aktif di PDF
            'title',
            'date'
        ));
        return $pdf->download('laporan-peminjaman-' . now()->format('Y-m-d-His') . '.pdf');
    }

    // Metode untuk export Excel bisa ditambahkan di sini jika menggunakan Maatwebsite/Excel
    // public function exportExcel(Request $request) { ... }

}
