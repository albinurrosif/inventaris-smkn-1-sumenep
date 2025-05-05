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


class PeminjamanController extends Controller
{
    /**
     * Menampilkan daftar peminjaman
     */
    public function index()
    {
        $user = Auth::user();

        if ($user && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_OPERATOR])) {
            $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            $peminjaman = Peminjaman::with(['detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
                ->where('id_peminjam', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('guru.peminjaman.index', compact('peminjaman'));
    }




    /**
     * Menampilkan form untuk membuat peminjaman baru
     */
    public function create()
    {
        $ruangan = Ruangan::all();
        return view('guru.peminjaman.create', compact('ruangan'));
    }

    /**
     * Menyimpan peminjaman baru ke database
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ruangan_asal.*' => 'required|exists:ruangan,id',
            'ruangan_tujuan.*' => 'required|exists:ruangan,id',
            'barang_id.*' => 'required|exists:barang,id',
            'jumlah_dipinjam.*' => 'required|integer|min:1',
            'tanggal_pinjam.*' => 'required|date|after_or_equal:today',
            'tanggal_kembali.*' => 'required|date|after:tanggal_pinjam.*',
        ], [
            'ruangan_asal.*.required' => 'Ruangan asal harus diisi.',
            'ruangan_asal.*.exists' => 'Ruangan asal tidak valid.',
            'ruangan_tujuan.*.required' => 'Ruangan tujuan harus diisi.',
            'ruangan_tujuan.*.exists' => 'Ruangan tujuan tidak valid.',
            'barang_id.*.required' => 'Nama barang harus diisi.',
            'barang_id.*.exists' => 'Nama barang tidak valid.',
            'jumlah_dipinjam.*.required' => 'Jumlah pinjam harus diisi.',
            'jumlah_dipinjam.*.integer' => 'Jumlah pinjam harus berupa angka.',
            'jumlah_dipinjam.*.min' => 'Jumlah pinjam minimal 1.',
            'tanggal_pinjam.*.required' => 'Tanggal pinjam harus diisi.',
            'tanggal_pinjam.*.date' => 'Tanggal pinjam tidak valid.',
            'tanggal_pinjam.*.after_or_equal' => 'Tanggal pinjam tidak boleh sebelum hari ini.',
            'tanggal_kembali.*.required' => 'Tanggal kembali harus diisi.',
            'tanggal_kembali.*.date' => 'Tanggal kembali tidak valid.',
            'tanggal_kembali.*.after' => 'Tanggal kembali harus setelah tanggal pinjam.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $peminjaman = new Peminjaman();
            $peminjaman->id_peminjam = Auth::id();
            $peminjaman->tanggal_pengajuan = Carbon::now();
            $peminjaman->status = 'diajukan'; // Status awal
            $peminjaman->save();

            foreach ($request->barang_id as $key => $barangId) {
                $barang = Barang::find($barangId);
                if ($barang->stok_tersedia < $request->jumlah_dipinjam[$key]) {
                    throw new \Exception("Stok barang " . $barang->nama_barang . " tidak mencukupi.");
                }

                $detailPeminjaman = new DetailPeminjaman();
                $detailPeminjaman->id_peminjaman = $peminjaman->id;
                $detailPeminjaman->id_barang = $barangId;
                $detailPeminjaman->ruangan_asal = $request->ruangan_asal[$key];
                $detailPeminjaman->ruangan_tujuan = $request->ruangan_tujuan[$key];
                $detailPeminjaman->jumlah_dipinjam = $request->jumlah_dipinjam[$key];
                $detailPeminjaman->tanggal_pinjam = $request->tanggal_pinjam[$key];
                $detailPeminjaman->tanggal_kembali = $request->tanggal_kembali[$key];

                // Hitung durasi peminjaman
                $tanggalPinjam = Carbon::parse($request->tanggal_pinjam[$key]);
                $tanggalKembali = Carbon::parse($request->tanggal_kembali[$key]);
                $detailPeminjaman->durasi_pinjam = $tanggalPinjam->diffInDays($tanggalKembali);

                $detailPeminjaman->save();

                $barang->stok_tersedia -= $request->jumlah_dipinjam[$key];
                $barang->save();
            }

            DB::commit();

            return redirect()->route('guru.peminjaman.index')->with('success', 'Peminjaman berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengajukan peminjaman: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage());
        }
    }


    /**
     * Menampilkan detail peminjaman
     */
    public function show($id)
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan', 'diprosesOleh'])
            ->findOrFail($id);

        if (Auth::user()->role == User::ROLE_GURU && Auth::id() != $peminjaman->id_peminjam) {
            abort(403, 'Anda tidak memiliki izin untuk melihat detail peminjaman ini.');
        }


        return view('guru.peminjaman.show', compact('peminjaman'));
    }

    public function getBarangByRuangan($ruanganId)
    {
        $barang = Barang::where('ruangan_id', $ruanganId)->get();
        return response()->json($barang);
    }





    /**
     * Menampilkan peminjaman yang sedang berlangsung
     */
    public function peminjamanBerlangsung()
    {
        $peminjamanBerlangsung = Peminjaman::where('status', 'dipinjam')
            ->orWhere('status', 'menunggu_verifikasi')
            ->paginate(10);

        return view('guru.peminjaman.sedang-berlangsung', compact('peminjamanBerlangsung'));
    }

    /**
     * Menampilkan form untuk mengajukan pengembalian
     */
    public function ajukanPengembalian($id)
    {
        try {
            DB::beginTransaction();

            $detailPeminjaman = DetailPeminjaman::findOrFail($id);

            // Validasi status
            if ($detailPeminjaman->status !== 'dipinjam') {
                throw new \Exception('Pengembalian hanya dapat diajukan untuk barang yang sedang dipinjam.');
            }

            $detailPeminjaman->status = 'menunggu_verifikasi';
            $detailPeminjaman->save();

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

            // Validasi status dan apakah dapat diperpanjang
            if ($detailPeminjaman->status !== 'dipinjam') {
                throw new \Exception('Perpanjangan hanya dapat diajukan untuk barang yang sedang dipinjam.');
            }

            if (!$detailPeminjaman->dapat_diperpanjang) {
                throw new \Exception('Barang ini tidak dapat diperpanjang.');
            }

            // Logika perpanjangan (misalnya, menambah tanggal kembali)
            $detailPeminjaman->tanggal_kembali = Carbon::parse($detailPeminjaman->tanggal_kembali)->addDays($detailPeminjaman->durasi_pinjam);
            $detailPeminjaman->diperpanjang = true;
            $detailPeminjaman->save();

            DB::commit();

            return redirect()->back()->with('success', 'Pengajuan perpanjangan berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengajukan perpanjangan: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus item dari peminjaman (hanya jika statusnya 'menunggu')
     */
    public function destroyItem($id, $detailId)
    {
        try {
            DB::beginTransaction();

            $peminjaman = Peminjaman::findOrFail($id);
            $detail = DetailPeminjaman::findOrFail($detailId);
            $user = Auth::user();

            // Pastikan peminjaman masih dalam status menunggu
            if ($peminjaman->status !== 'menunggu') {
                throw new \Exception('Hanya dapat menghapus item dari peminjaman dengan status menunggu.');
            }

            // Pastikan user yang menghapus adalah peminjam atau admin/operator
            if (!$user || ($peminjaman->id_peminjam != $user->id && !in_array($user->role, [User::ROLE_ADMIN, User::ROLE_OPERATOR]))) {
                throw new \Exception('Anda tidak berhak menghapus item dari peminjaman ini.');
            }

            // Kembalikan stok barang
            $barang = Barang::find($detail->id_barang);
            $barang->stok_tersedia += $detail->jumlah_dipinjam;
            $barang->save();

            // Hapus detail peminjaman
            $detail->delete();

            // Jika tidak ada lagi detail peminjaman, hapus peminjaman utama
            if ($peminjaman->detailPeminjaman()->count() === 0) {
                $peminjaman->delete();
                DB::commit();
                return redirect()->route('peminjaman.index')->with('success', 'Peminjaman berhasil dihapus karena tidak ada lagi item.');
            }

            DB::commit();
            return redirect()->route('peminjaman.show', $id)->with('success', 'Item berhasil dihapus dari peminjaman.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus peminjaman
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $peminjaman = Peminjaman::findOrFail($id);

            // Pastikan hanya peminjam yang bisa menghapus atau admin/operator
            if (Auth::id() != $peminjaman->id_peminjam && !in_array(Auth::user()->role, [User::ROLE_ADMIN, User::ROLE_OPERATOR])) {
                throw new \Exception('Anda tidak memiliki izin untuk membatalkan peminjaman ini.');
            }

            // Pastikan statusnya masih 'diajukan' atau 'menunggu'
            if (!in_array($peminjaman->status, ['diajukan', 'menunggu'])) {
                throw new \Exception('Peminjaman tidak dapat dibatalkan karena statusnya sudah berubah.');
            }

            // Kembalikan stok barang yang dipinjam
            foreach ($peminjaman->detailPeminjaman as $detail) {
                $barang = Barang::find($detail->id_barang);
                if ($barang) {
                    $barang->stok_tersedia += $detail->jumlah_dipinjam;
                    $barang->save();
                }
            }

            $peminjaman->delete();

            DB::commit();

            return redirect()->route('guru.peminjaman.index')->with('success', 'Peminjaman berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    /**------------------------------------------------------------------------------------------------*/

    /**
     * Menampilkan daftar peminjaman untuk operator
     */
    public function operatorIndex(Request $request)
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan']);

        // Filter Status
        if ($request->filled('status')) {
            $peminjaman->where('status', $request->status);
        }

        // Filter Ruangan (Mungkin perlu disesuaikan tergantung logika bagaimana ruangan terkait dengan peminjaman)
        if ($request->filled('ruangan')) {
            $peminjaman->whereHas('detailPeminjaman', function ($query) use ($request) {
                $query->where('ruangan_asal', $request->ruangan)
                    ->orWhere('ruangan_tujuan', $request->ruangan);
            });
        }

        // Sorting
        if ($request->filled('sort')) {
            $direction = $request->filled('direction') && strtolower($request->direction) == 'desc' ? 'desc' : 'asc';
            $peminjaman->orderBy($request->sort, $direction);
        } else {
            $peminjaman->orderBy('tanggal_pengajuan', 'desc'); // Default sorting
        }

        $peminjaman = $peminjaman->paginate(10);

        $ruangan = Ruangan::all(); // Fetch all ruangan for the filter

        // Ambil data peminjaman sedang berlangsung
        $peminjamanBerlangsung = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
            ->whereIn('status', ['dipinjam', 'menunggu_verifikasi'])
            ->orderBy('tanggal_kembali', 'asc') // Atau kriteria pengurutan lain
            ->paginate(5, ['*'], 'peminjamanBerlangsungPage'); // Pagination untuk bagian ini

        return view('operator.peminjaman.index', compact('peminjaman', 'ruangan', 'peminjamanBerlangsung'));
    }

    /**
     * Menampilkan detail peminjaman untuk operator
     */
    public function operatorShow($id)
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan', 'diprosesOleh'])
            ->findOrFail($id);

        return view('operator.peminjaman.show', compact('peminjaman'));
    }

    /**
     * Menampilkan daftar peminjaman yang menunggu verifikasi pengembalian
     */
    public function daftarPengembalianMenunggu()
    {
        $peminjamanMenungguVerifikasi = Peminjaman::with(['peminjam', 'detailPeminjaman.barang'])
            ->whereHas('detailPeminjaman', function ($query) {
                $query->where('status', 'menunggu_verifikasi');
            })
            ->orderBy('tanggal_pengajuan', 'desc')
            ->paginate(10);

        return view('operator.peminjaman.verifikasi-pengembalian', compact('peminjamanMenungguVerifikasi'));
    }

    /**
     * Menampilkan daftar peminjaman yang sedang berlangsung untuk operator
     */
    public function peminjamanBerlangsungOperator()
    {
        $operatorBerlangsung = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
            ->where('status', 'dipinjam')
            ->orderBy('tanggal_pengajuan', 'desc')
            ->paginate(10);

        return view('operator.peminjaman.sedang-berlangsung', compact('operatorBerlangsung'));
    }

    /**
     * Menampilkan form untuk verifikasi pengembalian barang
     */
    public function verifikasiPengembalian($id)
    {
        $peminjaman = Peminjaman::with('peminjam', 'detailPeminjaman.barang')
            ->findOrFail($id);

        // Pastikan hanya menampilkan jika status peminjaman adalah 'dipinjam' dan ada detail yang berstatus 'menunggu_verifikasi'
        $adaMenungguVerifikasi = $peminjaman->detailPeminjaman->contains(function ($detail) {
            return $detail->status === 'menunggu_verifikasi';
        });

        if ($peminjaman->status !== 'dipinjam' || !$adaMenungguVerifikasi) {
            return redirect()->route('operator.peminjaman.index')->with('error', 'Peminjaman ini tidak dapat diverifikasi.');
        }

        return view('operator.peminjaman.verifikasi-pengembalian', compact('peminjaman'));
    }

    /**
     * Memproses verifikasi pengembalian barang
     */
    public function prosesVerifikasiPengembalian(Request $request, $id)
    {
        $peminjaman = Peminjaman::findOrFail($id);

        // Validasi input
        $request->validate([
            'status_pengembalian.*' => 'required|in:Dikembalikan,Rusak',
            'kondisi_setelah.*'    => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $semua_dikembalikan = true;
            foreach ($peminjaman->detailPeminjaman as $detail) {
                if ($detail->status == 'menunggu_verifikasi') {
                    $detail->status = $request->input("status_pengembalian.{$detail->id}");
                    $detail->kondisi_setelah = $request->input("kondisi_setelah.{$detail->id}");
                    $detail->diverifikasi_oleh = Auth::id();
                    $detail->tanggal_pengembalian_aktual = now();
                    $detail->save();
                }
                if ($detail->status != 'dikembalikan') {
                    $semua_dikembalikan = false;
                }
            }

            if ($semua_dikembalikan) {
                $peminjaman->status = 'selesai';
            }
            $peminjaman->save();

            DB::commit();

            return redirect()->route('operator.peminjaman.index')->with('success', 'Verifikasi pengembalian berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses verifikasi: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan verifikasi: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form untuk verifikasi pengembalian barang
     */
    public function tampilkanFormVerifikasiPengembalian($id)
    {
        $peminjaman = Peminjaman::with([
            'peminjam',
            'detailPeminjaman' => function ($query) {
                $query->with('barang');
            }
        ])->findOrFail($id);

        // Validasi (seperti sebelumnya, ini PENTING)
        $allDetailPeminjamanMenungguVerifikasi = true;
        foreach ($peminjaman->detailPeminjaman as $detail) {
            if ($detail->status != 'menunggu_verifikasi') {
                $allDetailPeminjamanMenungguVerifikasi = false;
                break;
            }
        }

        if (!$allDetailPeminjamanMenungguVerifikasi) {
            return redirect()->route('operator.peminjaman.index')->with('error', 'Tidak dapat memverifikasi peminjaman ini.  Tidak semua item menunggu verifikasi.');
        }

        return view('operator.peminjaman.verifikasi-pengembalian', compact('peminjaman'));
    }

    /**
     * Menampilkan form untuk menolak peminjaman
     */
    public function tolakPeminjaman($id)
    {
        try {
            DB::beginTransaction();

            $peminjaman = Peminjaman::findOrFail($id);

            // Validasi status
            if ($peminjaman->status !== 'diajukan' && $peminjaman->status !== 'menunggu') {
                throw new \Exception('Peminjaman tidak dapat ditolak karena statusnya ' . $peminjaman->status);
            }

            $peminjaman->status = 'ditolak';
            $peminjaman->diproses_oleh = Auth::id();
            $peminjaman->tanggal_proses = Carbon::now();
            $peminjaman->save();

            // Update status detail peminjaman
            $peminjaman->detailPeminjaman()->update(['status' => 'ditolak', 'disetujui_oleh' => Auth::id()]);


            // Kembalikan stok barang
            foreach ($peminjaman->detailPeminjaman as $detail) {
                $barang = Barang::find($detail->id_barang);
                if ($barang) {
                    $barang->stok_tersedia += $detail->jumlah_dipinjam;
                    $barang->save();
                }
            }

            DB::commit();

            return redirect()->route('operator.peminjaman.index')->with('success', 'Peminjaman berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menolak peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form untuk menolak detail peminjaman
     */
    public function setujuiDetailPeminjaman($detailId)
    {
        try {
            DB::beginTransaction();

            $detailPeminjaman = DetailPeminjaman::findOrFail($detailId);

            // Validasi status
            if ($detailPeminjaman->status !== 'menunggu') {
                throw new \Exception('Item peminjaman tidak dapat disetujui karena statusnya ' . $detailPeminjaman->status);
            }

            $detailPeminjaman->status = 'disetujui';
            $detailPeminjaman->disetujui_oleh = Auth::id();
            $detailPeminjaman->save();

            // Kurangi stok barang
            $barang = Barang::find($detailPeminjaman->id_barang);
            if ($barang) {
                if ($barang->stok_tersedia < $detailPeminjaman->jumlah_dipinjam) {
                    throw new \Exception("Stok barang " . $barang->nama_barang . " tidak mencukupi.");
                }
                $barang->stok_tersedia -= $detailPeminjaman->jumlah_dipinjam;
                $barang->save();
            }

            DB::commit();

            return redirect()->back()->with('success', 'Item peminjaman berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyetujui item peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Menolak detail peminjaman
     */
    public function tolakDetailPeminjaman($detailId)
    {
        try {
            DB::beginTransaction();

            $detailPeminjaman = DetailPeminjaman::findOrFail($detailId);

            // Validasi status
            if ($detailPeminjaman->status !== 'menunggu') {
                throw new \Exception('Item peminjaman tidak dapat ditolak karena statusnya ' . $detailPeminjaman->status);
            }

            $detailPeminjaman->status = 'ditolak';
            $detailPeminjaman->disetujui_oleh = Auth::id(); // Tetap catat siapa yang menolak
            $detailPeminjaman->save();

            // Kembalikan stok (jika belum dikurangi sebelumnya - ini penting!)
            if ($detailPeminjaman->peminjaman->status !== 'disetujui') {
                $barang = Barang::find($detailPeminjaman->id_barang);
                if ($barang) {
                    $barang->stok_tersedia += $detailPeminjaman->jumlah_dipinjam;
                    $barang->save();
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Item peminjaman berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menolak item peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Menyetujui peminjaman
     */
    public function setujuiPeminjaman($id)
    {
        try {
            DB::beginTransaction();

            $peminjaman = Peminjaman::findOrFail($id);

            // Validasi status
            if ($peminjaman->status !== 'diajukan' && $peminjaman->status !== 'menunggu') {
                throw new \Exception('Peminjaman tidak dapat disetujui karena statusnya ' . $peminjaman->status);
            }

            $peminjaman->status = 'disetujui';
            $peminjaman->diproses_oleh = Auth::id();
            $peminjaman->tanggal_proses = Carbon::now();
            $peminjaman->save();

            // Update status detail peminjaman
            $peminjaman->detailPeminjaman()->update(['status' => 'disetujui', 'disetujui_oleh' => Auth::id()]);

            DB::commit();

            return redirect()->route('operator.peminjaman.index')->with('success', 'Peminjaman berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyetujui peminjaman: ' . $e->getMessage());
        }
    }


    /**--------------------------------------------------------------------------*/

    /**
     * Menampilkan daftar peminjaman untuk admin
     */
    public function adminIndex()
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.peminjaman.index', compact('peminjaman'));
    }

    /**
     * Menampilkan detail peminjaman untuk admin
     */
    public function adminShow($id)
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detailPeminjaman.barang', 'detailPeminjaman.ruanganAsal', 'detailPeminjaman.ruanganTujuan', 'diprosesOleh'])
            ->findOrFail($id);

        return view('admin.peminjaman.show', compact('peminjaman'));
    }
}
