<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use App\Models\BarangQrCode;
use App\Models\KategoriBarang; // Tambahkan ini
use App\Models\Pemeliharaan; // Tambahkan ini
use App\Models\LogAktivitas; // Tambahkan ini
use App\Models\StokOpname; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB; // Untuk query yang lebih kompleks
use Carbon\Carbon; // Untuk manipulasi tanggal

class DashboardController extends Controller
{
    // =========================================================================
    //  Dashboard Admin
    // =========================================================================

    /**     
     * Menampilkan dashboard untuk admin.
     */
    // public function admin(): View
    // {
    //     // --- Statistik Utama (Cards) ---
    //     $jumlahJenisBarang = Barang::count();
    //     $jumlahUnitBarang = BarangQrCode::whereNull('deleted_at')->count(); // Hanya unit aktif
    //     $jumlahUnitDipinjam = BarangQrCode::where('status', BarangQrCode::STATUS_DIPINJAM)->whereNull('deleted_at')->count();
    //     $jumlahUnitDalamPemeliharaan = BarangQrCode::where('status', BarangQrCode::STATUS_DALAM_PEMELIHARAAN)->whereNull('deleted_at')->count();
    //     $jumlahUser = User::count();
    //     $peminjamanMenunggu = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)->count();
    //     $pemeliharaanMenungguPersetujuan = Pemeliharaan::where('status_pengajuan', Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN)->whereNull('deleted_at')->count();

    //     // --- Data untuk Grafik ---
    //     // 1. Barang per Kategori (untuk Pie Chart)
    //     $barangPerKategori = KategoriBarang::withCount(['barangs as jumlah_barang_aktif' => function ($query) {
    //         $query->whereNull('deleted_at');
    //     }])
    //         ->having('jumlah_barang_aktif', '>', 0) // Hanya kategori yang punya barang
    //         ->orderBy('jumlah_barang_aktif', 'desc')
    //         ->take(5) // Ambil top 5 kategori atau sesuaikan
    //         ->get(['nama_kategori', 'jumlah_barang_aktif']);

    //     // 2. Tren Peminjaman (misalnya, 6 bulan terakhir) (untuk Line Chart)
    //     $trenPeminjaman = Peminjaman::select(
    //         DB::raw('YEAR(tanggal_pengajuan) as tahun'),
    //         DB::raw('MONTH(tanggal_pengajuan) as bulan_angka'),
    //         DB::raw('COUNT(*) as jumlah')
    //     )
    //         ->where('tanggal_pengajuan', '>=', Carbon::now()->subMonths(5)->startOfMonth()) // 6 bulan termasuk bulan ini
    //         ->groupBy('tahun', 'bulan_angka')
    //         ->orderBy('tahun', 'asc')
    //         ->orderBy('bulan_angka', 'asc')
    //         ->get()
    //         ->map(function ($item) {
    //             // Mengubah angka bulan menjadi nama bulan
    //             $item->bulan = Carbon::create()->month($item->bulan_angka)->locale('id')->monthName;
    //             return $item;
    //         });

    //     // 3. Status Unit Barang (untuk Bar Chart atau Donut Chart)
    //     $statusUnitBarang = BarangQrCode::whereNull('deleted_at')
    //         ->select('status', DB::raw('COUNT(*) as jumlah'))
    //         ->groupBy('status')
    //         ->pluck('jumlah', 'status'); // Hasilnya: ['Tersedia' => 10, 'Dipinjam' => 5, ...]

    //     // 4. Kondisi Unit Barang
    //     $kondisiUnitBarang = BarangQrCode::whereNull('deleted_at')
    //         ->select('kondisi', DB::raw('COUNT(*) as jumlah'))
    //         ->groupBy('kondisi')
    //         ->pluck('jumlah', 'kondisi');


    //     // --- Daftar Ringkas ---
    //     // 5. Peminjaman Terbaru Menunggu Persetujuan
    //     $peminjamanTerbaruMenunggu = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
    //         ->with('guru', 'detailPeminjaman')
    //         ->latest('tanggal_pengajuan')
    //         ->take(5)
    //         ->get();

    //     // 6. Laporan Pemeliharaan Terbaru yang Diajukan
    //     $pemeliharaanTerbaruDiajukan = Pemeliharaan::where('status_pengajuan', Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN)
    //         ->whereNull('deleted_at')
    //         ->with('barangQrCode.barang', 'pengaju')
    //         ->latest('tanggal_pengajuan')
    //         ->take(5)
    //         ->get();

    //     // 7. Aktivitas Sistem Terbaru
    //     $logAktivitasTerbaru = LogAktivitas::with('user')
    //         ->latest()
    //         ->take(7)
    //         ->get();


    //     return view('admin.dashboard', compact(
    //         'jumlahJenisBarang',
    //         'jumlahUnitBarang',
    //         'jumlahUnitDipinjam',
    //         'jumlahUnitDalamPemeliharaan',
    //         'jumlahUser',
    //         'peminjamanMenunggu',
    //         'pemeliharaanMenungguPersetujuan',
    //         'barangPerKategori',
    //         'trenPeminjaman',
    //         'statusUnitBarang',
    //         'kondisiUnitBarang',
    //         'peminjamanTerbaruMenunggu',
    //         'pemeliharaanTerbaruDiajukan',
    //         'logAktivitasTerbaru'
    //     ));
    // }

    /**
     * Menampilkan dashboard untuk admin versi 2.
     */
    /**
     * Menampilkan dashboard untuk admin dengan data yang komprehensif.
     */
    public function admin(): View
    {
        // --- KARTU STATISTIK ---
        $jumlahJenisBarang = Barang::count();
        $jumlahUnitBarang = BarangQrCode::whereNull('deleted_at')->count();
        $totalNilaiAset = BarangQrCode::whereNull('deleted_at')->sum('harga_perolehan_unit');
        $jumlahUser = User::count();
        $peminjamanMenunggu = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)->count();
        $jumlahUnitDipinjam = BarangQrCode::where('status', BarangQrCode::STATUS_DIPINJAM)->whereNull('deleted_at')->count();
        $pemeliharaanMenungguPersetujuan = Pemeliharaan::where('status_pengajuan', Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN)->whereNull('deleted_at')->count();
        $jumlahUnitDalamPemeliharaan = BarangQrCode::where('status', BarangQrCode::STATUS_DALAM_PEMELIHARAAN)->whereNull('deleted_at')->count();

        // --- DATA GRAFIK (4 Grafik) ---
        $barangPerKategori = KategoriBarang::withCount(['barangs as jumlah_jenis_barang' => fn($q) => $q->whereNull('deleted_at')])
            ->having('jumlah_jenis_barang', '>', 0)->orderBy('jumlah_jenis_barang', 'desc')->take(5)->get();

        $trenPeminjaman = Peminjaman::select(DB::raw("DATE_FORMAT(tanggal_pengajuan, '%b %Y') as bulan"), DB::raw('COUNT(*) as jumlah'))
            ->where('tanggal_pengajuan', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->groupBy('bulan')->orderByRaw("MIN(tanggal_pengajuan)")->get();

        $statusUnitBarang = BarangQrCode::whereNull('deleted_at')->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')->pluck('jumlah', 'status');

        $kondisiUnitBarang = BarangQrCode::whereNull('deleted_at')->select('kondisi', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('kondisi')->pluck('jumlah', 'kondisi');

        // --- DAFTAR AKTIVITAS TERBARU (3 Daftar) ---
        $peminjamanTerbaruMenunggu = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
            ->with(['guru', 'detailPeminjaman'])->latest('tanggal_pengajuan')->take(5)->get();

        $pemeliharaanTerbaruDiajukan = Pemeliharaan::where('status_pengajuan', Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN)
            ->whereNull('deleted_at')->with('barangQrCode.barang', 'pengaju')->latest('tanggal_pengajuan')->take(5)->get();

        $logAktivitasTerbaru = LogAktivitas::with('user')->latest()->take(7)->get();

        return view('admin.dashboardbaru', compact(
            'jumlahJenisBarang',
            'jumlahUnitBarang',
            'totalNilaiAset',
            'jumlahUser',
            'peminjamanMenunggu',
            'jumlahUnitDipinjam',
            'pemeliharaanMenungguPersetujuan',
            'jumlahUnitDalamPemeliharaan',
            'barangPerKategori',
            'trenPeminjaman',
            'statusUnitBarang',
            'kondisiUnitBarang',
            'peminjamanTerbaruMenunggu',
            'pemeliharaanTerbaruDiajukan',
            'logAktivitasTerbaru'
        ));
    }


    /**
     * Menampilkan dashboard untuk operator dengan data yang komprehensif.
     */
    public function operator(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ambil ID ruangan yang dikelola oleh operator
        $ruanganIds = $user->ruanganYangDiKelola()->pluck('id');

        // --- Statistik Utama (Cards Baris 1) ---
        $jumlahRuanganDikelola = $ruanganIds->count();
        $jumlahUnitDiRuangan = BarangQrCode::whereIn('id_ruangan', $ruanganIds)->whereNull('deleted_at')->count();

        $peminjamanPerluDiproses = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
            ->whereHas('detailPeminjaman.barangQrCode', function ($query) use ($ruanganIds) {
                $query->whereIn('id_ruangan', $ruanganIds);
            })
            ->count();

        $pemeliharaanBaru = Pemeliharaan::where('status_pengajuan', Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN)
            ->whereHas('barangQrCode', function ($query) use ($ruanganIds) {
                $query->whereIn('id_ruangan', $ruanganIds);
            })
            ->whereNull('deleted_at')
            ->count();

        // --- TAMBAHAN: Statistik Aktivitas (Cards Baris 2) ---
        $jumlahUnitDipinjamDiRuangan = BarangQrCode::whereIn('id_ruangan', $ruanganIds)
            ->where('status', \App\Models\BarangQrCode::STATUS_DIPINJAM)->count();

        $jumlahUnitDalamPemeliharaanDiRuangan = BarangQrCode::whereIn('id_ruangan', $ruanganIds)
            ->where('status', \App\Models\BarangQrCode::STATUS_DALAM_PEMELIHARAAN)->count();

        $tugasStokOpnameBerjalan = StokOpname::where('id_operator', $user->id)
            ->where('status', StokOpname::STATUS_DRAFT)->count();


        // --- Data untuk Grafik ---
        $kondisiUnitBarangOperator = BarangQrCode::whereIn('id_ruangan', $ruanganIds)
            ->whereNull('deleted_at')
            ->select('kondisi', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('kondisi')
            ->pluck('jumlah', 'kondisi');

        $statusUnitBarangOperator = BarangQrCode::whereIn('id_ruangan', $ruanganIds)
            ->whereNull('deleted_at')
            ->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status');


        // --- Daftar Tugas Terbaru ---
        $peminjamanTerbaru = Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
            ->whereHas('detailPeminjaman.barangQrCode', function ($query) use ($ruanganIds) {
                $query->whereIn('id_ruangan', $ruanganIds);
            })
            ->with('guru', 'detailPeminjaman')
            ->latest('tanggal_pengajuan')->take(5)->get();

        $pemeliharaanTerbaru = Pemeliharaan::where('status_pengajuan', Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN)
            ->whereHas('barangQrCode', function ($query) use ($ruanganIds) {
                $query->whereIn('id_ruangan', $ruanganIds);
            })
            ->with('barangQrCode.barang', 'pengaju')
            ->latest('tanggal_pengajuan')->take(5)->get();

        // PENYEMPURNAAN: Mengarahkan ke view yang benar dan mengirim semua data
        return view('operator.dashboardbaru', compact(
            'jumlahRuanganDikelola',
            'jumlahUnitDiRuangan',
            'peminjamanPerluDiproses',
            'pemeliharaanBaru',
            'jumlahUnitDipinjamDiRuangan',
            'jumlahUnitDalamPemeliharaanDiRuangan',
            'tugasStokOpnameBerjalan', // Variabel baru
            'kondisiUnitBarangOperator',
            'statusUnitBarangOperator',
            'peminjamanTerbaru',
            'pemeliharaanTerbaru'
        ));
    }

    /**
     * Menampilkan dashboard untuk guru.
     */
    public function guru(): View
    {
        $user = Auth::user();

        // Statistik untuk kartu ringkasan
        $peminjamanAktif = Peminjaman::where('id_guru', $user->id)
            ->where('status', Peminjaman::STATUS_SEDANG_DIPINJAM)
            ->count();

        $pengajuanMenunggu = Peminjaman::where('id_guru', $user->id)
            ->where('status', Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
            ->count();

        // Daftar 5 peminjaman terakhir
        $peminjamanTerbaru = Peminjaman::where('id_guru', $user->id)
            ->withCount('detailPeminjaman')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get();

        // Daftar 5 laporan pemeliharaan terakhir yang dibuat guru
        $pemeliharaanTerbaru = Pemeliharaan::where('id_user_pengaju', $user->id)
            ->with('barangQrCode.barang')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get();

        // Aset yang menjadi tanggung jawab personal guru
        $asetDipegangPersonal = BarangQrCode::where('id_pemegang_personal', $user->id)
            ->with([
                'barang',
                // Eager load relasi pemeliharaan HANYA yang statusnya masih aktif
                'pemeliharaanRecords' => function ($query) {
                    $query->whereIn('status_pengajuan', ['Diajukan', 'Disetujui']);
                }
            ])
            ->whereNull('deleted_at')
            ->get();

        return view('guru.dashboard', compact(
            'peminjamanAktif',
            'pengajuanMenunggu',
            'peminjamanTerbaru',
            'pemeliharaanTerbaru',
            'asetDipegangPersonal'
        ));
    }

    /**
     * Mengarahkan pengguna ke dashboard yang sesuai berdasarkan peran mereka.
     */
    public function redirectDashboard(): RedirectResponse
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user) {
            if ($user->hasRole(User::ROLE_ADMIN)) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
                return redirect()->route('operator.dashboard');
            } elseif ($user->hasRole(User::ROLE_GURU)) {
                return redirect()->route('guru.dashboard');
            } else {
                Auth::logout();
                return redirect()->route('login')->with('error', 'Peran pengguna tidak valid.');
            }
        }
        return redirect()->route('login');
    }
}
