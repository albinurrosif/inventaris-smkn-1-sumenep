<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\BarangQrCode;
use App\Models\Ruangan;
use App\Models\KategoriBarang;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\Pemeliharaan;
use App\Models\MutasiBarang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\InventarisReportExport;
use App\Exports\PeminjamanReportExport;
use App\Exports\PemeliharaanReportExport;
use App\Exports\MutasiReportExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    use AuthorizesRequests;
    /**
     * Menampilkan halaman laporan inventaris dengan filter.
     */
    public function inventaris(Request $request): View
    {
        // Otorisasi: Cek apakah user boleh melihat laporan inventaris.
        $this->authorize('view-laporan-inventaris');

        $user = Auth::user();
        /** @var \App\Models\User $user */

        // Ambil semua filter dari request
        $id_ruangan = $request->input('id_ruangan');
        $id_kategori = $request->input('id_kategori');
        $kondisi = $request->input('kondisi');
        $tahun_perolehan = $request->input('tahun_perolehan');

        // Query dasar untuk mengambil data unit barang beserta relasinya
        $query = BarangQrCode::with(['barang.kategori', 'ruangan', 'pemegangPersonal'])
            ->whereNull('deleted_at'); // Hanya ambil data yang aktif

        // === LOGIKA INTI: PEMBATASAN AKSES UNTUK OPERATOR ===
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');

            // Jika operator memilih ruangan, pastikan itu adalah ruangan miliknya
            if ($id_ruangan && !$ruanganOperatorIds->contains($id_ruangan)) {
                // Jika mencoba filter ruangan yang bukan miliknya, paksa untuk tidak menampilkan hasil
                $query->whereRaw('1 = 0');
            } else {
                // Filter semua hasil query agar hanya dari ruangan yang dikelolanya
                $query->whereIn('id_ruangan', $ruanganOperatorIds);
            }
        }

        // Terapkan filter dari form
        if ($id_ruangan) {
            $query->where('id_ruangan', $id_ruangan);
        }
        if ($id_kategori) {
            $query->whereHas('barang', function ($q) use ($id_kategori) {
                $q->where('id_kategori', $id_kategori);
            });
        }
        if ($kondisi) {
            $query->where('kondisi', $kondisi);
        }
        if ($tahun_perolehan) {
            $query->whereYear('tanggal_perolehan_unit', $tahun_perolehan);
        }

        // Eksekusi query dan paginasi
        $inventaris = $query->latest('created_at')->paginate(25)->withQueryString();

        // Data untuk dropdown filter
        // Jika Operator, hanya tampilkan ruangan miliknya. Jika Admin, tampilkan semua.
        $ruanganList = $user->hasRole(User::ROLE_ADMIN)
            ? Ruangan::orderBy('nama_ruangan')->get()
            : $user->ruanganYangDiKelola()->orderBy('nama_ruangan')->get();

        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $kondisiList = BarangQrCode::getValidKondisi();

        return view('pages.Laporan.inventaris', compact(
            'inventaris',
            'ruanganList',
            'kategoriList',
            'kondisiList',
            'request'
        ));
    }

    /**
     * Menangani export laporan inventaris ke PDF.
     */
    public function exportInventarisPdf(Request $request)
    {
        $this->authorize('view-laporan-inventaris');
        $user = Auth::user();
        /** @var \App\Models\User $user */

        // LOGIKA QUERY DI-COPY DARI METHOD inventaris() UNTUK MEMASTIKAN DATA SAMA
        $id_ruangan = $request->input('id_ruangan');
        $id_kategori = $request->input('id_kategori');
        $kondisi = $request->input('kondisi');
        $tahun_perolehan = $request->input('tahun_perolehan');

        $query = BarangQrCode::with(['barang.kategori', 'ruangan', 'pemegangPersonal'])
            ->whereNull('deleted_at');

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            if ($id_ruangan && !$ruanganOperatorIds->contains($id_ruangan)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id_ruangan', $ruanganOperatorIds);
            }
        }

        if ($id_ruangan) $query->where('id_ruangan', $id_ruangan);
        if ($id_kategori) $query->whereHas('barang', fn($q) => $q->where('id_kategori', $id_kategori));
        if ($kondisi) $query->where('kondisi', $kondisi);
        if ($tahun_perolehan) $query->whereYear('tanggal_perolehan_unit', $tahun_perolehan);

        // PERBEDAAN: Gunakan ->get() untuk mengambil semua data, bukan ->paginate()
        $inventaris = $query->latest('created_at')->get();

        if ($inventaris->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export berdasarkan filter yang dipilih.');
        }

        // Membuat PDF
        $pdf = PDF::loadView('pages.Laporan.pdf.inventaris_pdf', compact('inventaris'))
            ->setPaper('a4', 'landscape'); // Atur ukuran kertas dan orientasi

        $namaFile = 'laporan-inventaris-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($namaFile);
    }

    /**
     * Menangani export laporan inventaris ke Excel.
     */
    public function exportInventarisExcel(Request $request)
    {
        $this->authorize('view-laporan-inventaris');
        // Menggunakan kembali semua logika query dari method inventaris()
        // ... (copy semua logika filter dari method inventaris() Anda ke sini) ...
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $id_ruangan = $request->input('id_ruangan');
        $id_kategori = $request->input('id_kategori');
        $kondisi = $request->input('kondisi');
        $tahun_perolehan = $request->input('tahun_perolehan');
        $query = BarangQrCode::with(['barang.kategori', 'ruangan', 'pemegangPersonal'])->whereNull('deleted_at');
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            if ($id_ruangan && !$ruanganOperatorIds->contains($id_ruangan)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id_ruangan', $ruanganOperatorIds);
            }
        }
        if ($id_ruangan) $query->where('id_ruangan', $id_ruangan);
        if ($id_kategori) $query->whereHas('barang', fn($q) => $q->where('id_kategori', $id_kategori));
        if ($kondisi) $query->where('kondisi', $kondisi);
        if ($tahun_perolehan) $query->whereYear('tanggal_perolehan_unit', $tahun_perolehan);

        // Ambil semua data tanpa paginasi
        $inventaris = $query->latest('created_at')->get();

        if ($inventaris->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export.');
        }

        $namaFile = 'laporan-inventaris-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new InventarisReportExport($inventaris), $namaFile);
    }

    /**
     * Menampilkan halaman laporan transaksi peminjaman dengan filter.
     */
    public function peminjaman(Request $request): View
    {
        $this->authorize('view-laporan-peminjaman');
        $user = Auth::user();
        /** @var \App\Models\User $user */

        // Ambil filter dari request
        $status = $request->input('status');
        $id_guru = $request->input('id_guru');
        $tanggal_mulai = $request->input('tanggal_mulai');
        $tanggal_selesai = $request->input('tanggal_selesai');

        // Query dasar
        $query = Peminjaman::with(['guru', 'detailPeminjaman.barangQrCode.barang'])
            ->withTrashed() // Tampilkan juga yang sudah diarsipkan jika diminta
            ->latest('tanggal_pengajuan');

        // Filter berdasarkan peran Operator
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function (Builder $q) use ($ruanganOperatorIds) {
                $q->whereHas('detailPeminjaman.barangQrCode', function (Builder $qDetail) use ($ruanganOperatorIds) {
                    $qDetail->whereIn('id_ruangan', $ruanganOperatorIds);
                })->orWhereIn('id_ruangan_tujuan_peminjaman', $ruanganOperatorIds);
            });
        }

        // Terapkan filter dari form
        if ($status) {
            $query->where('status', $status);
        }
        if ($id_guru && $user->hasRole(User::ROLE_ADMIN)) { // Guru hanya bisa difilter oleh Admin
            $query->where('id_guru', $id_guru);
        }
        if ($tanggal_mulai) {
            $query->whereDate('tanggal_pengajuan', '>=', $tanggal_mulai);
        }
        if ($tanggal_selesai) {
            $query->whereDate('tanggal_pengajuan', '<=', $tanggal_selesai);
        }

        $peminjamanList = $query->paginate(20)->withQueryString();

        // Data untuk dropdown filter
        $statusList = Peminjaman::getValidStatuses();
        $guruList = User::where('role', User::ROLE_GURU)->orderBy('username')->get();

        return view('pages.Laporan.peminjaman', compact(
            'peminjamanList',
            'statusList',
            'guruList',
            'request'
        ));
    }

    /**
     * Menangani export laporan peminjaman ke PDF.
     */
    public function exportPeminjamanPdf(Request $request)
    {
        $this->authorize('view-laporan-peminjaman');
        $user = Auth::user();
        /** @var \App\Models\User $user */

        // Menggunakan kembali logika query dari method peminjaman()
        $status = $request->input('status');
        $id_guru = $request->input('id_guru');
        $tanggal_mulai = $request->input('tanggal_mulai');
        $tanggal_selesai = $request->input('tanggal_selesai');

        $query = Peminjaman::with(['guru', 'detailPeminjaman.barangQrCode.barang'])
            ->withTrashed()->latest('tanggal_pengajuan');

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function (Builder $q) use ($ruanganOperatorIds) {
                $q->whereHas('detailPeminjaman.barangQrCode', fn(Builder $qD) => $qD->whereIn('id_ruangan', $ruanganOperatorIds))
                    ->orWhereIn('id_ruangan_tujuan_peminjaman', $ruanganOperatorIds);
            });
        }

        if ($status) $query->where('status', $status);
        if ($id_guru && $user->hasRole(User::ROLE_ADMIN)) $query->where('id_guru', $id_guru);
        if ($tanggal_mulai) $query->whereDate('tanggal_pengajuan', '>=', $tanggal_mulai);
        if ($tanggal_selesai) $query->whereDate('tanggal_pengajuan', '<=', $tanggal_selesai);

        // Ambil semua data yang cocok dengan filter, tanpa paginasi
        $peminjamanList = $query->get();

        if ($peminjamanList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export.');
        }

        $pdf = PDF::loadView('pages.Laporan.pdf.peminjaman_pdf', compact('peminjamanList'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('laporan-peminjaman-' . now()->format('Ymd') . '.pdf');
    }

    /**
     * Menangani export laporan peminjaman ke Excel.
     */
    public function exportPeminjamanExcel(Request $request)
    {
        $this->authorize('view-laporan-peminjaman');
        // Copy-paste SEMUA logika query dari method peminjaman() Anda ke sini
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $status = $request->input('status');
        $id_guru = $request->input('id_guru');
        $tanggal_mulai = $request->input('tanggal_mulai');
        $tanggal_selesai = $request->input('tanggal_selesai');

        $query = Peminjaman::with(['guru', 'detailPeminjaman.barangQrCode.barang'])->withTrashed()->latest('tanggal_pengajuan');

        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function (Builder $q) use ($ruanganOperatorIds) {
                $q->whereHas('detailPeminjaman.barangQrCode', fn(Builder $qD) => $qD->whereIn('id_ruangan', $ruanganOperatorIds))
                    ->orWhereIn('id_ruangan_tujuan_peminjaman', $ruanganOperatorIds);
            });
        }

        if ($status) $query->where('status', $status);
        if ($id_guru && $user->hasRole(User::ROLE_ADMIN)) $query->where('id_guru', $id_guru);
        if ($tanggal_mulai) $query->whereDate('tanggal_pengajuan', '>=', $tanggal_mulai);
        if ($tanggal_selesai) $query->whereDate('tanggal_pengajuan', '<=', $tanggal_selesai);

        $peminjamanList = $query->get(); // Ambil semua data

        if ($peminjamanList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export.');
        }

        $namaFile = 'laporan-peminjaman-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new PeminjamanReportExport($peminjamanList), $namaFile);
    }


    /**
     * Menampilkan halaman laporan pemeliharaan barang dengan filter.
     */
    public function pemeliharaan(Request $request): View
    {
        $this->authorize('view-laporan-pemeliharaan');
        $user = Auth::user();
        /** @var \App\Models\User $user */

        // Ambil filter dari request
        $status_pengajuan = $request->input('status_pengajuan');
        $status_pengerjaan = $request->input('status_pengerjaan');
        $id_pelapor = $request->input('id_pelapor');
        $tanggal_mulai = $request->input('tanggal_mulai');
        $tanggal_selesai = $request->input('tanggal_selesai');

        $query = Pemeliharaan::with(['barangQrCode.barang', 'pengaju', 'penyetuju', 'operatorPengerjaan'])
            ->withTrashed() // Tampilkan juga yang sudah diarsipkan
            ->latest('tanggal_pengajuan');

        // Filter data untuk Operator
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function (Builder $q) use ($ruanganOperatorIds, $user) {
                $q->where('id_user_pengaju', $user->id) // Laporan yang dibuat operator
                    ->orWhere('id_operator_pengerjaan', $user->id) // Laporan yang dikerjakan operator
                    ->orWhereHas('barangQrCode', fn(Builder $qD) => $qD->whereIn('id_ruangan', $ruanganOperatorIds)); // Laporan untuk barang di ruangannya
            });
        }

        // Terapkan filter form
        if ($status_pengajuan) $query->where('status_pengajuan', $status_pengajuan);
        if ($status_pengerjaan) $query->where('status_pengerjaan', $status_pengerjaan);
        if ($id_pelapor && $user->hasRole(User::ROLE_ADMIN)) $query->where('id_user_pengaju', $id_pelapor);
        if ($tanggal_mulai) $query->whereDate('tanggal_pengajuan', '>=', $tanggal_mulai);
        if ($tanggal_selesai) $query->whereDate('tanggal_pengajuan', '<=', $tanggal_selesai);

        $pemeliharaanList = $query->paginate(20)->withQueryString();

        // Data untuk dropdown filter
        $statusPengajuanList = Pemeliharaan::getValidStatusPengajuan();
        $statusPengerjaanList = Pemeliharaan::getValidStatusPengerjaan();
        $userList = User::orderBy('username')->get();

        return view('pages.Laporan.pemeliharaan', compact(
            'pemeliharaanList',
            'statusPengajuanList',
            'statusPengerjaanList',
            'userList',
            'request'
        ));
    }

    /**
     * Menangani export laporan pemeliharaan ke PDF.
     */
    public function exportPemeliharaanPdf(Request $request)
    {
        $this->authorize('view-laporan-pemeliharaan');

        // Menggunakan kembali logika query dari method pemeliharaan()
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $query = Pemeliharaan::with(['barangQrCode.barang', 'pengaju', 'penyetuju', 'operatorPengerjaan'])
            ->withTrashed()->latest('tanggal_pengajuan');

        // (Copy-paste semua logika filter dari method pemeliharaan() Anda ke sini)
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function (Builder $q) use ($ruanganOperatorIds, $user) {
                $q->where('id_user_pengaju', $user->id)
                    ->orWhere('id_operator_pengerjaan', $user->id)
                    ->orWhereHas('barangQrCode', fn(Builder $qD) => $qD->whereIn('id_ruangan', $ruanganOperatorIds));
            });
        }
        if ($request->status_pengajuan) $query->where('status_pengajuan', $request->status_pengajuan);
        if ($request->status_pengerjaan) $query->where('status_pengerjaan', $request->status_pengerjaan);
        if ($request->id_pelapor && $user->hasRole(User::ROLE_ADMIN)) $query->where('id_user_pengaju', $request->id_pelapor);
        if ($request->tanggal_mulai) $query->whereDate('tanggal_pengajuan', '>=', $request->tanggal_mulai);
        if ($request->tanggal_selesai) $query->whereDate('tanggal_pengajuan', '<=', $request->tanggal_selesai);


        // Ambil semua data tanpa paginasi
        $pemeliharaanList = $query->get();

        if ($pemeliharaanList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export.');
        }

        $pdf = PDF::loadView('pages.Laporan.pdf.pemeliharaan_pdf', compact('pemeliharaanList'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-pemeliharaan-' . now()->format('Ymd') . '.pdf');
    }

    /**
     * Menangani export laporan pemeliharaan ke Excel.
     */
    public function exportPemeliharaanExcel(Request $request)
    {
        $this->authorize('view-laporan-pemeliharaan');
        // Copy-paste SEMUA logika query dari method pemeliharaan() Anda ke sini
        // ...
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $query = Pemeliharaan::with(['barangQrCode.barang', 'pengaju', 'penyetuju', 'operatorPengerjaan'])
            ->withTrashed()->latest('tanggal_pengajuan');
        if ($user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $user->ruanganYangDiKelola()->pluck('id');
            $query->where(function (Builder $q) use ($ruanganOperatorIds, $user) {
                $q->where('id_user_pengaju', $user->id)
                    ->orWhere('id_operator_pengerjaan', $user->id)
                    ->orWhereHas('barangQrCode', fn(Builder $qD) => $qD->whereIn('id_ruangan', $ruanganOperatorIds));
            });
        }
        if ($request->status_pengajuan) $query->where('status_pengajuan', $request->status_pengajuan);
        if ($request->status_pengerjaan) $query->where('status_pengerjaan', $request->status_pengerjaan);
        if ($request->id_pelapor && $user->hasRole(User::ROLE_ADMIN)) $query->where('id_user_pengaju', $request->id_pelapor);
        if ($request->tanggal_mulai) $query->whereDate('tanggal_pengajuan', '>=', $request->tanggal_mulai);
        if ($request->tanggal_selesai) $query->whereDate('tanggal_pengajuan', '<=', $request->tanggal_selesai);

        $pemeliharaanList = $query->get();

        if ($pemeliharaanList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export.');
        }

        $namaFile = 'laporan-pemeliharaan-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new PemeliharaanReportExport($pemeliharaanList), $namaFile);
    }


    // Di dalam LaporanController.php

    private function getMutasiQuery(Request $request)
    {
        // Pindahkan semua logika query ke sini
        return MutasiBarang::with(['barangQrCode.barang', 'ruanganAsal', 'ruanganTujuan', 'pemegangAsal', 'pemegangTujuan', 'admin'])
            ->filter($request) // Menggunakan scope filter dari Model
            ->latest('tanggal_mutasi');
    }
    /**
     * Menampilkan halaman laporan mutasi barang dengan filter.
     */
    public function mutasi(Request $request): View
    {
        // $this->authorize('view-laporan-mutasi');

        $filters = $request->only(['search', 'jenis_mutasi', 'id_user_pencatat', 'tanggal_mulai', 'tanggal_selesai']);

        // Panggil method query kita yang baru, lalu paginasi hasilnya
        $riwayatMutasi = $this->getMutasiQuery($request)->paginate(20)->withQueryString();

        $adminList = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->orderBy('username')->get();
        $jenisMutasiList = MutasiBarang::select('jenis_mutasi')->distinct()->pluck('jenis_mutasi');

        return view('pages.Laporan.mutasi', compact('riwayatMutasi', 'adminList', 'jenisMutasiList', 'filters'));
    }

    // Di dalam LaporanController.php

    public function exportMutasiPdf(Request $request)
    {
        // $this->authorize('view-laporan-mutasi');

        // Gunakan kembali method query kita, tapi ambil semua data dengan ->get()
        $riwayatMutasi = $this->getMutasiQuery($request)->get();

        if ($riwayatMutasi->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export berdasarkan filter yang dipilih.');
        }

        $pdf = PDF::loadView('pages.Laporan.pdf.mutasi_pdf', compact('riwayatMutasi'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-mutasi-' . now()->format('Ymd-His') . '.pdf');
    }

    public function exportMutasiExcel(Request $request)
    {
        // $this->authorize('view-laporan-mutasi');

        $riwayatMutasi = $this->getMutasiQuery($request)->get();

        if ($riwayatMutasi->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk di-export.');
        }

        return Excel::download(new MutasiReportExport($riwayatMutasi), 'laporan-mutasi-' . now()->format('Ymd-His') . '.xlsx');
    }
}
