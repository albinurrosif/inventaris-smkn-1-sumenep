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

        // --- MULAI LOGIKA BARU UNTUK PERBANDINGAN DATA ---

        // Decode JSON string menjadi array asosiatif, tangani jika null
        $dataLama = json_decode($logAktivitas->data_lama, true) ?? [];
        $dataBaru = json_decode($logAktivitas->data_baru, true) ?? [];

        // Gabungkan semua key dari data lama dan baru untuk perbandingan menyeluruh
        $allKeys = array_unique(array_merge(array_keys($dataLama), array_keys($dataBaru)));

        // Siapkan array untuk menampung perubahan
        $perubahan = [];

        // Definisikan pemetaan dari nama kolom database ke label yang ramah pengguna
        // Ini adalah bagian terpenting untuk keterbacaan
        $fieldMappings = [
            // Umum
            'id' => 'ID',
            'name' => 'Nama',
            'username' => 'Username',
            'email' => 'Email',
            'role' => 'Hak Akses',
            'status' => 'Status',
            'created_at' => 'Waktu Dibuat',
            'updated_at' => 'Waktu Diubah',
            'deleted_at' => 'Waktu Dihapus',

            // Model Barang
            'nama_barang' => 'Nama Barang',
            'kode_barang' => 'Kode Barang',
            'id_kategori_barang' => 'Kategori Barang',
            'id_ruangan' => 'Lokasi Ruangan',
            'merk' => 'Merk/Tipe',
            'tahun_perolehan' => 'Tahun Perolehan',
            'kondisi' => 'Kondisi',
            'sumber_dana' => 'Sumber Dana',
            'keterangan' => 'Keterangan',

            // Model Peminjaman
            'kode_peminjaman' => 'Kode Peminjaman',
            'id_peminjam' => 'Nama Peminjam',
            'tanggal_pinjam' => 'Tanggal Pinjam',
            'tanggal_kembali' => 'Tanggal Kembali',
            'status_peminjaman' => 'Status Peminjaman',

            // Tambahkan pemetaan lain dari model-model Anda di sini...
            'nama_kategori' => 'Nama Kategori',
            'nama_ruangan' => 'Nama Ruangan',
        ];

        // Definisikan pemetaan untuk mengubah nilai ID menjadi teks yang bisa dibaca
        $valueResolvers = [
            'id_ruangan' => fn($id) => \App\Models\Ruangan::find($id)?->nama_ruangan ?? "ID: $id",
            'id_kategori_barang' => fn($id) => \App\Models\KategoriBarang::find($id)?->nama_kategori ?? "ID: $id",
            'id_peminjam' => fn($id) => \App\Models\User::find($id)?->name ?? "ID: $id",
            // Tambahkan resolver lain jika diperlukan
        ];

        foreach ($allKeys as $key) {
            // Abaikan kolom yang tidak ingin ditampilkan
            if (in_array($key, ['password', 'remember_token'])) {
                continue;
            }

            $oldValue = \Illuminate\Support\Arr::get($dataLama, $key);
            $newValue = \Illuminate\Support\Arr::get($dataBaru, $key);

            // Jika ada resolver, gunakan untuk mendapatkan nilai yang bisa dibaca
            if (isset($valueResolvers[$key])) {
                $oldValue = !is_null($oldValue) ? $valueResolvers[$key]($oldValue) : $oldValue;
                $newValue = !is_null($newValue) ? $valueResolvers[$key]($newValue) : $newValue;
            }

            // Hanya proses dan tampilkan jika nilainya benar-benar berubah
            if ($oldValue != $newValue) {
                $perubahan[] = [
                    'field' => $fieldMappings[$key] ?? ucwords(str_replace('_', ' ', $key)),
                    'lama'  => $this->formatValue($oldValue),
                    'baru'  => $this->formatValue($newValue),
                ];
            }
        }

        // --- AKHIR LOGIKA BARU ---

        $modelTerkaitInstance = null;
        if ($logAktivitas->model_terkait && $logAktivitas->id_model_terkait && class_exists($logAktivitas->model_terkait)) {
            $modelClass = $logAktivitas->model_terkait;
            if (method_exists($modelClass, 'withTrashed')) {
                $modelTerkaitInstance = $modelClass::withTrashed()->find($logAktivitas->id_model_terkait);
            } else {
                $modelTerkaitInstance = $modelClass::find($logAktivitas->id_model_terkait);
            }
        }

        return view('admin.log-aktivitas.show', compact(
            'logAktivitas',
            'modelTerkaitInstance',
            'perubahan',            // Variabel baru untuk view
            'dataBaru',             // Variabel baru untuk view (kasus 'created')
            'fieldMappings'         // Variabel baru untuk view (kasus 'created')
        ));
    }

    /**
     * Helper function untuk memformat nilai agar mudah dibaca di view.
     */
    private function formatValue($value): string
    {
        if (is_null($value)) {
            return ''; // Akan ditampilkan sebagai (Kosong) di view
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }
        // Cek apakah format tanggal valid
        if (\DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false) {
            return \Carbon\Carbon::parse($value)->isoFormat('DD MMM YYYY, HH:mm');
        }
        if (\DateTime::createFromFormat('Y-m-d', $value) !== false) {
            return \Carbon\Carbon::parse($value)->isoFormat('DD MMMM YYYY');
        }
        return (string) $value;
    }
}
