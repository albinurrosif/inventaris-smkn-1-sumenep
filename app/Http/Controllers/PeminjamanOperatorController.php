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

class PeminjamanOperatorController extends Controller
{
    /**
     * Menampilkan daftar peminjaman untuk operator
     * Hanya menampilkan peminjaman dari ruangan yang dikelola operator.
     */
    public function index()
    {
        $user = Auth::user();

        // Cari semua ID ruangan yang dikelola oleh operator yang login
        $ruanganOperatorIds = Ruangan::where('id_operator', $user->id)->pluck('id')->toArray();

        // Jika operator tidak mengelola ruangan, tampilkan array kosong atau logika lain sesuai kebutuhan
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

        // Cari semua ID ruangan yang dikelola oleh operator yang login
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
     * Menyetujui peminjaman oleh operator
     * Hanya menyetujui item peminjaman yang berasal dari ruangan operator.
     */
    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $ruanganOperator = $user->ruangan_id;

            $peminjaman = Peminjaman::findOrFail($id);

            // Cek apakah ada detail peminjaman dari ruangan operator
            $adaDetailRuanganOperator = $peminjaman->detailPeminjaman->contains(function ($detail) use ($ruanganOperator) {
                return $detail->ruangan_asal == $ruanganOperator;
            });

            if (!$adaDetailRuanganOperator) {
                throw new \Exception('Tidak ada item dari ruangan Anda yang dapat disetujui.');
            }

            $peminjaman->status = 'disetujui'; //ubah status peminjaman
            $peminjaman->diproses_oleh = Auth::id();
            $peminjaman->tanggal_proses = Carbon::now();
            $peminjaman->save();

            // Update status detail peminjaman yang sesuai dengan ruangan operator.
            foreach ($peminjaman->detailPeminjaman as $detail) {
                if ($detail->ruangan_asal == $ruanganOperator) {
                    $detail->status = 'disetujui';
                    $detail->disetujui_oleh = Auth::id();
                    $detail->save();
                }
            }

            DB::commit();

            return redirect()->route('operator.peminjaman.index')->with('success', 'Peminjaman berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui peminjaman: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyetujui peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Menolak peminjaman oleh operator
     * Hanya menolak item peminjaman yang berasal dari ruangan operator.
     */
    public function tolakPeminjaman($id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $ruanganOperator = $user->ruangan_id;

            $peminjaman = Peminjaman::findOrFail($id);

            // Cek apakah ada detail peminjaman dari ruangan operator
            $adaDetailRuanganOperator = $peminjaman->detailPeminjaman->contains(function ($detail) use ($ruanganOperator) {
                return $detail->ruangan_asal == $ruanganOperator;
            });

            if (!$adaDetailRuanganOperator) {
                throw new \Exception('Tidak ada item dari ruangan Anda yang dapat ditolak.');
            }

            $peminjaman->status = 'ditolak'; //ubah status
            $peminjaman->diproses_oleh = Auth::id();
            $peminjaman->tanggal_proses = Carbon::now();
            $peminjaman->save();

            // Update status detail peminjaman yang sesuai dengan ruangan operator.
            foreach ($peminjaman->detailPeminjaman as $detail) {
                if ($detail->ruangan_asal == $ruanganOperator) {
                    $detail->status = 'ditolak';
                    $detail->ditolak_oleh = Auth::id();
                    $detail->save();
                }
                // Mengembalikan stok barang yang ditolak
                $barang = Barang::find($detail->id_barang);
                $barang->stok_tersedia += $detail->jumlah_dipinjam;
                $barang->save();
            }

            DB::commit();
            return redirect()->route('operator.peminjaman.index')->with('success', 'Peminjaman berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menolak peminjaman: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menolak peminjaman: ' . $e->getMessage());
        }
    }

    public function verifikasiPengembalian(Request $request, $id)
    {
        //
    }

    public function verifikasiPengembalianStore(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'status_pengembalian.*' => 'required|in:baik,rusak',
                'kondisi_setelah.*' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            foreach ($peminjaman->detailPeminjaman as $detail) {
                $detail->status = $request->input('status_pengembalian.' . $detail->id);
                $detail->kondisi_setelah = $request->input('kondisi_setelah.' . $detail->id);
                $detail->tanggal_pengembalian_aktual = Carbon::now();
                $detail->diverifikasi_oleh = Auth::id();
                $detail->save();

                // Update stok barang jika dikembalikan
                if ($detail->status == 'baik' || $detail->status == 'rusak') {
                    $barang = Barang::find($detail->id_barang);
                    $barang->stok_tersedia += $detail->jumlah_dipinjam;
                    $barang->save();
                }
            }

            // Cek apakah semua detail peminjaman sudah selesai
            $semuaDetailSelesai = true;
            foreach ($peminjaman->detailPeminjaman as $detail) {
                if ($detail->status != 'baik' && $detail->status != 'rusak') {
                    $semuaDetailSelesai = false;
                    break;
                }
            }

            // Jika semua detail selesai, update status peminjaman
            if ($semuaDetailSelesai) {
                $peminjaman->status = 'selesai';
                $peminjaman->tanggal_selesai = Carbon::now();
                $peminjaman->save();
            }

            DB::commit();

            return redirect()->route('operator.peminjaman.index')->with('success', 'Verifikasi pengembalian berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses verifikasi pengembalian: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses verifikasi pengembalian: ' . $e->getMessage());
        }
    }
}
