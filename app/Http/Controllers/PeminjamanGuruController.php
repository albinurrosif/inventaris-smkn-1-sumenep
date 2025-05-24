<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class PeminjamanGuruController extends Controller
{
    /**
     * Menampilkan daftar peminjaman untuk guru
     */
    public function index()
    {
        $user = Auth::user();

        $peminjaman = Peminjaman::with(['detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
            ->where('id_peminjam', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('guru.peminjaman.index', compact('peminjaman'));
    }

    /**
     * Menampilkan form untuk membuat peminjaman baru
     */
    public function create()
    {
        $ruangan = Ruangan::all();
        $barang = Barang::all();
        return view('guru.peminjaman.create', compact('ruangan', 'barang'));
    }

    /**
     * Menyimpan peminjaman baru ke database (DIUBAH UNTUK PEMISAHAN OTOMATIS)
     */
    public function store(Request $request)
    {
        // Mengambil data 'items' yang dikirimkan sebagai string JSON
        $items = json_decode($request->input('items'), true);

        // Jika json_decode gagal (data tidak valid), kita bisa memberi pesan error
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('error', 'Data items tidak valid.');
        }

        // Validasi minimal untuk memastikan item ada
        if (empty($items)) {
            return back()->with('error', 'Tidak ada barang yang dipilih untuk dipinjam.');
        }

        $user = Auth::user();

        // Kelompokkan item berdasarkan ruangan asal
        $itemsGrouped = collect($items)->groupBy('ruangan_asal');

        // Get the keterangan from the request
        $keterangan = $request->input('keterangan');

        DB::beginTransaction();
        try {
            foreach ($itemsGrouped as $ruanganId => $groupedItems) {
                $first = $groupedItems->first(); // Asumsi satu tanggal pinjam/kembali per peminjaman

                // Buat peminjaman untuk setiap ruangan
                $peminjaman = Peminjaman::create([
                    'id_peminjam' => $user->id,
                    'tanggal_pengajuan' => now(),
                    'status_persetujuan' => 'menunggu_verifikasi', // Status awal
                    'status_pengambilan' => 'belum_diambil', // Status pengambilan awal
                    'status_pengembalian' => 'belum_dikembalikan', // Status pengembalian awal
                    'pengajuan_disetujui_oleh' => null,
                    'keterangan' => $keterangan, // Set the keterangan value from form
                ]);

                // Proses detail peminjaman untuk setiap item
                foreach ($groupedItems as $item) {
                    DetailPeminjaman::create([
                        'id_peminjaman' => $peminjaman->id,
                        'id_barang' => $item['barang_id'],
                        'jumlah_dipinjam' => $item['jumlah'],
                        'status_persetujuan' => 'menunggu_verifikasi', // Status persetujuan awal
                        'status_pengambilan' => 'belum_diambil', // Status pengambilan awal
                        'status_pengembalian' => 'dipinjam', // Status pengembalian awal
                        'ruangan_asal' => $item['ruangan_asal'],
                        'ruangan_tujuan' => $item['ruangan_tujuan'],
                        'tanggal_pinjam' => $item['tanggal_pinjam'],
                        'tanggal_kembali' => $item['tanggal_kembali'],
                        'durasi_pinjam' => $item['durasi_pinjam'],
                        'dapat_diperpanjang' => true, // Default: bisa diperpanjang
                        'diperpanjang' => false,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('guru.peminjaman.index')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            return back()->with('error', 'Terjadi kesalahan saat mengajukan peminjaman: ' . $th->getMessage());
        }
    }

    private function calculateDurasi($tanggalPinjam, $tanggalKembali)
    {
        $date1 = Carbon::parse($tanggalPinjam);
        $date2 = Carbon::parse($tanggalKembali);

        return $date1->diffInDays($date2);
    }

    /**
     * Menampilkan detail peminjaman
     */
    public function show($id)
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan', 'pengajuanDisetujuiOleh'])
            ->findOrFail($id);

        return view('guru.peminjaman.show', compact('peminjaman'));
    }

    /**
     * Menghapus item dari peminjaman (hanya jika statusnya "menunggu_verifikasi")
     */
    public function destroy($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $detailId = $request->input('detail_id');

            $detail = DetailPeminjaman::findOrFail($detailId);
            $peminjaman = Peminjaman::findOrFail($detail->id_peminjaman);

            if ($peminjaman->status_persetujuan != 'menunggu_verifikasi') {
                throw new \Exception('Hanya dapat menghapus item dari peminjaman dengan status menunggu_verifikasi.');
            }

            if (!$user || ($peminjaman->id_peminjam != $user->id && !in_array($user->role, [User::ROLE_ADMIN, User::ROLE_OPERATOR]))) {
                throw new \Exception('Anda tidak berhak menghapus item dari peminjaman ini.');
            }

            $barang = Barang::find($detail->id_barang);
            $barang->stok_tersedia += $detail->jumlah_dipinjam;
            $barang->save();

            $detail->delete();

            // Jika semua detail peminjaman telah dihapus, hapus peminjaman
            if ($peminjaman->detailPeminjaman()->count() === 0) {
                $peminjaman->delete();
                DB::commit();
                return redirect()->route('guru.peminjaman.index')->with('success', 'Peminjaman berhasil dihapus karena tidak ada lagi item.');
            }

            DB::commit();
            return redirect()->route('guru.peminjaman.show', $id)->with('success', 'Item berhasil dihapus dari peminjaman.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    public function peminjamanBerlangsung()
    {
        $user = Auth::user();

        $peminjamanBerlangsung = Peminjaman::where('id_peminjam', $user->id)
            ->where(function ($query) {
                $query->where('status_persetujuan', 'disetujui')
                    ->orWhere('status_persetujuan', 'menunggu_verifikasi');
            })
            ->with(['detailPeminjaman.barang', 'detailPeminjaman.ruanganTujuan'])
            ->paginate(10, ['*'], 'peminjamanBerlangsungPage');

        $peminjamanTerlambat = Peminjaman::where('id_peminjam', $user->id)
            ->whereHas('detailPeminjaman', function ($query) {
                $query->where('status_pengembalian', 'dipinjam')
                    ->where('tanggal_kembali', '<', now());
            })
            ->with(['detailPeminjaman.barang', 'detailPeminjaman.ruanganTujuan'])
            ->paginate(10, ['*'], 'peminjamanTerlambatPage');

        return view('guru.peminjaman.sedang-berlangsung', compact('peminjamanBerlangsung', 'peminjamanTerlambat'));
    }

    public function ajukanPengembalian($id)
    {
        try {
            DB::beginTransaction();

            $detailPeminjaman = DetailPeminjaman::findOrFail($id);

            if ($detailPeminjaman->status_pengembalian != 'dipinjam') {
                throw new \Exception("Pengembalian hanya dapat diajukan untuk item yang sedang dipinjam.");
            }

            // Menggunakan metode yang ada di model DetailPeminjaman
            $detailPeminjaman->ajukanPengembalian();

            DB::commit();

            return redirect()->back()->with('success', 'Pengajuan pengembalian berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengajukan pengembalian: ' . $e->getMessage());
        }
    }

    public function ajukanPerpanjangan($id)
    {
        try {
            DB::beginTransaction();

            $detailPeminjaman = DetailPeminjaman::findOrFail($id);

            if ($detailPeminjaman->status_pengembalian != 'dipinjam') {
                throw new \Exception("Perpanjangan hanya dapat diajukan untuk item yang sedang dipinjam.");
            }

            if (!$detailPeminjaman->dapat_diperpanjang) {
                throw new \Exception("Item ini tidak dapat diperpanjang.");
            }

            // Ubah status pengajuan perpanjangan
            $detailPeminjaman->status_pengembalian = 'menunggu_verifikasi';
            $detailPeminjaman->diperpanjang = true; // Menandai bahwa ini adalah permintaan perpanjangan
            $detailPeminjaman->save();

            // Update status peminjaman induk
            $detailPeminjaman->peminjaman->updateStatusPengembalian();

            DB::commit();

            return redirect()->back()->with('success', 'Pengajuan perpanjangan berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengajukan perpanjangan: ' . $e->getMessage());
        }
    }

    public function getBarangByRuangan($ruanganId)
    {
        $barang = Barang::where('ruangan_id', $ruanganId)->get();
        return response()->json($barang);
    }
}
