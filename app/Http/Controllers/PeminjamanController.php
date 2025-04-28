<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Barang;
use App\Models\Ruangan;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PeminjamanController extends Controller
{
    // GURU: Lihat daftar pengajuannya sendiri
    public function index()
    {
        $user = Auth::user();

        $peminjaman = Peminjaman::with(['ruangan', 'diprosesOleh', 'detailPeminjaman'])
            ->where('id_peminjam', $user->id)
            ->latest()
            ->get();

        return view('guru.peminjaman.index', compact('peminjaman'));
    }

    // OPERATOR: Lihat semua pengajuan yang perlu diverifikasi
    public function operatorIndex()
    {
        $peminjaman = Peminjaman::with(['ruangan', 'peminjam', 'detailPeminjaman'])
            ->where('status', 'menunggu')
            ->latest()
            ->get();

        return view('operator.peminjaman.index', compact('peminjaman'));
    }

    // GURU: Tampilkan form pengajuan
    public function create(Request $request)
    {
        $query = Barang::query();
        $ruangan = Ruangan::all();

        $query->where('jumlah_barang', '>', 0)
            ->whereIn('keadaan_barang', ['Baik', 'Kurang Baik']);

        if ($request->filled('ruangan_id')) {
            $query->where('id_ruangan', $request->ruangan_id);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_barang', 'like', '%' . $request->keyword . '%')
                    ->orWhere('kode_barang', 'like', '%' . $request->keyword . '%');
            });
        }

        $barang = $query->get();

        return view('guru.peminjaman.create', compact('barang', 'ruangan'));
    }

    // GURU: Simpan pengajuan baru
    public function store(Request $request)
    {
        $request->validate([
            'id_ruangan' => 'required|exists:ruangan,id',
            'tanggal_pinjam' => 'required|date',
            'durasi_pinjam' => 'required|integer|min:1',
            'barang_id' => 'required|array',
            'barang_id.*' => 'exists:barang,id',
            'jumlah' => 'required|array',
        ]);

        $durasi = (int) $request->durasi_pinjam;

        $peminjaman = Peminjaman::create([
            'id_peminjam' => Auth::id(),
            'id_ruangan' => $request->id_ruangan,
            'tanggal_pinjam' => $request->tanggal_pinjam,
            'durasi_pinjam' => $durasi,
            'tanggal_kembali' => now()->parse($request->tanggal_pinjam)->addDays($durasi),
            'dapat_diperpanjang' => true,
            'status' => 'menunggu',
            'keterangan' => $request->keterangan,
        ]);

        foreach ($request->barang_id as $barangId) {
            $jumlah = $request->jumlah[$barangId] ?? 0;

            if ($jumlah < 1) continue;

            DetailPeminjaman::create([
                'id_peminjaman' => $peminjaman->id,
                'id_barang' => $barangId,
                'jumlah_dipinjam' => $jumlah,
                'kondisi_sebelum' => 'Baik',
            ]);
        }

        // Validasi jumlah barang yang dipinjam
        $barang = Barang::findOrFail($barangId);
        if ($jumlah > $barang->jumlah_barang) {
            return redirect()->back()->with('error', "Jumlah pinjam untuk {$barang->nama_barang} melebihi stok.");
        }


        return redirect()->route('peminjaman.index')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    // OPERATOR: Verifikasi pengajuan
    public function verifikasi($id)
    {
        $peminjaman = Peminjaman::with('detailPeminjaman')->where('status', 'menunggu')->findOrFail($id);

        $peminjaman->update([
            'status' => 'dipinjam',
            'diproses_oleh' => Auth::id(),
        ]);

        // Update status detail peminjaman
        foreach ($peminjaman->detailPeminjaman as $detail) {
            $barang = $detail->barang;
            $barang->decrement('jumlah_barang', $detail->jumlah_dipinjam);

            $detail->update([
                'disetujui_oleh' => Auth::id(),
            ]);
        }


        return redirect()->route('operator.peminjaman.index')->with('success', 'Peminjaman telah diverifikasi.');
    }

    // GURU: Ajukan pengembalian
    public function returnRequest(Request $request, $id)
    {
        Log::info('Pengembalian Request:', $request->all());



        $peminjaman = Peminjaman::where('id', $id)
            ->where('id_peminjam', Auth::id())
            ->where('status', 'dipinjam')
            ->with('detailPeminjaman')
            ->first();

        if (!$peminjaman) {
            return redirect()->back()->with('error', 'Data tidak ditemukan atau tidak dapat dikembalikan.');
        }

        DB::beginTransaction();
        try {
            foreach ($peminjaman->detailPeminjaman as $detail) {
                $detail->update([
                    'status_pengembalian' => 'menunggu_verifikasi',
                    'tanggal_pengembalian' => now(),
                    'diperpanjang' => false,
                ]);
            }

            // Update status peminjaman
            $peminjaman->update([
                'status' => 'menunggu_verifikasi_pengembalian',
            ]);

            DB::commit();
            return redirect()->route('peminjaman.index')->with('success', 'Pengembalian berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pengembalian gagal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengajukan pengembalian: ' . $e->getMessage());
        }
    }

    // GURU: Perpanjang peminjaman
    public function perpanjang($id)
    {
        $peminjaman = Peminjaman::where('id', $id)
            ->where('id_peminjam', Auth::id())
            ->where('status', 'dipinjam')
            ->where('dapat_diperpanjang', true)
            ->firstOrFail();

        // Logika perpanjangan, misal tambah 3 hari
        $tambahHari = 3;
        $peminjaman->update([
            'tanggal_kembali' => \Carbon\Carbon::parse($peminjaman->tanggal_kembali)->addDays($tambahHari),
            5
        ]);

        // Tandai semua detail sudah diperpanjang
        foreach ($peminjaman->detailPeminjaman as $detail) {
            $detail->update(['diperpanjang' => true]);
        }

        return redirect()->route('peminjaman.index')->with('success', 'Peminjaman berhasil diperpanjang.');
    }



    //OPERATOR: Tampilkan form verifikasi pengembalian
    public function verifikasiPengembalianForm($id)
    {
        $peminjaman = Peminjaman::with(['ruangan', 'peminjam', 'detailPeminjaman.barang'])
            ->where('id', $id)
            ->whereIn('status', ['dipinjam', 'menunggu_verifikasi_operator'])
            ->firstOrFail(); // ✅

        return view('operator.peminjaman.verifikasi_pengembalian', compact('peminjaman'));
    }

    // OPERATOR: Simpan hasil verifikasi pengembalian
    public function verifikasiPengembalianStore(Request $request, $id)
    {
        $peminjaman = Peminjaman::with('detailPeminjaman')->findOrFail($id);

        DB::beginTransaction();
        try {
            foreach ($request->status_pengembalian as $detailId => $status) {
                $detail = DetailPeminjaman::findOrFail($detailId);
                $jumlahKembali = (int) $request->jumlah_terverifikasi[$detailId];

                // Update detail peminjaman
                $detail->update([
                    'jumlah_terverifikasi' => $jumlahKembali,
                    'status_pengembalian' => $status,
                    'kondisi_setelah' => $request->kondisi_setelah[$detailId],
                    'diverifikasi_oleh' => Auth::id(),
                ]);

                // Kembalikan stok hanya jika status "Dikembalikan"
                if ($status === 'Dikembalikan') {
                    $detail->barang->increment('jumlah_barang', $jumlahKembali);
                }
            }

            // Update status peminjaman
            $peminjaman->update(['status' => 'dikembalikan']);

            DB::commit();

            return redirect()->route('operator.peminjaman.index')
                ->with('success', 'Pengembalian telah diverifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memverifikasi pengembalian: ' . $e->getMessage());
        }
    }


    // OPERATOR: Lihat daftar pengembalian yang menunggu verifikasi
    public function daftarPengembalianMenunggu()
    {
        $peminjaman = Peminjaman::with(['peminjam', 'ruangan', 'detailPeminjaman.barang'])
            ->where('status', 'menunggu_verifikasi_pengembalian') // <- sesuai dengan proses returnRequest
            ->latest()
            ->get(); // <- penting! harus .get()

        return view('operator.peminjaman.verifikasi-pengembalian', compact('peminjaman'));
    }

    public function daftarSedangDipinjam()
    {
        $peminjaman = Peminjaman::with(['ruangan', 'peminjam', 'detailPeminjaman'])
            ->where('status', 'dipinjam')
            ->latest()
            ->get();

        return view('operator.peminjaman.daftar-dipinjam', compact('peminjaman'));
    }






    // Admin: Lihat semua peminjaman
    public function adminIndex()
    {
        $peminjaman = Peminjaman::with(['peminjam', 'ruangan', 'diprosesOleh', 'detailPeminjaman.barang'])
            ->latest()
            ->get();

        return view('admin.peminjaman.index', compact('peminjaman'));
    }
}
