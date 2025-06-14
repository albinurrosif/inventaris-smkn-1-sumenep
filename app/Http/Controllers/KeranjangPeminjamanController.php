<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BarangQrCode;

class KeranjangPeminjamanController extends Controller
{
    // Menambah item ke keranjang di session
    public function tambahItem(Request $request)
    {
        $request->validate(['id_barang_qr_code' => 'required|exists:barang_qr_codes,id']);

        $keranjang = session()->get('keranjang_peminjaman', []);
        $barangIdBaru = $request->id_barang_qr_code;

        // 1. Cek duplikat (logika ini tetap penting)
        if (in_array($barangIdBaru, $keranjang)) {
            return back()->with('info', 'Barang sudah ada di keranjang.');
        }

        // 2. Ambil data barang baru yang akan ditambahkan
        $barangBaru = BarangQrCode::with('ruangan')->find($barangIdBaru);
        if (!$barangBaru || !$barangBaru->ruangan) {
            return back()->with('error', 'Gagal menambah: Barang tidak valid atau tidak memiliki lokasi.');
        }

        // 3. Jika keranjang TIDAK kosong, lakukan validasi ruangan
        if (!empty($keranjang)) {
            // Ambil ID barang pertama di keranjang untuk dijadikan patokan ruangan
            $idBarangPertama = $keranjang[0];
            $barangPertama = BarangQrCode::find($idBarangPertama);

            // Bandingkan ID ruangan barang pertama dengan barang baru
            if ($barangPertama->id_ruangan !== $barangBaru->id_ruangan) {
                $namaRuanganTerkunci = optional($barangPertama->ruangan)->nama_ruangan ?? 'N/A';
                return back()->with('error', "Gagal menambah barang. Semua barang harus berasal dari ruangan yang sama. Keranjang Anda terkunci untuk ruangan: '{$namaRuanganTerkunci}'.");
            }
        }

        // 4. Jika semua validasi lolos, tambahkan barang ke keranjang
        $keranjang[] = $barangIdBaru;
        session()->put('keranjang_peminjaman', $keranjang);

        return back()->with('success', "'{$barangBaru->barang->nama_barang}' berhasil ditambahkan ke keranjang.");
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
