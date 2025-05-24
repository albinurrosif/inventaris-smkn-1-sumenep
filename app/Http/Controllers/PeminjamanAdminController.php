<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PeminjamanAdminController extends Controller
{
    /**
     * Menampilkan daftar peminjaman untuk admin dengan filter dan pencarian yang ditingkatkan
     */
    public function index(Request $request)
    {
        $query = Peminjaman::with([
            'peminjam',
            'detailPeminjaman.barang',
            'detailPeminjaman.ruanganAsal',
            'detailPeminjaman.ruanganTujuan',
            'pengajuanDisetujuiOleh',
            'pengajuanDitolakOleh'
        ]);

        // Filter berdasarkan status persetujuan
        if ($request->has('status_persetujuan') && $request->status_persetujuan) {
            $query->where('status_persetujuan', $request->status_persetujuan);
        }

        // Filter berdasarkan status pengambilan
        if ($request->has('status_pengambilan') && $request->status_pengambilan) {
            $query->where('status_pengambilan', $request->status_pengambilan);
        }

        // Filter berdasarkan status pengembalian
        if ($request->has('status_pengembalian') && $request->status_pengembalian) {
            $query->where('status_pengembalian', $request->status_pengembalian);
        }

        // Filter untuk peminjaman terlambat
        if ($request->has('terlambat') && $request->terlambat) {
            $query->whereHas('detailPeminjaman', function ($q) {
                $q->where('status_pengembalian', 'dipinjam')
                    ->where('tanggal_kembali', '<', now());
            });
        }

        // Filter berdasarkan peminjam (nama atau ID)
        if ($request->has('peminjam') && $request->peminjam) {
            $query->whereHas('peminjam', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->peminjam . '%')
                    ->orWhere('id', $request->peminjam);
            });
        }

        // Filter berdasarkan barang yang dipinjam
        if ($request->has('barang') && $request->barang) {
            $query->whereHas('detailPeminjaman.barang', function ($q) use ($request) {
                $q->where('nama_barang', 'like', '%' . $request->barang . '%')
                    ->orWhere('kode_barang', 'like', '%' . $request->barang . '%');
            });
        }

        // Filter berdasarkan ruangan asal
        if ($request->has('ruangan_asal') && $request->ruangan_asal) {
            $query->whereHas('detailPeminjaman', function ($q) use ($request) {
                $q->where('ruangan_asal', $request->ruangan_asal);
            });
        }

        // Filter berdasarkan ruangan tujuan
        if ($request->has('ruangan_tujuan') && $request->ruangan_tujuan) {
            $query->whereHas('detailPeminjaman', function ($q) use ($request) {
                $q->where('ruangan_tujuan', $request->ruangan_tujuan);
            });
        }

        // Filter berdasarkan rentang tanggal pengajuan
        if ($request->has('tanggal_mulai') && $request->tanggal_mulai) {
            $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
            $query->where('tanggal_pengajuan', '>=', $tanggalMulai);
        }

        if ($request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAkhir = Carbon::parse($request->tanggal_akhir)->endOfDay();
            $query->where('tanggal_pengajuan', '<=', $tanggalAkhir);
        }

        // Filter berdasarkan rentang tanggal pinjam
        if ($request->has('tanggal_pinjam_mulai') && $request->tanggal_pinjam_mulai) {
            $query->whereHas('detailPeminjaman', function ($q) use ($request) {
                $q->where('tanggal_pinjam', '>=', Carbon::parse($request->tanggal_pinjam_mulai)->startOfDay());
            });
        }

        if ($request->has('tanggal_pinjam_akhir') && $request->tanggal_pinjam_akhir) {
            $query->whereHas('detailPeminjaman', function ($q) use ($request) {
                $q->where('tanggal_pinjam', '<=', Carbon::parse($request->tanggal_pinjam_akhir)->endOfDay());
            });
        }

        // Pengurutan data
        $sortField = $request->input('sort_by', 'tanggal_pengajuan');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validasi field pengurutan untuk menghindari SQL injection
        $allowedSortFields = [
            'id',
            'tanggal_pengajuan',
            'status_persetujuan',
            'status_pengambilan',
            'status_pengembalian',
            'tanggal_disetujui',
            'tanggal_semua_diambil',
            'tanggal_selesai'
        ];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('tanggal_pengajuan', 'desc');
        }

        // Jumlah item per halaman
        $perPage = $request->input('per_page', 10);
        $peminjaman = $query->paginate($perPage)
            ->appends($request->all());

        // Data untuk dropdown filter
        $statusPersetujuan = ['menunggu_verifikasi', 'diproses', 'disetujui', 'ditolak', 'sebagian_disetujui'];
        $statusPengambilan = ['belum_diambil', 'sebagian_diambil', 'sudah_diambil'];
        $statusPengembalian = ['belum_dikembalikan', 'sebagian_dikembalikan', 'sudah_dikembalikan'];

        // Ambil data ruangan untuk filter
        $ruangan = Ruangan::select('id', 'nama_ruangan')->orderBy('nama_ruangan')->get();

        // Tambahkan ringkasan
        $totalPeminjaman = Peminjaman::count();
        $menungguVerifikasi = Peminjaman::where('status_persetujuan', 'menunggu_verifikasi')->count();
        $disetujui = Peminjaman::where('status_persetujuan', 'disetujui')->count();
        $ditolak = Peminjaman::where('status_persetujuan', 'ditolak')->count();
        $belumDikembalikan = Peminjaman::where('status_pengembalian', 'belum_dikembalikan')->count();
        $terlambat = Peminjaman::whereHas('detailPeminjaman', function ($q) {
            $q->where('status_pengembalian', 'dipinjam')
                ->where('tanggal_kembali', '<', now());
        })->count();

        $ringkasan = [
            'total' => $totalPeminjaman,
            'menunggu_verifikasi' => $menungguVerifikasi,
            'disetujui' => $disetujui,
            'ditolak' => $ditolak,
            'belum_dikembalikan' => $belumDikembalikan,
            'terlambat' => $terlambat
        ];

        return view('admin.peminjaman.index', compact(
            'peminjaman',
            'statusPersetujuan',
            'statusPengambilan',
            'statusPengembalian',
            'ruangan',
            'ringkasan'
        ));
    }

    /**
     * Menampilkan detail peminjaman untuk admin
     */
    public function show($id)
    {
        $peminjaman = Peminjaman::with([
            'peminjam',
            'detailPeminjaman.barang',
            'detailPeminjaman.ruanganAsal',
            'detailPeminjaman.ruanganTujuan',
            'detailPeminjaman.disetujuiOleh',
            'detailPeminjaman.ditolakOleh',
            'detailPeminjaman.pengambilanDikonfirmasiOleh',
            'detailPeminjaman.disetujuiOlehPengembalian',
            'detailPeminjaman.diverifikasiOlehPengembalian',
            'pengajuanDisetujuiOleh',
            'pengajuanDitolakOleh'
        ])->findOrFail($id);

        // Cek status terlambat untuk setiap item
        foreach ($peminjaman->detailPeminjaman as $detail) {
            $detail->is_terlambat = $detail->terlambat;
            $detail->jumlah_hari_terlambat = $detail->jumlahHariTerlambat;
        }

        return view('admin.peminjaman.show', compact('peminjaman'));
    }

    /**
     * Tampilkan laporan peminjaman
     */
    public function report(Request $request)
    {
        $query = Peminjaman::with([
            'peminjam',
            'detailPeminjaman.barang',
            'detailPeminjaman.ruanganAsal',
            'detailPeminjaman.ruanganTujuan'
        ]);

        // Filter berdasarkan tanggal
        if ($request->has('tanggal_mulai') && $request->tanggal_mulai) {
            $query->where('tanggal_pengajuan', '>=', Carbon::parse($request->tanggal_mulai)->startOfDay());
        }

        if ($request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $query->where('tanggal_pengajuan', '<=', Carbon::parse($request->tanggal_akhir)->endOfDay());
        }

        // Filter berdasarkan status persetujuan
        if ($request->has('status_persetujuan') && $request->status_persetujuan) {
            $query->where('status_persetujuan', $request->status_persetujuan);
        }

        // Filter berdasarkan status pengambilan
        if ($request->has('status_pengambilan') && $request->status_pengambilan) {
            $query->where('status_pengambilan', $request->status_pengambilan);
        }

        // Filter berdasarkan status pengembalian
        if ($request->has('status_pengembalian') && $request->status_pengembalian) {
            $query->where('status_pengembalian', $request->status_pengembalian);
        }

        // Filter berdasarkan peminjam
        if ($request->has('peminjam') && $request->peminjam) {
            $query->whereHas('peminjam', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->peminjam . '%');
            });
        }

        // Filter berdasarkan barang
        if ($request->has('barang') && $request->barang) {
            $query->whereHas('detailPeminjaman.barang', function ($q) use ($request) {
                $q->where('nama_barang', 'like', '%' . $request->barang . '%')
                    ->orWhere('kode_barang', 'like', '%' . $request->barang . '%');
            });
        }

        $peminjamanList = $query->orderBy('created_at', 'desc')->get();

        // Perhitungan untuk ringkasan laporan
        $totalPeminjaman = $peminjamanList->count();
        $totalBarang = $peminjamanList->sum(function ($peminjaman) {
            return $peminjaman->totalBarang;
        });

        $totalTerlambat = $peminjamanList->filter(function ($peminjaman) {
            return $peminjaman->adaItemTerlambat;
        })->count();

        $rusak = 0;
        $hilang = 0;

        foreach ($peminjamanList as $peminjaman) {
            foreach ($peminjaman->detailPeminjaman as $detail) {
                if ($detail->status_pengembalian === 'rusak') {
                    $rusak += $detail->jumlah_terverifikasi ?? $detail->jumlah_dipinjam;
                }
                if ($detail->status_pengembalian === 'hilang') {
                    $hilang += $detail->jumlah_terverifikasi ?? $detail->jumlah_dipinjam;
                }
            }
        }

        // Mengelompokkan peminjaman berdasarkan status
        $statusGroups = [
            'menunggu_verifikasi' => $peminjamanList->where('status_persetujuan', 'menunggu_verifikasi')->count(),
            'disetujui' => $peminjamanList->where('status_persetujuan', 'disetujui')->count(),
            'ditolak' => $peminjamanList->where('status_persetujuan', 'ditolak')->count(),
            'sebagian_disetujui' => $peminjamanList->where('status_persetujuan', 'sebagian_disetujui')->count(),
        ];

        $report = [
            'totalPeminjaman' => $totalPeminjaman,
            'totalBarang' => $totalBarang,
            'totalTerlambat' => $totalTerlambat,
            'rusak' => $rusak,
            'hilang' => $hilang,
            'statusGroups' => $statusGroups
        ];

        // Data untuk dropdown filter
        $statusPersetujuan = ['menunggu_verifikasi', 'diproses', 'disetujui', 'ditolak', 'sebagian_disetujui'];
        $statusPengambilan = ['belum_diambil', 'sebagian_diambil', 'sudah_diambil'];
        $statusPengembalian = ['belum_dikembalikan', 'sebagian_dikembalikan', 'sudah_dikembalikan'];

        return view('admin.peminjaman.report', compact(
            'peminjamanList',
            'report',
            'statusPersetujuan',
            'statusPengambilan',
            'statusPengembalian'
        ));
    }

    /**
     * Tampilkan daftar peminjaman yang terlambat
     */
    public function overdue(Request $request)
    {
        $query = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal'])
            ->whereHas('detailPeminjaman', function ($query) {
                $query->where('status_pengembalian', 'dipinjam')
                    ->where('tanggal_kembali', '<', now());
            });

        // Filter berdasarkan peminjam
        if ($request->has('peminjam') && $request->peminjam) {
            $query->whereHas('peminjam', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->peminjam . '%');
            });
        }

        // Filter berdasarkan level keterlambatan
        if ($request->has('level_terlambat')) {
            $today = Carbon::now();

            switch ($request->level_terlambat) {
                case '1-7':
                    $query->whereHas('detailPeminjaman', function ($q) use ($today) {
                        $q->where('status_pengembalian', 'dipinjam')
                            ->where('tanggal_kembali', '>=', $today->copy()->subDays(7))
                            ->where('tanggal_kembali', '<', $today);
                    });
                    break;
                case '8-14':
                    $query->whereHas('detailPeminjaman', function ($q) use ($today) {
                        $q->where('status_pengembalian', 'dipinjam')
                            ->where('tanggal_kembali', '>=', $today->copy()->subDays(14))
                            ->where('tanggal_kembali', '<', $today->copy()->subDays(7));
                    });
                    break;
                case '15+':
                    $query->whereHas('detailPeminjaman', function ($q) use ($today) {
                        $q->where('status_pengembalian', 'dipinjam')
                            ->where('tanggal_kembali', '<', $today->copy()->subDays(14));
                    });
                    break;
            }
        }

        $peminjaman = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->all());

        // Hitung jumlah hari terlambat untuk setiap detail
        foreach ($peminjaman as $p) {
            foreach ($p->detailPeminjaman as $detail) {
                if ($detail->status_pengembalian === 'dipinjam' && Carbon::now()->gt($detail->tanggal_kembali)) {
                    $detail->hari_terlambat = Carbon::now()->diffInDays($detail->tanggal_kembali);
                }
            }
        }

        // Informasi ringkasan
        $totalTerlambat = $query->count();
        $terlambat1_7 = Peminjaman::whereHas('detailPeminjaman', function ($q) {
            $q->where('status_pengembalian', 'dipinjam')
                ->where('tanggal_kembali', '>=', Carbon::now()->subDays(7))
                ->where('tanggal_kembali', '<', Carbon::now());
        })->count();

        $terlambat8_14 = Peminjaman::whereHas('detailPeminjaman', function ($q) {
            $q->where('status_pengembalian', 'dipinjam')
                ->where('tanggal_kembali', '>=', Carbon::now()->subDays(14))
                ->where('tanggal_kembali', '<', Carbon::now()->subDays(7));
        })->count();

        $terlambat15Plus = Peminjaman::whereHas('detailPeminjaman', function ($q) {
            $q->where('status_pengembalian', 'dipinjam')
                ->where('tanggal_kembali', '<', Carbon::now()->subDays(14));
        })->count();

        $ringkasan = [
            'total' => $totalTerlambat,
            '1-7_hari' => $terlambat1_7,
            '8-14_hari' => $terlambat8_14,
            '15+_hari' => $terlambat15Plus
        ];

        return view('admin.peminjaman.overdue', compact('peminjaman', 'ringkasan'));
    }

    /**
     * Export peminjaman ke PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Peminjaman::with([
            'peminjam',
            'detailPeminjaman.barang',
            'detailPeminjaman.ruanganAsal',
            'detailPeminjaman.ruanganTujuan'
        ]);

        // Terapkan filter yang sama dengan method report
        if ($request->has('tanggal_mulai') && $request->tanggal_mulai) {
            $query->where('tanggal_pengajuan', '>=', Carbon::parse($request->tanggal_mulai)->startOfDay());
        }

        if ($request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $query->where('tanggal_pengajuan', '<=', Carbon::parse($request->tanggal_akhir)->endOfDay());
        }

        if ($request->has('status_persetujuan') && $request->status_persetujuan) {
            $query->where('status_persetujuan', $request->status_persetujuan);
        }

        if ($request->has('status_pengambilan') && $request->status_pengambilan) {
            $query->where('status_pengambilan', $request->status_pengambilan);
        }

        if ($request->has('status_pengembalian') && $request->status_pengembalian) {
            $query->where('status_pengembalian', $request->status_pengembalian);
        }

        // Filter berdasarkan peminjam
        if ($request->has('peminjam') && $request->peminjam) {
            $query->whereHas('peminjam', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->peminjam . '%');
            });
        }

        // Filter berdasarkan barang
        if ($request->has('barang') && $request->barang) {
            $query->whereHas('detailPeminjaman.barang', function ($q) use ($request) {
                $q->where('nama_barang', 'like', '%' . $request->barang . '%')
                    ->orWhere('kode_barang', 'like', '%' . $request->barang . '%');
            });
        }

        $peminjaman = $query->orderBy('created_at', 'desc')->get();

        // Perhitungan untuk ringkasan laporan
        $totalPeminjaman = $peminjaman->count();
        $totalBarang = $peminjaman->sum(function ($item) {
            return $item->totalBarang;
        });

        $totalTerlambat = $peminjaman->filter(function ($item) {
            return $item->adaItemTerlambat;
        })->count();

        $rusak = 0;
        $hilang = 0;

        foreach ($peminjaman as $item) {
            foreach ($item->detailPeminjaman as $detail) {
                if ($detail->status_pengembalian === 'rusak') {
                    $rusak += $detail->jumlah_terverifikasi ?? $detail->jumlah_dipinjam;
                }
                if ($detail->status_pengembalian === 'hilang') {
                    $hilang += $detail->jumlah_terverifikasi ?? $detail->jumlah_dipinjam;
                }
            }
        }

        // Mengelompokkan peminjaman berdasarkan status
        $statusGroups = [
            'menunggu_verifikasi' => $peminjaman->where('status_persetujuan', 'menunggu_verifikasi')->count(),
            'disetujui' => $peminjaman->where('status_persetujuan', 'disetujui')->count(),
            'ditolak' => $peminjaman->where('status_persetujuan', 'ditolak')->count(),
            'sebagian_disetujui' => $peminjaman->where('status_persetujuan', 'sebagian_disetujui')->count(),
        ];

        $summary = [
            'totalPeminjaman' => $totalPeminjaman,
            'totalBarang' => $totalBarang,
            'totalTerlambat' => $totalTerlambat,
            'rusak' => $rusak,
            'hilang' => $hilang,
            'statusGroups' => $statusGroups
        ];

        // Tentukan periode laporan untuk judul
        $periodText = 'Semua Periode';
        if ($request->has('tanggal_mulai') && $request->tanggal_mulai) {
            $periodText = 'Periode ' . Carbon::parse($request->tanggal_mulai)->format('d M Y');

            if ($request->has('tanggal_akhir') && $request->tanggal_akhir) {
                $periodText .= ' s/d ' . Carbon::parse($request->tanggal_akhir)->format('d M Y');
            } else {
                $periodText .= ' s/d Sekarang';
            }
        }

        $title = 'Laporan Peminjaman Barang ' . $periodText;
        $date = Carbon::now()->format('d F Y');

        // Generate PDF
        $pdf = PDF::loadView('admin.peminjaman.pdf', compact(
            'peminjaman',
            'summary',
            'title',
            'date'
        ));

        return $pdf->download('laporan-peminjaman-' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export peminjaman ke Excel
     */
    // public function exportExcel(Request $request)
    // {
    //     $fileName = 'laporan-peminjaman-' . Carbon::now()->format('Y-m-d') . '.xlsx';

    //     return Excel::download(new PeminjamanExport($request->all()), $fileName);
    // }

    /**
     * Export peminjaman yang terlambat ke PDF
     */
    public function exportOverduePdf(Request $request)
    {
        $query = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal'])
            ->whereHas('detailPeminjaman', function ($query) {
                $query->where('status_pengembalian', 'dipinjam')
                    ->where('tanggal_kembali', '<', now());
            });

        // Filter berdasarkan peminjam
        if ($request->has('peminjam') && $request->peminjam) {
            $query->whereHas('peminjam', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->peminjam . '%');
            });
        }

        // Filter berdasarkan level keterlambatan
        if ($request->has('level_terlambat')) {
            $today = Carbon::now();

            switch ($request->level_terlambat) {
                case '1-7':
                    $query->whereHas('detailPeminjaman', function ($q) use ($today) {
                        $q->where('status_pengembalian', 'dipinjam')
                            ->where('tanggal_kembali', '>=', $today->copy()->subDays(7))
                            ->where('tanggal_kembali', '<', $today);
                    });
                    break;
                case '8-14':
                    $query->whereHas('detailPeminjaman', function ($q) use ($today) {
                        $q->where('status_pengembalian', 'dipinjam')
                            ->where('tanggal_kembali', '>=', $today->copy()->subDays(14))
                            ->where('tanggal_kembali', '<', $today->copy()->subDays(7));
                    });
                    break;
                case '15+':
                    $query->whereHas('detailPeminjaman', function ($q) use ($today) {
                        $q->where('status_pengembalian', 'dipinjam')
                            ->where('tanggal_kembali', '<', $today->copy()->subDays(14));
                    });
                    break;
            }
        }

        $peminjaman = $query->orderBy('created_at', 'desc')->get();

        // Hitung jumlah hari terlambat untuk setiap detail
        foreach ($peminjaman as $p) {
            foreach ($p->detailPeminjaman as $detail) {
                if ($detail->status_pengembalian === 'dipinjam' && Carbon::now()->gt($detail->tanggal_kembali)) {
                    $detail->hari_terlambat = Carbon::now()->diffInDays($detail->tanggal_kembali);
                }
            }
        }

        // Informasi ringkasan
        $totalTerlambat = $peminjaman->count();
        $terlambat1_7 = $peminjaman->filter(function ($p) {
            return $p->detailPeminjaman->contains(function ($detail) {
                return $detail->status_pengembalian === 'dipinjam'
                    && Carbon::now()->diffInDays($detail->tanggal_kembali) <= 7;
            });
        })->count();

        $terlambat8_14 = $peminjaman->filter(function ($p) {
            return $p->detailPeminjaman->contains(function ($detail) {
                $days = Carbon::now()->diffInDays($detail->tanggal_kembali);
                return $detail->status_pengembalian === 'dipinjam'
                    && $days > 7 && $days <= 14;
            });
        })->count();

        $terlambat15Plus = $peminjaman->filter(function ($p) {
            return $p->detailPeminjaman->contains(function ($detail) {
                return $detail->status_pengembalian === 'dipinjam'
                    && Carbon::now()->diffInDays($detail->tanggal_kembali) > 14;
            });
        })->count();

        $ringkasan = [
            'total' => $totalTerlambat,
            '1-7_hari' => $terlambat1_7,
            '8-14_hari' => $terlambat8_14,
            '15+_hari' => $terlambat15Plus
        ];

        $title = 'Laporan Peminjaman Terlambat';
        $date = Carbon::now()->format('d F Y');
        $pdf = PDF::loadView('admin.peminjaman.overdue-pdf', compact(
            'peminjaman',
            'ringkasan',
            'title',
            'date'
        ));

        return $pdf->download('laporan-peminjaman-terlambat-' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export peminjaman yang terlambat ke Excel
     */
    // public function exportOverdueExcel(Request $request)
    // {
    //     $fileName = 'laporan-peminjaman-terlambat-' . Carbon::now()->format('Y-m-d') . '.xlsx';

    //     return Excel::download(new PeminjamanTerlambatExport($request->all()), $fileName);
    // }
}
