<?php

namespace App\Http\Controllers;

use App\Models\BarangStatus;
use App\Models\BarangQrCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class BarangStatusController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BarangStatus::class);

        $searchTerm = $request->input('search');
        $userIdPencatat = $request->input('id_user_pencatat');
        $kejadianFilter = $request->input('deskripsi_kejadian');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $idBarangQrCode = $request->input('id_barang_qr_code');

        $query = BarangStatus::with([
            'barangQrCode' => function ($q) {
                $q->withTrashed()->with(['barang.kategori']);
            },
            'userPencatat',
            'ruanganSebelumnya',
            'ruanganSesudahnya',
            'pemegangPersonalSebelumnya',
            'pemegangPersonalSesudahnya'
        ]);

        if ($idBarangQrCode) {
            $query->where('id_barang_qr_code', $idBarangQrCode);
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('barangQrCode', function ($qQr) use ($searchTerm) {
                    $qQr->where('kode_inventaris_sekolah', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('no_seri_pabrik', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('no_seri_internal', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('barang', function ($qBarang) use ($searchTerm) {
                            $qBarang->where('nama_barang', 'LIKE', "%{$searchTerm}%");
                        });
                });
            });
        }

        if ($userIdPencatat) {
            $query->where('id_user_pencatat', $userIdPencatat);
        }

        if ($kejadianFilter) {
            $query->where('deskripsi_kejadian', 'LIKE', "%{$kejadianFilter}%");
        }

        if ($tanggalMulai) {
            $query->whereDate('tanggal_pencatatan', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $query->whereDate('tanggal_pencatatan', '<=', $tanggalSelesai);
        }

        $logStatus = $query->latest('tanggal_pencatatan')->latest('id')->paginate(20)->withQueryString();

        $usersPencatat = User::orderBy('username')->get();
        $barangQrList = BarangQrCode::withTrashed()->with('barang')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'display_name' => ($item->barang->nama_barang ?? 'N/A') . ' - ' . $item->kode_inventaris_sekolah .
                    ($item->no_seri_pabrik ? ' (SN:' . $item->no_seri_pabrik . ')' : ($item->no_seri_internal ? ' (Internal:' . $item->no_seri_internal . ')' : ''))
            ];
        })->sortBy('display_name');

        return view('admin.barang-status.index', compact(
            'logStatus',
            'usersPencatat',
            'barangQrList',
            'searchTerm',
            'userIdPencatat',
            'kejadianFilter',
            'tanggalMulai',
            'tanggalSelesai',
            'idBarangQrCode'
        ));
    }

    /**
     * Display the specified resource.
     */
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $log = BarangStatus::with([
                'barangQrCode' => function ($q) {
                    $q->withTrashed()->with(['barang.kategori', 'ruangan', 'pemegangPersonal']);
                },
                'userPencatat',
                'ruanganSebelumnya',
                'ruanganSesudahnya',
                'pemegangPersonalSebelumnya',
                'pemegangPersonalSesudahnya'
            ])->findOrFail($id);

            $this->authorize('view', $log);

            return request()->ajax()
                ? view('admin.barang-status._detail', compact('log'))
                : view('admin.barang-status.show', compact('log'));
        } catch (\Exception $e) {
            Log::error('Error in BarangStatusController@show: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'error' => 'Server Error',
                    'message' => $e->getMessage()
                ], 500);
            }

            abort(500, 'Terjadi kesalahan server: ' . $e->getMessage());
        }
    }
}
