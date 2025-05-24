<?php

namespace App\Http\Controllers;

use App\Models\KategoriBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;

class KategoriBarangController extends Controller
{
    /**
     * Menampilkan daftar semua kategori barang
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Proses filter atau pencarian jika ada
        $query = KategoriBarang::query();

        $kategoriBarang = KategoriBarang::query()
            ->when($request->has('search'), function ($query) use ($request) {
                $query->search($request->search);
            })
            ->orderBy($request->input('sort', 'nama_kategori'), $request->input('direction', 'asc'))
            ->paginate($request->input('per_page', 10));

        // Tambahkan informasi jumlah barang untuk setiap kategori
        // Pastikan $kategoriBarang adalah instance dari LengthAwarePaginator sebelum memanggil getCollection()
        if ($kategoriBarang instanceof LengthAwarePaginator) {
            $kategoriBarang->getCollection()->transform(function ($kategori) {
                $kategori->jumlah_item = $kategori->getItemCount();
                $kategori->jumlah_unit = $kategori->getTotalUnitCount();
                $kategori->unit_aktif = $kategori->getActiveUnitCount();
                $kategori->nilai_total = $kategori->getTotalValue();
                return $kategori;
            });
        } else {
            // Handle jika $kategoriBarang bukan instance yang diharapkan.  Ini penting
            // untuk mencegah error jika terjadi sesuatu yang tidak terduga.  Misalnya:
            // Log::error('kategoriBarang bukan instance LengthAwarePaginator'); // Jika Anda menggunakan logging
            // Atau, kembalikan response error:
            return response()->json(['error' => 'Terjadi kesalahan: Data kategori barang tidak valid.'], 500);
        }

        // Load the view, passing the paginated data.
        return view('admin.kategori.index', compact('kategoriBarang'));
    }

    /**
     * Menampilkan form untuk membuat kategori barang baru
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.kategori.create');
    }

    /**
     * Menyimpan kategori barang baru ke database
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama_kategori' => 'required|string|max:255|unique:kategori_barang,nama_kategori',
            'deskripsi' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->route('kategori-barang.create')
                ->withErrors($validator)
                ->withInput();
        }

        // Buat kategori baru
        $kategori = KategoriBarang::create([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi
        ]);

        return redirect()->route('kategori-barang.index')
            ->with('success', 'Kategori barang berhasil ditambahkan.');
    }

    /**
     * Menampilkan data kategori barang tertentu
     *
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function show(KategoriBarang $kategoriBarang)
    {
        // Tambahkan informasi jumlah barang dalam kategori
        $itemCount = $kategoriBarang->getItemCount();
        $totalUnit = $kategoriBarang->getTotalUnitCount();
        $activeUnit = $kategoriBarang->getActiveUnitCount();
        $totalValue = $kategoriBarang->getTotalValue();

        // Dapatkan daftar barang dalam kategori ini
        $barangList = $kategoriBarang->getAllItems();

        return view('admin.kategori.show', compact(
            'kategoriBarang',
            'itemCount',
            'totalUnit',
            'activeUnit',
            'totalValue',
            'barangList'
        ));
    }

    /**
     * Menampilkan form untuk mengedit kategori barang
     *
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function edit(KategoriBarang $kategoriBarang)
    {
        return view('admin.kategori.edit', compact('kategoriBarang'));
    }

    /**
     * Mengupdate data kategori barang di database
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, KategoriBarang $kategoriBarang)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kategori_barang')->ignore($kategoriBarang->id)
            ],
            'deskripsi' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->route('kategori-barang.edit', $kategoriBarang->id)
                ->withErrors($validator)
                ->withInput();
        }

        // Update kategori
        $kategoriBarang->update([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi
        ]);

        return redirect()->route('kategori-barang.index')
            ->with('success', 'Kategori barang berhasil diperbarui.');
    }

    /**
     * Menghapus kategori barang dari database
     *
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function destroy(KategoriBarang $kategoriBarang)
    {
        // Cek apakah kategori memiliki barang
        if ($kategoriBarang->hasItems()) {
            return redirect()->route('kategori-barang.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki barang terkait.');
        }

        // Hapus kategori
        $kategoriBarang->delete();

        return redirect()->route('kategori-barang.index')
            ->with('success', 'Kategori barang berhasil dihapus.');
    }

    /**
     * Menampilkan daftar barang dalam kategori tertentu (API)
     *
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function getItems(KategoriBarang $kategoriBarang)
    {
        $barangList = $kategoriBarang->getAllItems();

        return response()->json([
            'status' => 'success',
            'data' => $barangList
        ]);
    }

    /**
     * Mendapatkan data statistik kategori
     *
     * @return \Illuminate\Http\Response
     */
    public function getStatistics()
    {
        $kategoris = KategoriBarang::all();

        $stats = $kategoris->map(function ($kategori) {
            return [
                'id' => $kategori->id,
                'nama' => $kategori->nama_kategori,
                'jumlah_item' => $kategori->getItemCount(),
                'jumlah_unit' => $kategori->getTotalUnitCount(),
                'unit_aktif' => $kategori->getActiveUnitCount(),
                'nilai_total' => $kategori->getTotalValue()
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
