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
        $barangId = $request->id_barang_qr_code;

        // Cek duplikat
        if (!in_array($barangId, $keranjang)) {
            $keranjang[] = $barangId;
            session()->put('keranjang_peminjaman', $keranjang);
            return back()->with('success', 'Barang berhasil ditambahkan ke keranjang.');
        }

        return back()->with('info', 'Barang sudah ada di keranjang.');
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
