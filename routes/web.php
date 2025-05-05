<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangStatusController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PemeliharaanController;
use App\Http\Controllers\RekapStokController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\StokOpnameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// New Peminjaman Controllers (from new version)
use App\Http\Controllers\PeminjamanGuruController;
use App\Http\Controllers\PeminjamanOperatorController;
use App\Http\Controllers\PeminjamanAdminController;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

// Bisa diletakkan di paling atas
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('redirect-dashboard');
    }
    return view('auth.login');
});

// Khusus guest (tidak perlu lagi isi '/')
Route::middleware('guest')->group(function () {
    require __DIR__ . '/auth.php';
});


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Redirect to dashboard by role (from old version - using match)
    Route::get('/redirect-dashboard', function () {
        return match (Auth::user()->role) {
            'Admin' => redirect()->route('admin.dashboard'),
            'Operator' => redirect()->route('operator.dashboard'),
            'Guru' => redirect()->route('guru.dashboard'),
            default => abort(403),
        };
    })->name('redirect-dashboard');

    // Logout routes (from old version)
    Route::get('/logout', [AuthenticatedSessionController::class, 'showLogout'])->name('logout.show');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Role: Guru (using isGuru middleware from old version)
    |--------------------------------------------------------------------------
    */
    Route::middleware('isGuru')->group(function () {
        Route::get('/guru/dashboard', [DashboardController::class, 'guru'])->name('guru.dashboard');

        // Peminjaman Routes for Guru (from new version with PeminjamanGuruController)
        Route::prefix('guru/peminjaman')->group(function () {
            Route::get('/', [PeminjamanGuruController::class, 'index'])->name('guru.peminjaman.index');
            Route::get('/create', [PeminjamanGuruController::class, 'create'])->name('guru.peminjaman.create');
            Route::post('/', [PeminjamanGuruController::class, 'store'])->name('guru.peminjaman.store');
            Route::get('/{id}', [PeminjamanGuruController::class, 'show'])->name('guru.peminjaman.show');
            Route::delete('/{id}', [PeminjamanGuruController::class, 'destroy'])->name('guru.peminjaman.destroy');
            Route::get('/berlangsung', [PeminjamanGuruController::class, 'peminjamanBerlangsung'])->name('guru.peminjaman.berlangsung');
            Route::post('/{id}/ajukan-pengembalian', [PeminjamanGuruController::class, 'ajukanPengembalian'])->name('guru.peminjaman.ajukanPengembalian');
            Route::post('/{id}/ajukan-perpanjangan', [PeminjamanGuruController::class, 'ajukanPerpanjangan'])->name('guru.peminjaman.ajukanPerpanjangan');
            Route::get('/barang/{ruanganId}', [PeminjamanGuruController::class, 'getBarangByRuangan']);
            Route::get('/riwayat', [PeminjamanGuruController::class, 'riwayat'])->name('guru.peminjaman.riwayat');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Role: Operator (using isOperator middleware from old version)
    |--------------------------------------------------------------------------
    */
    Route::middleware('isOperator')->group(function () {
        Route::get('/operator/dashboard', [DashboardController::class, 'operator'])->name('operator.dashboard');

        // Operator specific routes from old version
        Route::resource('stok-opname', StokOpnameController::class);
        Route::resource('pemeliharaan', PemeliharaanController::class);
        Route::get('/operator/barang', [BarangController::class, 'indexOperator'])->name('operator.barang.index');
        Route::get('/operator/barang/export', [BarangController::class, 'exportOperator'])->name('operator.barang.export');
        Route::get('/operator/pengembalian', [PeminjamanOperatorController::class, 'daftarPengembalianMenunggu'])->name('operator.pengembalian.index');

        // Peminjaman Routes for Operator (from new version with PeminjamanOperatorController)
        Route::prefix('operator/peminjaman')->group(function () {
            Route::get('/', [PeminjamanOperatorController::class, 'index'])->name('operator.peminjaman.index');
            Route::get('/{id}', [PeminjamanOperatorController::class, 'show'])->name('operator.peminjaman.show');
            Route::post('/{id}/setujui', [PeminjamanOperatorController::class, 'setujuiPeminjaman'])->name('operator.peminjaman.setujui');
            Route::post('/{id}/tolak', [PeminjamanOperatorController::class, 'tolakPeminjaman'])->name('operator.peminjaman.tolak');
            Route::get('/{id}/verifikasi-pengembalian', [PeminjamanOperatorController::class, 'tampilkanFormVerifikasiPengembalian'])->name('operator.peminjaman.verifikasi-pengembalian');
            Route::post('/{id}/verifikasi-pengembalian', [PeminjamanOperatorController::class, 'prosesVerifikasiPengembalian'])->name('operator.peminjaman.verifikasi-pengembalian.store');
            Route::get('/berlangsung', [PeminjamanOperatorController::class, 'peminjamanBerlangsungOperator'])->name('operator.peminjaman.berlangsung');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Role: Admin (using isAdmin middleware from old version)
    |--------------------------------------------------------------------------
    */
    Route::middleware('isAdmin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');

        // Admin specific routes from old version
        Route::resource('users', UserController::class);
        Route::resource('rekap-stok', RekapStokController::class);
        Route::resource('barang-status', BarangStatusController::class);
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

        // Admin - Export & Import Barang (from old version)
        Route::get('/barang/export', [BarangController::class, 'export'])->name('barang.export');
        Route::post('/barang/import', [BarangController::class, 'import'])->name('barang.import');

        // Admin - Pengaturan
        Route::resource('pengaturan', PengaturanController::class)->only(['index']);
        Route::post('/pengaturan/update', [PengaturanController::class, 'update'])->name('pengaturan.update');

        // Peminjaman Routes for Admin (from new version with PeminjamanAdminController)
        Route::prefix('admin/peminjaman')->group(function () {
            Route::get('/', [PeminjamanAdminController::class, 'index'])->name('admin.peminjaman.index');
            Route::get('/{id}', [PeminjamanAdminController::class, 'show'])->name('admin.peminjaman.show');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Shared Access: Admin & Operator (via canManageBarang)
    |--------------------------------------------------------------------------
    */
    Route::middleware('canManageBarang')->group(function () {
        Route::resource('barang', BarangController::class);
        Route::resource('ruangan', RuanganController::class);
    });
});
