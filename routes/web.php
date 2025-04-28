<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangStatusController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PemeliharaanController;
use App\Http\Controllers\RekapStokController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\StokOpnameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/', fn() => view('auth.login'));
    require __DIR__ . '/auth.php';
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Logout
    Route::get('/logout', [AuthenticatedSessionController::class, 'showLogout'])->name('logout.show');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Redirect to dashboard by role
    Route::get('/redirect-dashboard', function () {
        return match (Auth::user()->role) {
            'Admin' => redirect()->route('admin.dashboard'),
            'Operator' => redirect()->route('operator.dashboard'),
            'Guru' => redirect()->route('guru.dashboard'),
            default => abort(403),
        };
    })->name('redirect-dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Role: Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('isAdmin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
        Route::resource('users', UserController::class);
        Route::resource('rekap-stok', RekapStokController::class);
        Route::resource('barang-status', BarangStatusController::class);
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        // Admin - Pengaturan
        Route::resource('pengaturan', PengaturanController::class)->only(['index']);
        Route::post('/pengaturan/update', [PengaturanController::class, 'update'])->name('pengaturan.update');
        // Admin - Export & Import Barang
        Route::get('/barang/export', [BarangController::class, 'export'])->name('barang.export');
        Route::post('/barang/import', [BarangController::class, 'import'])->name('barang.import');
        // Admin - Pantau Semua Peminjaman
        Route::get('/admin/peminjaman', [PeminjamanController::class, 'adminIndex'])->name('admin.peminjaman.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Role: Operator
    |--------------------------------------------------------------------------
    */
    Route::middleware('isOperator')->group(function () {
        Route::get('/operator/dashboard', [DashboardController::class, 'operator'])->name('operator.dashboard');
        Route::resource('stok-opname', StokOpnameController::class);
        Route::resource('pemeliharaan', PemeliharaanController::class);
        Route::get('/operator/peminjaman', [PeminjamanController::class, 'operatorIndex'])->name('operator.peminjaman.index');
        Route::get('/operator/pengembalian', [PeminjamanController::class, 'daftarPengembalianMenunggu'])->name('operator.pengembalian.index');
        Route::post('/operator/peminjaman/{id}/verifikasi-peminjaman', [PeminjamanController::class, 'verifikasi'])->name('operator.peminjaman.verifikasi');
        Route::get('/operator/peminjaman/{id}/verifikasi-pengembalian', [PeminjamanController::class, 'verifikasiPengembalianForm'])
            ->name('operator.peminjaman.verifikasi-pengembalian');
        Route::post('/operator/peminjaman/{id}/verifikasi-pengembalian', [PeminjamanController::class, 'verifikasiPengembalianStore'])->name('operator.peminjaman.verifikasi-pengembalian.store');
        Route::get('/operator/peminjaman/daftar-dipinjam', [PeminjamanController::class, 'daftarSedangDipinjam'])
            ->name('operator.peminjaman.daftar-dipinjam');
    });

    /*
    |--------------------------------------------------------------------------
    | Role: Guru
    |--------------------------------------------------------------------------
    */
    Route::middleware('isGuru')->group(function () {
        Route::get('/guru/dashboard', [DashboardController::class, 'guru'])->name('guru.dashboard');
        Route::resource('peminjaman', PeminjamanController::class);
        Route::post('/peminjaman/{id}/kembalikan', [PeminjamanController::class, 'returnRequest'])->name('peminjaman.kembalikan');
        Route::post('/peminjaman/{id}/perpanjang', [PeminjamanController::class, 'perpanjang'])->name('peminjaman.perpanjang');
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
