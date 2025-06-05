<?php

namespace App\Exports\barang;

use App\Models\BarangQrCode;
use App\Models\User;
use App\Models\Ruangan;
use App\Models\KategoriBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class BarangQrCodeExportPdf
{
    protected array $filters;
    protected ?User $user;

    public function __construct(Request $request)
    {
        // Ambil filter dari request
        $this->filters = $request->only([
            'id_ruangan',
            'id_kategori',
            'status',
            'kondisi',
            'tahun_pembuatan',
            'search'
        ]);

        $this->user = Auth::user();
    }

    /**
     * Mendapatkan data yang sudah difilter
     */
    public function getFilteredData(): Collection
    {
        $query = BarangQrCode::with([
            'barang.kategori',
            'ruangan',
            'pemegangPersonal'
        ]);

        // Filter berdasarkan id_ruangan
        if (!empty($this->filters['id_ruangan'])) {
            $query->where('id_ruangan', $this->filters['id_ruangan']);
        }

        // Filter berdasarkan id_kategori (melalui relasi barang)
        if (!empty($this->filters['id_kategori'])) {
            $query->whereHas('barang', function ($q) {
                $q->where('id_kategori', $this->filters['id_kategori']);
            });
        }

        // Filter berdasarkan status unit
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Filter berdasarkan kondisi unit
        if (!empty($this->filters['kondisi'])) {
            $query->where('kondisi', $this->filters['kondisi']);
        }

        // Filter berdasarkan tahun pembuatan jenis barang (induk)
        if (!empty($this->filters['tahun_pembuatan'])) {
            $query->whereHas('barang', function ($q) {
                $q->where('tahun_pembuatan', $this->filters['tahun_pembuatan']);
            });
        }

        // Filter berdasarkan pencarian umum
        if (!empty($this->filters['search'])) {
            $searchTerm = $this->filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('kode_inventaris_sekolah', 'like', "%{$searchTerm}%")
                    ->orWhere('no_seri_pabrik', 'like', "%{$searchTerm}%")
                    ->orWhereHas('barang', function ($subQuery) use ($searchTerm) {
                        $subQuery->where('nama_barang', 'like', "%{$searchTerm}%")
                            ->orWhere('kode_barang', 'like', "%{$searchTerm}%")
                            ->orWhere('merk_model', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('ruangan', function ($subQuery) use ($searchTerm) {
                        $subQuery->where('nama_ruangan', 'like', "%{$searchTerm}%")
                            ->orWhere('kode_ruangan', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Jika pengguna adalah Operator, batasi data hanya dari ruangan yang dikelola
        if ($this->user && $this->user->hasRole(User::ROLE_OPERATOR)) {
            $ruanganOperatorIds = $this->user->ruanganYangDiKelola()->pluck('id');

            if ($ruanganOperatorIds->isNotEmpty()) {
                if (!empty($this->filters['id_ruangan'])) {
                    // Pastikan ruangan yang difilter adalah ruangan yang dikelola operator
                    if (!$ruanganOperatorIds->contains($this->filters['id_ruangan'])) {
                        return collect(); // Return empty collection
                    }
                } else {
                    // Batasi ke semua ruangan yang dikelola operator
                    $query->whereIn('id_ruangan', $ruanganOperatorIds);
                }
            } else {
                return collect(); // Return empty collection jika operator tidak mengelola ruangan
            }
        }

        // Hanya ambil unit yang tidak di-soft-delete
        $query->whereNull('deleted_at');

        return $query->orderBy('id_barang')
            ->orderBy('kode_inventaris_sekolah')
            ->get();
    }

    /**
     * Mendapatkan informasi filter untuk ditampilkan di PDF
     */
    public function getFilterInfo(): array
    {
        return [
            'ruangan' => $this->getFilteredRuanganName(),
            'kategori' => $this->getFilteredKategoriName(),
            'status' => $this->filters['status'] ?? 'Semua Status',
            'kondisi' => $this->filters['kondisi'] ?? 'Semua Kondisi',
            'tahun_pembuatan' => $this->filters['tahun_pembuatan'] ?? 'Semua Tahun',
            'search' => $this->filters['search'] ?? '-',
        ];
    }

    /**
     * Mendapatkan nama ruangan yang difilter
     */
    private function getFilteredRuanganName(): string
    {
        if (!empty($this->filters['id_ruangan'])) {
            $ruangan = Ruangan::find($this->filters['id_ruangan']);
            return $ruangan ? $ruangan->nama_ruangan : 'Ruangan Tidak Ditemukan';
        }
        return 'Semua Ruangan';
    }

    /**
     * Mendapatkan nama kategori yang difilter
     */
    private function getFilteredKategoriName(): string
    {
        if (!empty($this->filters['id_kategori'])) {
            $kategori = KategoriBarang::find($this->filters['id_kategori']);
            return $kategori ? $kategori->nama_kategori : 'Kategori Tidak Ditemukan';
        }
        return 'Semua Kategori';
    }
}
