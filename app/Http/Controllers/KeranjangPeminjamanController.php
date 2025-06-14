<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BarangQrCode;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\LogAktivitas; // <-- TAMBAHKAN IMPORT INI
use Illuminate\Support\Facades\Auth; // <-- TAMBAHKAN IMPORT INI
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class KeranjangPeminjamanController extends Controller
{
    use AuthorizesRequests;

    public function tambahItem(Request $request)
    {
        $validated = $request->validate([
            'id_barang_qr_code' => 'required|exists:barang_qr_codes,id',
            'peminjaman_id' => 'nullable|exists:peminjamen,id'
        ]);

        $barangIdBaru = $validated['id_barang_qr_code'];
        $peminjamanId = $validated['peminjaman_id'] ?? null;

        // [PENYESUAIAN 1: Hapus Duplikasi]
        // Ambil data barang baru satu kali di awal untuk efisiensi
        $barangBaru = BarangQrCode::with(['ruangan', 'barang'])->find($barangIdBaru);
        if (!$barangBaru || !$barangBaru->ruangan) {
            return back()->with('error', 'Gagal menambah: Barang tidak valid atau tidak memiliki lokasi ruangan.');
        }

        // =======================================================
        // == BAGAN ALUR UTAMA: Edit Peminjaman vs Buat Baru ==
        // =======================================================

        if ($peminjamanId) {
            // ------ MODE EDIT PENGAJUAN YANG SUDAH ADA ------
            $peminjaman = Peminjaman::findOrFail($peminjamanId);
            $this->authorize('update', $peminjaman);

            if ($peminjaman->detailPeminjaman()->where('id_barang_qr_code', $barangIdBaru)->exists()) {
                return redirect()->route('guru.peminjaman.edit', ['peminjaman' => $peminjamanId])->with('info', 'Barang sudah ada dalam pengajuan ini.');
            }

            $itemPertama = $peminjaman->detailPeminjaman()->with('barangQrCode.ruangan')->first();
            if ($itemPertama && $itemPertama->barangQrCode->id_ruangan !== $barangBaru->id_ruangan) {
                $namaRuanganTerkunci = optional($itemPertama->barangQrCode->ruangan)->nama_ruangan ?? 'N/A';
                return redirect()->route('guru.peminjaman.edit', ['peminjaman' => $peminjamanId])
                    ->with('error', "Gagal: Barang baru harus dari ruangan '{$namaRuanganTerkunci}'.");
            }

            DB::beginTransaction();
            try {
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjamanId,
                    'id_barang_qr_code' => $barangIdBaru,
                    'kondisi_sebelum' => $barangBaru->kondisi,
                    'status_unit' => DetailPeminjaman::STATUS_ITEM_DIAJUKAN,
                ]);

                // [PENYESUAIAN 2: Tambah Log Aktivitas]
                LogAktivitas::create([
                    'id_user' => Auth::id(),
                    'aktivitas' => 'Tambah Item ke Peminjaman (Edit)',
                    'deskripsi' => "Menambahkan '{$barangBaru->barang->nama_barang}' ke Peminjaman ID: {$peminjamanId}",
                    'model_terkait' => Peminjaman::class,
                    'id_model_terkait' => $peminjamanId
                ]);

                DB::commit();

                // [PENYESUAIAN 3: Perbaiki Redirect]
                return redirect()->route('guru.peminjaman.edit', ['peminjaman' => $peminjamanId])
                    ->with('success', "'{$barangBaru->barang->nama_barang}' berhasil ditambahkan ke pengajuan.");
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->route('guru.peminjaman.edit', ['peminjaman' => $peminjamanId])
                    ->with('error', 'Terjadi kesalahan saat menambahkan barang: ' . $e->getMessage());
            }
        } else {
            // ------ MODE BUAT PENGAJUAN BARU (SESSION) ------
            $keranjang = session()->get('keranjang_peminjaman', []);

            if (in_array($barangIdBaru, $keranjang)) {
                return back()->with('info', 'Barang sudah ada di keranjang.');
            }

            if (!empty($keranjang)) {
                $barangPertama = BarangQrCode::find($keranjang[0]);
                if ($barangPertama->id_ruangan !== $barangBaru->id_ruangan) {
                    $namaRuanganTerkunci = optional($barangPertama->ruangan)->nama_ruangan ?? 'N/A';
                    return back()->with('error', "Gagal menambah. Semua barang harus dari ruangan: '{$namaRuanganTerkunci}'.");
                }
            }

            $keranjang[] = $barangIdBaru;
            session()->put('keranjang_peminjaman', $keranjang);

            return back()->with('success', "'{$barangBaru->barang->nama_barang}' berhasil ditambahkan ke keranjang.");
        }
    }

    // Menghapus item dari keranjang
    public function hapusItem(Request $request, $id_barang_qr_code)
    {
        $keranjang = session()->get('keranjang_peminjaman', []);

        if (($key = array_search($id_barang_qr_code, $keranjang)) !== false) {
            unset($keranjang[$key]);
        }

        session()->put('keranjang_peminjaman', array_values($keranjang)); // Re-index array
        return back()->with('success', 'Barang berhasil dihapus dari keranjang.');
    }

    // Mengosongkan seluruh keranjang
    public function resetKeranjang(Request $request)
    {
        session()->forget('keranjang_peminjaman');
        return back()->with('success', 'Keranjang peminjaman berhasil dikosongkan.');
    }
}
