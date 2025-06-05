<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth; // Ditambahkan jika belum ada
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LogAktivitasController extends Controller
{
    use AuthorizesRequests;

    /**
     * Menampilkan daftar log aktivitas.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LogAktivitas::class); // Menggunakan Policy

        $searchTerm = $request->input('search');
        $userIdFilter = $request->input('id_user');
        $modelTerkaitFilter = $request->input('model_terkait');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = LogAktivitas::with(['user']);

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('aktivitas', 'LIKE', "%{$searchTerm}%")
                    // Jika Anda menambahkan field 'deskripsi' ke model LogAktivitas, uncomment baris berikut
                    // ->orWhere('deskripsi', 'LIKE', "%{$searchTerm}%") 
                    ->orWhere('ip_address', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($userIdFilter) {
            $query->where('id_user', $userIdFilter);
        }

        if ($modelTerkaitFilter) {
            // Controller sudah menghandle jika $modelTerkaitFilter adalah nama class pendek atau FQCN
            if (class_exists("App\\Models\\" . $modelTerkaitFilter) && !str_contains($modelTerkaitFilter, '\\')) {
                $query->where('model_terkait', "App\\Models\\" . $modelTerkaitFilter);
            } else if (class_exists($modelTerkaitFilter)) {
                $query->where('model_terkait', $modelTerkaitFilter);
            }
        }

        if ($tanggalMulai) {
            $query->whereDate('created_at', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $query->whereDate('created_at', '<=', $tanggalSelesai);
        }

        $logAktivitasList = $query->latest('created_at')->paginate(25)->withQueryString();

        $userList = User::orderBy('username')->get();

        $modelList = [
            'Barang' => 'App\Models\Barang',
            'BarangQrCode' => 'App\Models\BarangQrCode',
            'KategoriBarang' => 'App\Models\KategoriBarang',
            'Ruangan' => 'App\Models\Ruangan',
            'Peminjaman' => 'App\Models\Peminjaman',
            'Pemeliharaan' => 'App\Models\Pemeliharaan',
            'StokOpname' => 'App\Models\StokOpname',
            'ArsipBarang' => 'App\Models\ArsipBarang',
            'User' => 'App\Models\User',
            'MutasiBarang' => 'App\Models\MutasiBarang', // Ditambahkan
            'DetailPeminjaman' => 'App\Models\DetailPeminjaman', // Ditambahkan
            'DetailStokOpname' => 'App\Models\DetailStokOpname', // Ditambahkan
            // Tambahkan model lain yang relevan dan dicatat di LogAktivitas
        ];

        return view('admin.log-aktivitas.index', compact(
            'logAktivitasList',
            'userList',
            'modelList',
            'searchTerm',
            'userIdFilter',
            'modelTerkaitFilter',
            'tanggalMulai',
            'tanggalSelesai'
        ));
    }

    /**
     * Menampilkan detail satu entri log aktivitas.
     */
    public function show(LogAktivitas $logAktivitas): View
    {
        $this->authorize('view', $logAktivitas); // Menggunakan Policy

        $logAktivitas->load('user');

        $modelTerkaitInstance = null;
        if ($logAktivitas->model_terkait && $logAktivitas->id_model_terkait && class_exists($logAktivitas->model_terkait)) {
            $modelClass = $logAktivitas->model_terkait;
            // Coba dengan withTrashed jika model mungkin soft deleted
            if (method_exists($modelClass, 'withTrashed')) {
                $modelTerkaitInstance = $modelClass::withTrashed()->find($logAktivitas->id_model_terkait);
            } else {
                $modelTerkaitInstance = $modelClass::find($logAktivitas->id_model_terkait);
            }
        }

        return view('admin.log-aktivitas.show', compact('logAktivitas', 'modelTerkaitInstance'));
    }
}
