<?php

namespace App\Http\Controllers;

use App\Models\RekapStok;
use App\Models\Barang;
use App\Models\Ruangan;
use App\Models\KategoriBarang;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB; // Untuk query yang lebih kompleks jika diperlukan
use Illuminate\Http\RedirectResponse;

class RekapStokController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', RekapStok::class);

        $searchTerm = $request->input('search'); // Untuk nama barang, kode barang
        $ruanganFilter = $request->input('id_ruangan');
        $kategoriFilter = $request->input('id_kategori');
        $periodeFilter = $request->input('periode_rekap'); // Format YYYY-MM

        $query = RekapStok::with(['barang.kategori', 'ruangan']);

        if ($searchTerm) {
            $query->whereHas('barang', function ($q) use ($searchTerm) {
                $q->where('nama_barang', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('kode_barang', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($ruanganFilter) {
            $query->where('id_ruangan', $ruanganFilter);
        }

        if ($kategoriFilter) {
            $query->whereHas('barang', function ($q) use ($kategoriFilter) {
                $q->where('id_kategori', $kategoriFilter);
            });
        }

        if ($periodeFilter) {
            // Asumsi periode_rekap adalah date, filter berdasarkan tahun dan bulan
            $year = substr($periodeFilter, 0, 4);
            $month = substr($periodeFilter, 5, 2);
            $query->whereYear('periode_rekap', $year)->whereMonth('periode_rekap', $month);
        }

        $rekapStokList = $query->orderBy('periode_rekap', 'desc')
            ->orderBy('id_ruangan')
            ->orderBy('id_barang')
            ->paginate(20)
            ->withQueryString();

        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();
        $kategoriList = KategoriBarang::orderBy('nama_kategori')->get();

        return view('admin.rekap-stok.index', compact(
            'rekapStokList',
            'ruanganList',
            'kategoriList',
            'searchTerm',
            'ruanganFilter',
            'kategoriFilter',
            'periodeFilter'
        ));
    }

    /**
     * Display the specified resource.
     * (Opsional, jika diperlukan halaman detail per rekap)
     */
    public function show(RekapStok $rekapStok): View
    {
        $this->authorize('view', $rekapStok);
        $rekapStok->load(['barang.kategori', 'ruangan']);
        return view('admin.rekap-stok.show', compact('rekapStok'));
    }

    // Metode create, store, edit, update, destroy tidak diperlukan karena rekap adalah hasil proses.
    // Namun, bisa ada metode untuk memicu pembuatan rekap secara manual.

    /**
     * Memicu pembuatan rekap stok untuk periode tertentu (misalnya bulan saat ini).
     * Ini adalah contoh sederhana, logika sebenarnya bisa lebih kompleks.
     */
    public function generateRekap(Request $request): RedirectResponse
    {
        $this->authorize('create', RekapStok::class); // Atau ability khusus

        $periode = $request->input('periode', now()->format('Y-m-d')); // Ambil periode dari input atau default ke hari ini
        $targetDate = \Carbon\Carbon::parse($periode)->endOfMonth(); // Contoh: rekap akhir bulan

        // Logika untuk mengambil semua barang dan ruangan, lalu menghitung stok
        // dan memanggil RekapStok::updateOrCreateRekapForCompletedSO() atau metode serupa.
        // Ini bisa menjadi proses yang panjang, pertimbangkan background job.

        // Contoh placeholder:
        $barangs = Barang::all();
        $ruangans = Ruangan::all();

        foreach ($ruangans as $ruangan) {
            foreach ($barangs as $barang) {
                // Panggil metode yang ada di model RekapStok untuk membuat/update data
                // Misalnya, jika tidak ada StokOpname ID spesifik, Anda mungkin perlu metode lain
                // yang hanya menghitung jumlah_tercatat_sistem.
                // RekapStok::updateOrCreateRekapForCompletedSO($barang->id, $ruangan->id, $targetDate->toDateString(), null);
                // Atau metode custom:
                RekapStok::updateOrCreate(
                    [
                        'id_barang' => $barang->id,
                        'id_ruangan' => $ruangan->id,
                        'periode_rekap' => $targetDate->toDateString(),
                    ],
                    [
                        'jumlah_tercatat_sistem' => $barang->qrCodes()
                                                        ->where('id_ruangan', $ruangan->id)
                                                        ->whereNull('deleted_at')->count(),
                        // jumlah_fisik_terakhir mungkin perlu diambil dari StokOpname terakhir yang relevan
                        'jumlah_fisik_terakhir' => null, // Atau logika untuk mengambilnya
                        'catatan' => 'Rekap manual periode ' . $targetDate->isoFormat('MMMM YYYY'),
                    ]
                );
            }
        }

        return redirect()->route('admin.rekap-stok.index')->with('success', 'Proses pembuatan rekap stok telah dimulai/selesai.');
    }
}
