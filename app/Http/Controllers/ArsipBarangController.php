<?php

namespace App\Http\Controllers;

use App\Models\ArsipBarang;
use Illuminate\Http\Request;

class ArsipBarangController extends Controller
{
    /**
     * Menampilkan daftar arsip barang yang dihapus.
     */
    public function index()
    {
        $arsipList = ArsipBarang::with(['barang.ruangan', 'barangQrCode', 'user'])
            ->latest('tanggal_dihapus')
            ->get();

        return view('admin.arsip-barang.index', compact('arsipList'));
    }
}
