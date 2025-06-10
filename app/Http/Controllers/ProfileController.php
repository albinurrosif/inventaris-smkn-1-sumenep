<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

// --- TAMBAHAN: Import Model yang dibutuhkan ---
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\Pemeliharaan;
use App\Models\StokOpname;
use App\Models\BarangQrCode;
use Illuminate\Database\Eloquent\Builder;

class ProfileController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    /**
     * Display the user's profile form.
     * (Method ini tidak diubah)
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     * (Method ini tidak diubah)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete(); // Ini akan melakukan soft delete

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }


    // ====================================================================================
    // == PENYESUAIAN: METHOD BARU UNTUK HALAMAN "AKTIVITAS SAYA" ==
    // ====================================================================================

    /**
     * Menampilkan halaman riwayat aktivitas pengguna yang sedang login.
     */
    public function myActivity(): View
    {
        // PERUBAHAN: Tambahkan otorisasi menggunakan Gate
        $this->authorize('view-my-activity');

        $user = Auth::user();
        /** @var \App\Models\User $user */
        $data = [];

        if ($user->hasRole(User::ROLE_GURU)) {
            // Data untuk Guru: riwayat peminjaman dan pemeliharaan yang diajukan
            $data['peminjamanList'] = Peminjaman::where('id_guru', $user->id)
                ->withCount('detailPeminjaman')
                ->latest('tanggal_pengajuan')->paginate(10, ['*'], 'peminjaman_page');

            $data['pemeliharaanList'] = Pemeliharaan::where('id_user_pengaju', $user->id)
                ->with('barangQrCode.barang')
                ->latest('tanggal_pengajuan')->paginate(10, ['*'], 'pemeliharaan_page');
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            // Data untuk Operator: Tugas dan aktivitas yang terkait dengannya
            $ruanganIds = $user->ruanganYangDiKelola()->pluck('id');

            $data['peminjamanTerkait'] = Peminjaman::where(function (Builder $query) use ($ruanganIds, $user) {
                $query->whereHas('detailPeminjaman.barangQrCode', fn($q) => $q->whereIn('id_ruangan', $ruanganIds))
                    ->orWhereIn('id_ruangan_tujuan_peminjaman', $ruanganIds);
            })
                ->withCount('detailPeminjaman')->latest('tanggal_pengajuan')->paginate(10, ['*'], 'peminjaman_page');

            $data['pemeliharaanTerkait'] = Pemeliharaan::where(function (Builder $query) use ($ruanganIds, $user) {
                $query->where('id_user_pengaju', $user->id)
                    ->orWhere('id_operator_pengerjaan', $user->id)
                    ->orWhereHas('barangQrCode', fn($q) => $q->whereIn('id_ruangan', $ruanganIds));
            })
                ->with('barangQrCode.barang', 'pengaju')->latest('tanggal_pengajuan')->paginate(10, ['*'], 'pemeliharaan_page');

            $data['stokOpnameTugas'] = StokOpname::where('id_operator', $user->id)
                ->with('ruangan')->latest('tanggal_opname')->paginate(10, ['*'], 'stokopname_page');
        }

        return view('profile.activity', $data);
    }
}
