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
use App\Http\Controllers\KategoriBarangController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BarangQrCodeController;
use App\Http\Controllers\ArsipBarangController;
use Illuminate\Http\Request;

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

        // Peminjaman Routes for Guru (using PeminjamanGuruController)
        Route::prefix('guru')->group(function () {
            Route::get('/peminjaman', [PeminjamanGuruController::class, 'index'])->name('guru.peminjaman.index');
            Route::get('/peminjaman/create', [PeminjamanGuruController::class, 'create'])->name('guru.peminjaman.create');
            Route::post('/peminjaman', [PeminjamanGuruController::class, 'store'])->name('guru.peminjaman.store');
            Route::get('/peminjaman/{id}', [PeminjamanGuruController::class, 'show'])->name('guru.peminjaman.show');
            Route::delete('/peminjaman/{id}', [PeminjamanGuruController::class, 'destroy'])->name('guru.peminjaman.destroy');
            Route::get('/berlangsung', [PeminjamanGuruController::class, 'peminjamanBerlangsung'])->name('guru.peminjaman.berlangsung');
            Route::post('/peminjaman/{id}/ajukan-pengembalian', [PeminjamanGuruController::class, 'ajukanPengembalian'])->name('guru.peminjaman.ajukanPengembalian');
            Route::post('/peminjaman/{id}/ajukan-perpanjangan', [PeminjamanGuruController::class, 'ajukanPerpanjangan'])->name('guru.peminjaman.ajukanPerpanjangan');
            Route::get('/barang/{ruanganId}', [PeminjamanGuruController::class, 'getBarangByRuangan'])->name('guru.barang.byRuangan');




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

        // Peminjaman Routes for Operator (from new version with PeminjamanOperatorController)
        Route::prefix('operator')->group(function () {
            // Daftar peminjaman untuk operator
            Route::get('/peminjaman', [PeminjamanOperatorController::class, 'index'])->name('operator.peminjaman.index');

            // Detail peminjaman
            Route::get('/peminjaman/{id}', [PeminjamanOperatorController::class, 'show'])->name('operator.peminjaman.show');

            // Export peminjaman (perlu disesuaikan dengan controller yang benar)
            Route::get('/peminjaman/export', [BarangController::class, 'exportOperator'])->name('operator.peminjaman.export');

            // Persetujuan/penolakan item individual
            Route::post('/peminjaman/item/{detailId}/setujui', [PeminjamanOperatorController::class, 'setujuiItem'])
                ->name('operator.peminjaman.setujui-item');
            Route::post('/peminjaman/item/{detailId}/tolak', [PeminjamanOperatorController::class, 'tolakItem'])
                ->name('operator.peminjaman.tolak-item');

            // Persetujuan semua item dalam satu peminjaman
            Route::post('/peminjaman/{peminjamanId}/setujui-semua', [PeminjamanOperatorController::class, 'setujuiSemuaItem'])
                ->name('operator.peminjaman.setujui-semua');

            // Konfirmasi pengambilan dan pengembalian item
            Route::post('/peminjaman/konfirmasi-pengambilan', [PeminjamanOperatorController::class, 'konfirmasiPengambilanItem'])
                ->name('operator.peminjaman.konfirmasi-pengambilan-item');
            Route::post('/peminjaman/verifikasi-pengembalian', [PeminjamanOperatorController::class, 'verifikasiPengembalianItem'])
                ->name('operator.peminjaman.verifikasi-pengembalian-item');

            // Daftar peminjaman berlangsung
            Route::get('/peminjaman-berlangsung', [PeminjamanOperatorController::class, 'peminjamanBerlangsungOperator'])
                ->name('operator.peminjaman.berlangsung');

            // Daftar pengembalian yang menunggu
            Route::get('/pengembalian', [PeminjamanOperatorController::class, 'daftarPengembalianMenunggu'])
                ->name('operator.peminjaman.pengembalian');

            // Daftar item terlambat
            Route::get('/item-terlambat', [PeminjamanOperatorController::class, 'daftarItemTerlambat'])
                ->name('operator.peminjaman.item-terlambat');

            // Laporan peminjaman
            Route::get('/laporan-peminjaman', [PeminjamanOperatorController::class, 'laporanPeminjaman'])
                ->name('operator.peminjaman.laporan');
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

        // Admin - Export & Import Barang (from old version)
        Route::get('/barang/export', [BarangController::class, 'export'])->name('barang.export');
        Route::post('/barang/import', [BarangController::class, 'import'])->name('barang.import');

        Route::get('/barang-qr-code/export/excel', [BarangQrCodeController::class, 'exportExcel'])->name('barang-qr-code.export.excel');
        Route::get('/barang-qr-code/export/pdf', [BarangQrCodeController::class, 'exportPdf'])->name('barang-qr-code.export.pdf');

        Route::post('/barang/{barang}/qrcode', [BarangQrCodeController::class, 'store'])->name('barang.qrcode.store');
        // Route::get('/barang', [BarangController::class, 'index'])->name('barang.index');
        Route::get('/barang/create', [BarangController::class, 'create'])->name('barang.create');
        Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
        Route::get('/barang/{id}/input-serial', [BarangController::class, 'inputSerialForm'])->name('barang.input-serial');
        Route::post('/barang/{id}/input-serial', [BarangController::class, 'storeSerialNumbers'])->name('barang.store-serial');
        Route::get('/barang/{id}', [BarangController::class, 'show'])->name('barang.show');

        Route::get('/barang/{id}/suggest-serials', [BarangController::class, 'suggestSerials'])->name('barang.suggest-serials');

        Route::get('/barang/{id}/edit', [BarangController::class, 'edit'])->name('barang.edit');
        Route::put('/barang/{id}', [BarangController::class, 'update'])->name('barang.update');

        // Tambahkan route baru untuk wizard step 1 edit
        Route::get('/barang/{id}/edit-step1', [BarangController::class, 'editStep1'])->name('barang.edit-step1');
        Route::put('/barang/{id}/update-step1', [BarangController::class, 'updateStep1'])->name('barang.update-step1');

        // Route untuk batal pembuatan
        Route::delete('/barang/{id}/cancel', [BarangController::class, 'cancel'])->name('barang.cancel');
        Route::delete('/barang/{id}/cancel-create', [BarangController::class, 'cancelCreate'])
            ->name('barang.cancel-create');

        Route::delete('/barang-qrcode/{id}', [BarangQrCodeController::class, 'destroy'])->name('barang-qrcode.destroy');
        Route::get('/arsip-barang', [ArsipBarangController::class, 'index'])->name('arsip-barang.index');



        // Admin - Pengaturan
        Route::resource('pengaturan', PengaturanController::class)->only(['index']);
        Route::post('/pengaturan/update', [PengaturanController::class, 'update'])->name('pengaturan.update');

        // Peminjaman Routes for Admin (from new version with PeminjamanAdminController)
        Route::prefix('admin/peminjaman')->group(function () {
            Route::get('/', [PeminjamanAdminController::class, 'index'])->name('admin.peminjaman.index');
            Route::get('/export', [BarangController::class, 'exportOperator'])->name('admin.peminjaman.export');
            Route::get('/overdue', [PeminjamanAdminController::class, 'exportOperator'])->name('admin.peminjaman.overdue');
            Route::get('/report', [PeminjamanAdminController::class, 'exportOperator'])->name('admin.peminjaman.report');
            Route::get('/exportPdf', [PeminjamanAdminController::class, 'exportPdf'])->name('admin.peminjaman.exportPdf');
            Route::get('/exportPdf', [PeminjamanAdminController::class, 'exportExcel'])->name('admin.peminjaman.exportExcel');



            Route::get('/{id}', [PeminjamanAdminController::class, 'show'])->name('admin.peminjaman.show');
        });

        Route::get('/ruangan/{id}', [RuanganController::class, 'show'])->name('ruangan.show');
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

    /**
     * Laravel Route for saving dark mode preference to session
     * Add this to your web.php routes file
     */

    // Route for saving dark mode preference to session
    Route::post('/set-dark-mode', function (Request $request) {
        // Validate the request
        $validated = $request->validate([
            'darkMode' => 'required|string|in:dark,light',
        ]);

        // Store in session
        session(['darkMode' => $validated['darkMode']]);

        // Return success response
        return response()->json(['success' => true, 'mode' => $validated['darkMode']]);
    })->middleware('web');


    Route::resource('kategori-barang', KategoriBarangController::class);
    Route::get('kategori-barang/{kategoriBarang}/items', [KategoriBarangController::class, 'getItems'])->name('kategori-barang.items');
    Route::get('kategori-barang-statistics', [KategoriBarangController::class, 'getStatistics'])->name('kategori-barang.statistics');
});
