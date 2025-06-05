<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// --- Controller Imports ---
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangQrCodeController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\KategoriBarangController;
use App\Http\Controllers\PeminjamanController; // Penting untuk fitur peminjaman
use App\Http\Controllers\PemeliharaanController;
use App\Http\Controllers\StokOpnameController;
use App\Http\Controllers\ArsipBarangController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\RekapStokController;
use App\Http\Controllers\BarangStatusController;
use App\Http\Controllers\LogAktivitasController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('redirect-dashboard');
    }
    return view('auth.login');
});

Route::middleware('guest')->group(function () {
    require __DIR__ . '/auth.php';
});

// Grup utama yang memerlukan autentikasi
Route::middleware(['auth'])->group(function () {

    Route::get('/redirect-dashboard', [DashboardController::class, 'redirectDashboard'])->name('redirect-dashboard');

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // =====================================================================
    //                          SUMBER DAYA UTAMA
    // =====================================================================

    // --- Manajemen Barang & Aset (Rute Lama Anda, bisa Anda review kembali) ---
    Route::resource('barang', BarangController::class);
    Route::get('/barang/{barang}/input-serial', [BarangController::class, 'inputSerialForm'])->name('barang.input-serial');
    Route::post('/barang/{barang}/input-serial', [BarangController::class, 'storeSerialNumbers'])->name('barang.store-serial');
    Route::get('/barang/{barang}/suggest-serials', [BarangController::class, 'suggestSerials'])->name('barang.suggest-serials');
    Route::delete('/barang/{barang}/cancel-create', [BarangController::class, 'cancelCreate'])->name('barang.cancel-create');
    Route::get('/barang-export-all', [BarangController::class, 'export'])->name('barang.export.all');
    Route::post('/barang-import-all', [BarangController::class, 'import'])->name('barang.import.all');
    Route::get('/barang/{barang}/edit-step1', [BarangController::class, 'editStep1'])->name('barang.edit-step1');
    Route::put('/barang/{barang}/update-step1', [BarangController::class, 'updateStep1'])->name('barang.update-step1');
    Route::get('/barang/suggest-serials-for-new', [BarangController::class, 'suggestSerialsForNew'])->name('barang.suggest-serials-for-new');

    Route::resource('barang-qr-code', BarangQrCodeController::class)
        ->parameters(['barang-qr-code' => 'barangQrCode']);
    Route::post('barang-qr-code/{barangQrCode}/mutasi', [BarangQrCodeController::class, 'mutasi'])->name('barang-qr-code.mutasi');
    Route::post('barang-qr-code/{barangQrCode}/archive', [BarangQrCodeController::class, 'archive'])->name('barang-qr-code.archive');
    Route::post('barang-qr-code/{barangQrCode}/restore', [BarangQrCodeController::class, 'restore'])->name('barang-qr-code.restore');
    Route::get('barang-qr-code/{barangQrCode}/download', [BarangQrCodeController::class, 'download'])->name('barang-qr-code.download');
    Route::post('barang-qr-code/print-multiple', [BarangQrCodeController::class, 'printMultiple'])->name('barang-qr-code.print-multiple');
    Route::get('/barang-qr-code/export-pdf', [BarangQrCodeController::class, 'exportPdf'])->name('barang-qr-code.export-pdf');
    // Route::get('barang-qr-code/export-excel', [BarangQrCodeController::class, 'exportExcel'])->name('barang-qr-code.export-excel'); // Jika ada
    Route::get('/barang-qr-code/{barangQrCode}/assign-personal-form', [BarangQrCodeController::class, 'showAssignPersonalForm'])->name('barang-qr-code.show-assign-personal-form');
    Route::post('/barang-qr-code/{barangQrCode}/assign-personal', [BarangQrCodeController::class, 'assignPersonal'])->name('barang-qr-code.assign-personal');
    Route::get('/barang-qr-code/{barangQrCode}/return-personal-form', [BarangQrCodeController::class, 'showReturnFromPersonalForm'])->name('barang-qr-code.show-return-personal-form');
    Route::post('/barang-qr-code/{barangQrCode}/return-personal', [BarangQrCodeController::class, 'returnFromPersonal'])->name('barang-qr-code.return-personal');
    Route::get('/barang-qr-code/{barangQrCode}/transfer-personal-form', [BarangQrCodeController::class, 'showTransferPersonalForm'])->name('barang-qr-code.show-transfer-personal-form');
    Route::post('/barang-qr-code/{barangQrCode}/transfer-personal', [BarangQrCodeController::class, 'transferPersonal'])->name('barang-qr-code.transfer-personal');

    // --- Manajemen Aktivitas Aset ---
    // Route::resource('pemeliharaan', PemeliharaanController::class); // Sudah ada di grup Admin
    // Route::resource('stok-opname', StokOpnameController::class); // Sudah ada di grup Admin


    // =====================================================================
    //                      RUTE EKSKLUSIF BERDASARKAN PERAN
    // =====================================================================

    // --- Rute Khusus Admin ---
    Route::middleware(['isAdmin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::resource('users', UserController::class);
        Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();

        Route::resource('pengaturan', PengaturanController::class)->only(['index', 'store']);
        Route::resource('rekap-stok', RekapStokController::class)->only(['index', 'show']); // Admin biasanya hanya view rekap
        Route::resource('barang-status', BarangStatusController::class)->only(['index', 'show']);
        Route::resource('arsip-barang', ArsipBarangController::class)->only(['index', 'show']);
        Route::post('arsip-barang/{arsipBarang}/restore', [ArsipBarangController::class, 'restore'])->name('arsip-barang.restore')->withTrashed();

        Route::resource('pemeliharaan', PemeliharaanController::class); // Full resource untuk admin
        Route::post('pemeliharaan/{id}/restore', [PemeliharaanController::class, 'restore'])->name('pemeliharaan.restore')->withTrashed();

        Route::resource('stok-opname', StokOpnameController::class); // Full resource untuk admin
        Route::post('stok-opname/{stokOpname}/restore', [StokOpnameController::class, 'restore'])->name('stok-opname.restore')->withTrashed(); // Menggunakan {stokOpname}
        Route::post('stok-opname/{stokOpname}/finalize', [StokOpnameController::class, 'finalize'])->name('stok-opname.finalize');
        Route::post('stok-opname/{stokOpname}/cancel', [StokOpnameController::class, 'cancel'])->name('stok-opname.cancel');
        Route::put('stok-opname/{stokOpname}/detail/{detailStokOpname}', [StokOpnameController::class, 'updateDetail'])->name('stok-opname.updateDetail'); // Parameter disesuaikan
        Route::get('stok-opname-search-barang-qr', [StokOpnameController::class, 'searchBarangQr'])->name('stok-opname.search-barang-qr');
        Route::post('stok-opname-add-barang-temuan', [StokOpnameController::class, 'addBarangTemuan'])->name('stok-opname.add-barang-temuan');

        Route::resource('kategori-barang', KategoriBarangController::class);
        Route::post('kategori-barang/{id}/restore', [KategoriBarangController::class, 'restore'])->name('kategori-barang.restore')->withTrashed();
        Route::get('kategori-barang/{kategoriBarang}/items', [KategoriBarangController::class, 'getItems'])->name('kategori-barang.items');
        Route::get('kategori-barang-stats/all', [KategoriBarangController::class, 'getStatistics'])->name('kategori-barang.statistics');
        
        Route::resource('ruangan', RuanganController::class);
        Route::post('ruangan/{id}/restore', [RuanganController::class, 'restore'])->name('ruangan.restore')->withTrashed();
        Route::get('ruangan/{ruangan}/inventory', [RuanganController::class, 'inventory'])->name('ruangan.inventory');


        Route::get('log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas.index');
        Route::get('log-aktivitas/{logAktivitas}', [LogAktivitasController::class, 'show'])->name('log-aktivitas.show');

        // PEMINJAMAN (ADMIN)
        Route::resource('peminjaman', PeminjamanController::class); // Admin punya full control
        Route::post('peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve'])->name('peminjaman.approve');
        Route::post('peminjaman/{peminjaman}/reject', [PeminjamanController::class, 'reject'])->name('peminjaman.reject');
        Route::post('peminjaman/detail/{detailPeminjaman}/handover', [PeminjamanController::class, 'processItemHandover'])->name('peminjaman.item.handover');
        Route::post('peminjaman/detail/{detailPeminjaman}/return', [PeminjamanController::class, 'processItemReturn'])->name('peminjaman.item.return');
        Route::post('peminjaman/{peminjaman}/cancel-by-user', [PeminjamanController::class, 'cancelByUser'])->name('peminjaman.cancelByUser'); // Admin bisa cancel atas nama user
        Route::post('peminjaman/{id}/restore', [PeminjamanController::class, 'restore'])->name('peminjaman.restore')->withTrashed();
    });

    // --- Rute Khusus Operator ---
    Route::middleware(['isOperator'])->prefix('operator')->name('operator.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'operator'])->name('dashboard');

        // Akses Operator ke resource lain (seperti stok opname, pemeliharaan) akan diatur oleh Policy di controller utama
        // Tidak perlu duplikasi resource route di sini jika controllernya sama.
        // Cukup pastikan policy mengizinkan Operator untuk aksi yang relevan.

        // PEMINJAMAN (OPERATOR)
        Route::get('peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
        Route::get('peminjaman/{peminjaman}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
        Route::get('peminjaman/{peminjaman}/edit', [PeminjamanController::class, 'edit'])->name('peminjaman.edit'); // Untuk edit catatan operator
        Route::put('peminjaman/{peminjaman}', [PeminjamanController::class, 'update'])->name('peminjaman.update'); // Untuk update catatan operator

        Route::post('peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve'])->name('peminjaman.approve');
        Route::post('peminjaman/{peminjaman}/reject', [PeminjamanController::class, 'reject'])->name('peminjaman.reject');
        Route::post('peminjaman/detail/{detailPeminjaman}/handover', [PeminjamanController::class, 'processItemHandover'])->name('peminjaman.item.handover');
        Route::post('peminjaman/detail/{detailPeminjaman}/return', [PeminjamanController::class, 'processItemReturn'])->name('peminjaman.item.return');
    });

    // --- Rute Khusus Guru ---
    Route::middleware(['isGuru'])->prefix('guru')->name('guru.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'guru'])->name('dashboard');

        // PEMINJAMAN (GURU)
        Route::resource('peminjaman', PeminjamanController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('peminjaman/{peminjaman}/cancel-by-user', [PeminjamanController::class, 'cancelByUser'])->name('peminjaman.cancelByUser');
        
        // Rute lama Guru (jika masih relevan dan ada methodnya di controller)
        // Route::get('peminjaman/berlangsung', [PeminjamanController::class, 'peminjamanBerlangsungGuru'])->name('peminjaman.berlangsung');
        // Route::get('peminjaman/riwayat', [PeminjamanController::class, 'riwayatPeminjamanGuru'])->name('peminjaman.riwayat');
        // Route::get('/get-units-by-ruangan/{ruangan}', [PeminjamanController::class, 'getAvailableUnitsByRuangan'])->name('peminjaman.getAvailableUnitsByRuangan');
    });


    // Dark Mode Toggle
    Route::post('/set-dark-mode', function (Request $request) {
        session(['darkMode' => $request->input('darkMode', 'light')]);
        return response()->json(['success' => true]);
    })->name('set-dark-mode');
});