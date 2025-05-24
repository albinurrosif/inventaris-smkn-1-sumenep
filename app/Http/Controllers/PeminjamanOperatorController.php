<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PeminjamanOperatorController extends Controller
{
    /**
     * Menampilkan daftar peminjaman untuk operator
     * Hanya menampilkan peminjaman dari ruangan yang dikelola operator.
     */
    public function index()
    {
        $user = Auth::user();
        $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

        if (empty($ruanganOperatorIds)) {
            $peminjaman = Peminjaman::whereRaw('1=0')->paginate(10); // Tidak ada data
        } else {
            $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
                ->whereHas('detailPeminjaman', function ($query) use ($ruanganOperatorIds) {
                    $query->whereIn('ruangan_asal', $ruanganOperatorIds);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('operator.peminjaman.index', compact('peminjaman'));
    }

    /**
     * Menampilkan detail peminjaman untuk operator
     * Hanya menampilkan detail peminjaman dari ruangan yang dikelola operator.
     */
    public function show($id)
    {
        $user = Auth::user();
        $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan', 'pengajuanDisetujuiOleh'])
            ->findOrFail($id);

        // Filter detail peminjaman berdasarkan ruangan yang dikelola operator
        $peminjaman->detailPeminjaman = $peminjaman->detailPeminjaman->filter(function ($detail) use ($ruanganOperatorIds) {
            return in_array($detail->ruangan_asal, $ruanganOperatorIds);
        });

        // Jika tidak ada detail yang sesuai dengan ruangan operator, tampilkan pesan error
        if ($peminjaman->detailPeminjaman->isEmpty()) {
            abort(403, 'Anda tidak berhak mengakses detail peminjaman ini.');
        }

        return view('operator.peminjaman.show', compact('peminjaman'));
    }

    /**
     * Setujui item peminjaman secara individual
     */
    public function setujuiItem($detailId)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Cari detail peminjaman
            $detail = DetailPeminjaman::with('peminjaman', 'ruanganAsal', 'barang')->findOrFail($detailId);

            // Verifikasi operator mengelola ruangan asal item
            $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($detail->ruangan_asal, $ruanganOperatorIds)) {
                throw new \Exception('Anda tidak berhak menyetujui item ini.');
            }

            // Verifikasi status detail
            if ($detail->status_persetujuan !== 'menunggu_verifikasi') {
                throw new \Exception('Item ini tidak dalam status menunggu verifikasi.');
            }

            // Update status persetujuan
            $detail->status_persetujuan = 'disetujui';
            $detail->status_pengambilan = 'belum_diambil';
            $detail->disetujui_oleh = Auth::id();
            $detail->tanggal_disetujui = now();
            $detail->save();

            // Kurangi stok barang
            $barang = $detail->barang;
            if ($barang->jumlah_barang < $detail->jumlah_dipinjam) {
                throw new \Exception('Stok barang tidak mencukupi.');
            }
            $barang->jumlah_barang -= $detail->jumlah_dipinjam;
            $barang->save();

            // Update status peminjaman
            $this->updateStatusPeminjaman($detail->id_peminjaman);

            DB::commit();
            return redirect()->back()->with('success', 'Item peminjaman berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui item peminjaman: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyetujui item: ' . $e->getMessage());
        }
    }

    /**
     * Tolak item peminjaman secara individual
     */
    public function tolakItem($detailId)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Cari detail peminjaman
            $detail = DetailPeminjaman::with('peminjaman', 'ruanganAsal', 'barang')->findOrFail($detailId);

            // Verifikasi operator mengelola ruangan asal item
            $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($detail->ruangan_asal, $ruanganOperatorIds)) {
                throw new \Exception('Anda tidak berhak menolak item ini.');
            }

            // Verifikasi status detail
            if ($detail->status_persetujuan !== 'menunggu_verifikasi') {
                throw new \Exception('Item ini tidak dalam status menunggu verifikasi.');
            }

            // Update status persetujuan
            $detail->status_persetujuan = 'ditolak';
            $detail->ditolak_oleh = Auth::id();
            $detail->tanggal_ditolak = now();
            $detail->save();

            // Update status peminjaman
            $this->updateStatusPeminjaman($detail->id_peminjaman);

            DB::commit();
            return redirect()->back()->with('success', 'Item peminjaman berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menolak item peminjaman: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menolak item: ' . $e->getMessage());
        }
    }

    /**
     * Setujui semua item peminjaman yang dikelola oleh operator ini
     */
    public function setujuiSemuaItem($peminjamanId)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

            $peminjaman = Peminjaman::findOrFail($peminjamanId);

            // Filter dan setujui detail peminjaman yang dikelola operator
            $detailDikelola = $peminjaman->detailPeminjaman()
                ->whereIn('ruangan_asal', $ruanganOperatorIds)
                ->where('status_persetujuan', 'menunggu_verifikasi')
                ->get();

            if ($detailDikelola->isEmpty()) {
                throw new \Exception('Tidak ada item yang dapat disetujui.');
            }

            foreach ($detailDikelola as $detail) {
                // Verifikasi stok barang
                $barang = Barang::find($detail->id_barang);
                if ($barang->jumlah_barang < $detail->jumlah_dipinjam) {
                    throw new \Exception("Stok barang {$barang->nama_barang} tidak mencukupi.");
                }

                // Update status persetujuan
                $detail->status_persetujuan = 'disetujui';
                $detail->status_pengambilan = 'belum_diambil';
                $detail->disetujui_oleh = Auth::id();
                $detail->tanggal_disetujui = now();
                $detail->save();

                // Kurangi stok barang
                $barang->jumlah_barang -= $detail->jumlah_dipinjam;
                $barang->save();
            }

            // Update status peminjaman
            $this->updateStatusPeminjaman($peminjamanId);

            DB::commit();
            return redirect()->back()->with('success', 'Semua item peminjaman berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui semua item: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyetujui semua item: ' . $e->getMessage());
        }
    }

    /**
     * Konfirmasi pengambilan item peminjaman
     */
    public function konfirmasiPengambilanItem(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Validasi input
            $validator = Validator::make($request->all(), [
                'detail_id' => 'required|exists:detail_peminjaman,id',
                'catatan' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $detailId = $request->input('detail_id');
            $detail = DetailPeminjaman::with('peminjaman', 'ruanganAsal', 'barang')->findOrFail($detailId);

            // Verifikasi operator mengelola ruangan asal item
            $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($detail->ruangan_asal, $ruanganOperatorIds)) {
                throw new \Exception('Anda tidak berhak mengkonfirmasi pengambilan item ini.');
            }

            // Verifikasi status detail
            if ($detail->status_persetujuan != 'disetujui' || $detail->status_pengambilan != 'belum_diambil') {
                throw new \Exception('Item harus berstatus disetujui dan belum diambil.');
            }

            // Update status pengambilan
            $detail->status_pengambilan = 'sudah_diambil';
            $detail->tanggal_dipinjam = now();
            $detail->pengambilan_dikonfirmasi_oleh = Auth::id();
            $detail->tanggal_pengambilan_dikonfirmasi = now();

            // Tambahkan catatan jika ada
            if ($request->has('catatan')) {
                $detail->catatan = $request->catatan;
            }

            $detail->save();

            // Setelah pengambilan dikonfirmasi, update status pengembalian menjadi dipinjam
            $detail->status_pengembalian = 'dipinjam';
            $detail->save();

            // Update status peminjaman
            $this->updateStatusPeminjaman($detail->id_peminjaman);

            DB::commit();
            return redirect()->back()->with('success', 'Pengambilan item berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal konfirmasi pengambilan item: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal konfirmasi pengambilan: ' . $e->getMessage());
        }
    }

    /**
     * Verifikasi pengembalian item peminjaman
     */
    public function verifikasiPengembalianItem(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Validasi input
            $validator = Validator::make($request->all(), [
                'detail_id' => 'required|exists:detail_peminjaman,id',
                'kondisi_setelah' => 'required|in:baik,rusak ringan,rusak berat,hilang',
                'catatan' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $detailId = $request->input('detail_id');
            $detail = DetailPeminjaman::with('peminjaman', 'ruanganAsal', 'barang')->findOrFail($detailId);

            // Verifikasi operator mengelola ruangan asal item
            $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
            if (!in_array($detail->ruangan_asal, $ruanganOperatorIds)) {
                throw new \Exception('Anda tidak berhak memverifikasi pengembalian item ini.');
            }

            // Verifikasi status detail
            if ($detail->status_pengembalian != 'dipinjam') {
                throw new \Exception('Item harus berstatus dipinjam untuk dapat dikembalikan.');
            }

            // Update kondisi barang setelah pengembalian
            $detail->kondisi_setelah = $request->kondisi_setelah;
            $detail->status_pengembalian = 'dikembalikan';
            $detail->tanggal_pengembalian_aktual = now();
            $detail->diverifikasi_oleh_pengembalian = Auth::id();

            // Tambahkan catatan jika ada
            if ($request->has('catatan')) {
                $detail->catatan = $request->catatan;
            }

            $detail->save();

            // Update stok barang berdasarkan kondisi
            $barang = $detail->barang;
            if ($request->kondisi_setelah == 'baik' || $request->kondisi_setelah == 'rusak ringan') {
                // Barang masih bisa digunakan, kembalikan ke stok tersedia
                $barang->jumlah_barang += $detail->jumlah_dipinjam;
                $barang->save();
            } elseif ($request->kondisi_setelah == 'rusak berat') {
                // Barang rusak berat, tambahkan ke stok rusak (jika ada field semacam itu)
                if (Schema::hasColumn('barang', 'stok_rusak')) {
                    $barang->stok_rusak += $detail->jumlah_dipinjam;
                    $barang->save();
                }
            }
            // Untuk kondisi 'hilang', tidak perlu update stok

            // Update status peminjaman
            $this->updateStatusPeminjaman($detail->id_peminjaman);

            DB::commit();
            return redirect()->back()->with('success', 'Pengembalian item berhasil diverifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal verifikasi pengembalian item: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal verifikasi pengembalian: ' . $e->getMessage());
        }
    }

    /**
     * Update status peminjaman berdasarkan status detail peminjaman
     */
    private function updateStatusPeminjaman($peminjamanId)
    {
        $peminjaman = Peminjaman::findOrFail($peminjamanId);
        $detailPeminjaman = DetailPeminjaman::where('id_peminjaman', $peminjamanId)->get();

        // Status persetujuan
        $countTotal = $detailPeminjaman->count();
        $countDisetujui = $detailPeminjaman->where('status_persetujuan', 'disetujui')->count();
        $countDitolak = $detailPeminjaman->where('status_persetujuan', 'ditolak')->count();
        $countMenunggu = $detailPeminjaman->where('status_persetujuan', 'menunggu_verifikasi')->count();

        if ($countTotal == $countDisetujui) {
            $peminjaman->status_persetujuan = 'disetujui';
            $peminjaman->tanggal_disetujui = now();
        } elseif ($countTotal == $countDitolak) {
            $peminjaman->status_persetujuan = 'ditolak';
        } elseif ($countDisetujui > 0 && $countDitolak > 0) {
            $peminjaman->status_persetujuan = 'sebagian_disetujui';
        } elseif ($countMenunggu > 0 && ($countDisetujui > 0 || $countDitolak > 0)) {
            $peminjaman->status_persetujuan = 'diproses';
        } else {
            $peminjaman->status_persetujuan = 'menunggu_verifikasi';
        }

        // Status pengambilan
        $countBelumDiambil = $detailPeminjaman->where('status_persetujuan', 'disetujui')
            ->where('status_pengambilan', 'belum_diambil')->count();
        $countSudahDiambil = $detailPeminjaman->where('status_persetujuan', 'disetujui')
            ->where('status_pengambilan', 'sudah_diambil')->count();

        if ($countDisetujui > 0) {
            if ($countBelumDiambil == 0 && $countSudahDiambil > 0) {
                $peminjaman->status_pengambilan = 'sudah_diambil';
                $peminjaman->tanggal_semua_diambil = now();
            } elseif ($countBelumDiambil > 0 && $countSudahDiambil > 0) {
                $peminjaman->status_pengambilan = 'sebagian_diambil';
            } else {
                $peminjaman->status_pengambilan = 'belum_diambil';
            }
        }

        // Status pengembalian
        $countDipinjam = $detailPeminjaman->where('status_pengambilan', 'sudah_diambil')
            ->where('status_pengembalian', 'dipinjam')->count();
        $countDikembalikan = $detailPeminjaman->where('status_pengambilan', 'sudah_diambil')
            ->where('status_pengembalian', 'dikembalikan')->count();

        if ($countSudahDiambil > 0) {
            if ($countDipinjam == 0 && $countDikembalikan > 0) {
                $peminjaman->status_pengembalian = 'sudah_dikembalikan';
                $peminjaman->tanggal_selesai = now();
            } elseif ($countDipinjam > 0 && $countDikembalikan > 0) {
                $peminjaman->status_pengembalian = 'sebagian_dikembalikan';
            } else {
                $peminjaman->status_pengembalian = 'belum_dikembalikan';
            }
        }

        $peminjaman->save();
    }

    /**
     * Menampilkan daftar peminjaman yang menunggu pengembalian
     * untuk operator ruangan
     */
    public function daftarPengembalianMenunggu()
    {
        $user = Auth::user();
        $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

        if (empty($ruanganOperatorIds)) {
            $peminjaman = Peminjaman::whereRaw('1=0')->paginate(10); // Tidak ada data
        } else {
            $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal'])
                ->whereHas('detailPeminjaman', function ($query) use ($ruanganOperatorIds) {
                    $query->whereIn('ruangan_asal', $ruanganOperatorIds)
                        ->where('status_pengembalian', 'dipinjam')
                        ->where('status_pengambilan', 'sudah_diambil');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('operator.peminjaman.pengembalian', compact('peminjaman'));
    }

    /**
     * Menampilkan daftar peminjaman yang sedang berlangsung
     * untuk operator ruangan
     */
    public function peminjamanBerlangsungOperator()
    {
        $user = Auth::user();
        $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

        if (empty($ruanganOperatorIds)) {
            $peminjamanBerlangsung = Peminjaman::whereRaw('1=0')->paginate(10); // Tidak ada data
        } else {
            $today = Carbon::now();

            $peminjamanBerlangsung = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal'])
                ->whereHas('detailPeminjaman', function ($query) use ($ruanganOperatorIds, $today) {
                    $query->whereIn('ruangan_asal', $ruanganOperatorIds)
                        ->where('status_pengembalian', 'dipinjam')
                        ->where('status_pengambilan', 'sudah_diambil');
                })
                ->whereIn('status_persetujuan', ['disetujui', 'sebagian_disetujui'])
                ->whereIn('status_pengambilan', ['sebagian_diambil', 'sudah_diambil'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('operator.peminjaman.sedang-berlangsung', compact('peminjamanBerlangsung'));
    }

    /**
     * Menampilkan daftar item peminjaman yang telah lewat jatuh tempo
     * untuk operator ruangan
     */
    public function daftarItemTerlambat()
    {
        $user = Auth::user();
        $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();
        $today = Carbon::now();

        if (empty($ruanganOperatorIds)) {
            $itemTerlambat = DetailPeminjaman::whereRaw('1=0')->paginate(10); // Tidak ada data
        } else {
            $itemTerlambat = DetailPeminjaman::with(['peminjaman.peminjam', 'barang', 'ruanganAsal'])
                ->whereIn('ruangan_asal', $ruanganOperatorIds)
                ->where('status_pengembalian', 'dipinjam')
                ->where('status_pengambilan', 'sudah_diambil')
                ->where('tanggal_kembali', '<', $today)
                ->orderBy('tanggal_kembali', 'asc')
                ->paginate(10);
        }

        return view('operator.peminjaman.item-terlambat', compact('itemTerlambat'));
    }

    /**
     * Laporan statistik peminjaman untuk ruangan yang dikelola operator
     */
    public function laporanPeminjaman()
    {
        $user = Auth::user();
        $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

        if (empty($ruanganOperatorIds)) {
            return view('operator.laporan.peminjaman', [
                'ruangan' => [],
                'totalPeminjaman' => 0,
                'peminjamanBerlangsung' => 0,
                'peminjamanSelesai' => 0,
                'itemTerlambat' => 0,
                'barangTerpopuler' => []
            ]);
        }

        // Data untuk laporan
        $ruangan = Ruangan::whereIn('id', $ruanganOperatorIds)->get();
        $today = Carbon::now();

        // Total peminjaman yang pernah melibatkan ruangan operator
        $totalPeminjaman = Peminjaman::whereHas('detailPeminjaman', function ($query) use ($ruanganOperatorIds) {
            $query->whereIn('ruangan_asal', $ruanganOperatorIds);
        })->count();

        // Peminjaman yang sedang berlangsung
        $peminjamanBerlangsung = Peminjaman::whereHas('detailPeminjaman', function ($query) use ($ruanganOperatorIds) {
            $query->whereIn('ruangan_asal', $ruanganOperatorIds)
                ->where('status_pengembalian', 'dipinjam')
                ->where('status_pengambilan', 'sudah_diambil');
        })->count();

        // Peminjaman yang sudah selesai
        $peminjamanSelesai = Peminjaman::whereIn('status_pengembalian', ['sudah_dikembalikan'])
            ->whereHas('detailPeminjaman', function ($query) use ($ruanganOperatorIds) {
                $query->whereIn('ruangan_asal', $ruanganOperatorIds);
            })->count();

        // Item yang terlambat dikembalikan
        $itemTerlambat = DetailPeminjaman::whereIn('ruangan_asal', $ruanganOperatorIds)
            ->where('status_pengembalian', 'dipinjam')
            ->where('status_pengambilan', 'sudah_diambil')
            ->where('tanggal_kembali', '<', $today)
            ->count();

        // Barang terpopuler dari ruangan operator
        $barangTerpopuler = DB::table('detail_peminjaman')
            ->join('barang', 'detail_peminjaman.id_barang', '=', 'barang.id')
            ->join('ruangan', 'detail_peminjaman.ruangan_asal', '=', 'ruangan.id')
            ->whereIn('detail_peminjaman.ruangan_asal', $ruanganOperatorIds)
            ->where('detail_peminjaman.status_persetujuan', 'disetujui')
            ->select(DB::raw('barang.nama as nama_barang, ruangan.nama as nama_ruangan, COUNT(*) as jumlah_peminjaman'))
            ->groupBy('detail_peminjaman.id_barang', 'barang.nama', 'ruangan.nama')
            ->orderBy('jumlah_peminjaman', 'desc')
            ->limit(5)
            ->get();

        return view('operator.laporan.peminjaman', compact(
            'ruangan',
            'totalPeminjaman',
            'peminjamanBerlangsung',
            'peminjamanSelesai',
            'itemTerlambat',
            'barangTerpopuler'
        ));
    }
}
