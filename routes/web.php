<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// --- Controller Imports ---
use App\Http\Controllers\Auth\AuthenticatedSessionController; // Perbaikan nama controller
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangQrCodeController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\KategoriBarangController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PemeliharaanController;
use App\Http\Controllers\StokOpnameController;
use App\Http\Controllers\ArsipBarangController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\RekapStokController;       // Tambahkan jika belum ada
use App\Http\Controllers\BarangStatusController;   // Tambahkan jika belum ada
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
    return view('auth.login'); // Pastikan view login Anda ada di auth/login.blade.php
});

Route::middleware('guest')->group(function () {
    // Ini akan mengambil route dari routes/auth.php (login, register, forgot password, dll.)
    // Pastikan Anda sudah menjalankan php artisan ui bootstrap --auth atau sejenisnya jika menggunakan UI bawaan
    require __DIR__ . '/auth.php';
});

// Grup utama yang memerlukan autentikasi
Route::middleware(['auth'])->group(function () {

    Route::get('/redirect-dashboard', [DashboardController::class, 'redirectDashboard'])->name('redirect-dashboard');

    // Logout - pastikan ini sesuai dengan setup autentikasi Anda (misal, Laravel Breeze)
    // Jika menggunakan Breeze, route POST /logout biasanya sudah ada.
    // Route GET ini mungkin tidak diperlukan atau bisa jadi halaman konfirmasi logout.
    Route::get('/logout-confirmation', [AuthenticatedSessionController::class, 'showLogoutConfirmation'])->name('logout.show'); // Contoh
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // =====================================================================
    //          SUMBER DAYA UTAMA (DIKONTROL OLEH POLICY)
    // =====================================================================

    // --- Manajemen Barang & Aset ---
    Route::resource('barang', BarangController::class);
    // Custom routes untuk Barang
    Route::get('/barang/{barang}/input-serial', [BarangController::class, 'inputSerialForm'])->name('barang.input-serial');
    Route::post('/barang/{barang}/input-serial', [BarangController::class, 'storeSerialNumbers'])->name('barang.store-serial');
    Route::get('/barang/{barang}/suggest-serials', [BarangController::class, 'suggestSerials'])->name('barang.suggest-serials');
    Route::delete('/barang/{barang}/cancel-create', [BarangController::class, 'cancelCreate'])->name('barang.cancel-create');
    Route::get('/barang-export-all', [BarangController::class, 'export'])->name('barang.export.all');
    Route::post('/barang-import-all', [BarangController::class, 'import'])->name('barang.import.all');
    Route::get('/barang/{barang}/edit-step1', [BarangController::class, 'editStep1'])->name('barang.edit-step1');
    Route::put('/barang/{barang}/update-step1', [BarangController::class, 'updateStep1'])->name('barang.update-step1');

    Route::get('/barang/suggest-serials-for-new', [BarangController::class, 'suggestSerialsForNew'])->name('barang.suggest-serials-for-new');
    // Hanya SATU definisi resource untuk barang-qr-code
    // Ini akan secara otomatis membuat rute untuk index, create, store, show, edit, update, destroy
    // dengan parameter {barangQrCode} karena kustomisasi ->parameters()
    Route::resource('barang-qr-code', BarangQrCodeController::class)
        ->parameters(['barang-qr-code' => 'barangQrCode']); // Pastikan parameter ini 'barangQrCode' (camelCase)

    // Rute custom untuk BarangQrCodeController yang tidak termasuk dalam resource standar:
    Route::post('barang-qr-code/{barangQrCode}/mutasi', [BarangQrCodeController::class, 'mutasi'])->name('barang-qr-code.mutasi');
    Route::post('barang-qr-code/{barangQrCode}/archive', [BarangQrCodeController::class, 'archive'])->name('barang-qr-code.archive');
    Route::post('barang-qr-code/{barangQrCode}/restore', [BarangQrCodeController::class, 'restore'])->name('barang-qr-code.restore');
    Route::get('barang-qr-code/{barangQrCode}/download', [BarangQrCodeController::class, 'download'])->name('barang-qr-code.download');
    Route::post('barang-qr-code/print-multiple', [BarangQrCodeController::class, 'printMultiple'])->name('barang-qr-code.print-multiple'); // Tidak ada {barangQrCode}
    Route::get('/barang-qr-code/export-pdf', [BarangQrCodeController::class, 'exportPdf'])->name('barang-qr-code.export-pdf'); // Tidak ada {barangQrCode}
    Route::get('barang-qr-code/export-excel', [BarangQrCodeController::class, 'exportExcel'])->name('barang-qr-code.export-excel'); // Tidak ada {barangQrCode}
    Route::get('/barang-qr-code/{barangQrCode}/assign-personal-form', [BarangQrCodeController::class, 'showAssignPersonalForm'])
        ->name('barang-qr-code.show-assign-personal-form');
    Route::post('/barang-qr-code/{barangQrCode}/assign-personal', [BarangQrCodeController::class, 'assignPersonal'])
        ->name('barang-qr-code.assign-personal');
    Route::get('/barang-qr-code/{barangQrCode}/return-personal-form', [BarangQrCodeController::class, 'showReturnFromPersonalForm'])
        ->name('barang-qr-code.show-return-personal-form');
    Route::post('/barang-qr-code/{barangQrCode}/return-personal', [BarangQrCodeController::class, 'returnFromPersonal'])
        ->name('barang-qr-code.return-personal');
    Route::get('/barang-qr-code/{barangQrCode}/transfer-personal-form', [BarangQrCodeController::class, 'showTransferPersonalForm'])
        ->name('barang-qr-code.show-transfer-personal-form');
    Route::post('/barang-qr-code/{barangQrCode}/transfer-personal', [BarangQrCodeController::class, 'transferPersonal'])
        ->name('barang-qr-code.transfer-personal');




    // --- Manajemen Aktivitas Aset ---
    Route::resource('pemeliharaan', PemeliharaanController::class);
    Route::resource('stok-opname', StokOpnameController::class); // Untuk Admin dan Operator (dikontrol Policy)

    // --- Manajemen Peminjaman (Struktur Terpadu) ---
    Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
        Route::get('/', [PeminjamanController::class, 'index'])->name('index');
        Route::get('/create', [PeminjamanController::class, 'create'])->name('create'); // Untuk Guru
        Route::post('/', [PeminjamanController::class, 'store'])->name('store'); // Untuk Guru
        Route::get('/{peminjaman}', [PeminjamanController::class, 'show'])->name('show');
        Route::delete('/{peminjaman}', [PeminjamanController::class, 'destroy'])->name('destroy'); // Untuk Guru membatalkan

        // Rute aksi untuk Operator & Admin (dilindungi Policy di controller)
        Route::post('/{peminjaman}/approve', [PeminjamanController::class, 'approve'])->name('approve');
        Route::post('/{peminjaman}/reject', [PeminjamanController::class, 'reject'])->name('reject');
        Route::post('/detail/{detail}/konfirmasi-pengambilan', [PeminjamanController::class, 'konfirmasiPengambilan'])->name('detail.konfirmasiPengambilan');
        Route::post('/detail/{detail}/verifikasi-pengembalian', [PeminjamanController::class, 'verifikasiPengembalian'])->name('detail.verifikasiPengembalian');

        // Rute tambahan untuk Guru (tampilan berbeda dari index utama)
        Route::get('/guru/berlangsung', [PeminjamanController::class, 'peminjamanBerlangsungGuru'])->name('guru.berlangsung'); // Method baru di controller
        Route::get('/guru/riwayat', [PeminjamanController::class, 'riwayatPeminjamanGuru'])->name('guru.riwayat');         // Method baru di controller
        // Route::post('/guru/{peminjaman}/ajukan-pengembalian', [PeminjamanController::class, 'ajukanPengembalianGuru'])->name('guru.ajukanPengembalian'); // Jika ada
        // Route::post('/guru/{peminjaman}/ajukan-perpanjangan', [PeminjamanController::class, 'ajukanPerpanjanganGuru'])->name('guru.ajukanPerpanjangan'); // Jika ada
    });

    // =====================================================================
    //          RUTE EKSKLUSIF BERDASARKAN PERAN
    // =====================================================================

    // --- Rute Khusus Admin ---
    Route::middleware('isAdmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::resource('users', UserController::class);
        Route::resource('pengaturan', PengaturanController::class)->only(['index', 'store']);
        Route::resource('rekap-stok', RekapStokController::class);
        Route::resource('barang-status', BarangStatusController::class)->only(['index', 'show']);;
        Route::resource('arsip-barang', ArsipBarangController::class)->only(['index', 'show']);
        Route::post('arsip-barang/{arsipBarang}/restore', [ArsipBarangController::class, 'restore'])->name('arsip-barang.restore');
        Route::resource('pemeliharaan', PemeliharaanController::class)->parameters([
            'pemeliharaan' => 'pemeliharaan'
        ]);
        Route::post('pemeliharaan/{id}/restore', [PemeliharaanController::class, 'restore'])->name('pemeliharaan.restore');

        // Grup untuk Stok Opname
        Route::prefix('stok-opname')->name('stok-opname.')->group(function () {
            Route::get('/', [StokOpnameController::class, 'index'])->name('index');
            Route::get('/create', [StokOpnameController::class, 'create'])->name('create');
            Route::post('/', [StokOpnameController::class, 'store'])->name('store');
            Route::get('/{stokOpname}', [StokOpnameController::class, 'show'])->name('show');
            Route::get('/{stokOpname}/edit', [StokOpnameController::class, 'edit'])->name('edit');
            Route::put('/{stokOpname}', [StokOpnameController::class, 'update'])->name('update');
            Route::delete('/{stokOpname}', [StokOpnameController::class, 'destroy'])->name('destroy');

            // Route untuk restore, finalize, cancel (parameter {stokOpname} karena menerima ID atau model)
            Route::post('/{stokOpname}/restore', [StokOpnameController::class, 'restore'])->name('restore'); // Diubah dari {id} ke {stokOpname} untuk konsistensi
            Route::post('/{stokOpname}/finalize', [StokOpnameController::class, 'finalize'])->name('finalize');
            Route::post('/{stokOpname}/cancel', [StokOpnameController::class, 'cancel'])->name('cancel');

            // Route untuk AJAX (parameter {stokOpname} untuk ID sesi SO)
            // Pastikan nama parameter {stokOpname} dan {detail} konsisten dengan controller dan view
            Route::put('/{stokOpname}/detail/{detail}', [StokOpnameController::class, 'updateDetail'])->name('updateDetail');
            Route::get('/search-barang-qr', [StokOpnameController::class, 'searchBarangQr'])->name('search-barang-qr'); // Tidak perlu {stokOpname} di URL jika dikirim via query param
            Route::post('/add-barang-temuan', [StokOpnameController::class, 'addBarangTemuan'])->name('add-barang-temuan'); // Tidak perlu {stokOpname} di URL jika dikirim via form data
        });

        // Route untuk Kategori Barang
        Route::resource('kategori-barang', KategoriBarangController::class)->parameters([
            'kategori_barang' => 'kategoriBarang'
        ]);
        Route::get('kategori-barang/{kategoriBarang}/items', [KategoriBarangController::class, 'getItems'])
            ->name('kategori-barang.items');
        Route::get('kategori-barang-stats/all', [KategoriBarangController::class, 'getStatistics'])
            ->name('kategori-barang.statistics');

        // Tambahkan Route untuk Ruangan di sini
        Route::resource('ruangan', RuanganController::class)->parameters([
            'ruangan' => 'ruangan' // Sesuaikan nama parameter jika berbeda di controller
        ]);

        // Untuk User
        // URI: admin/users/{id}/restore
        // Nama Route: admin.users.restore
        Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');

        // Untuk Ruangan
        // URI: admin/ruangan/{id}/restore
        // Nama Route: admin.ruangan.restore
        Route::post('ruangan/{id}/restore', [RuanganController::class, 'restore'])->name('ruangan.restore');

        // Untuk Kategori Barang
        // URI: admin/kategori-barang/{id}/restore
        // Nama Route: admin.kategori-barang.restore
        Route::post('kategori-barang/{id}/restore', [KategoriBarangController::class, 'restore'])->name('kategori-barang.restore');
        // Jika ada route custom untuk ruangan, tambahkan di sini juga, misalnya:
        // Route::get('ruangan/{ruangan}/inventory', [RuanganController::class, 'inventory'])->name('ruangan.inventory');


        Route::get('log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas.index');
        Route::get('log-aktivitas/{logAktivitas}', [LogAktivitasController::class, 'show'])->name('log-aktivitas.show');
    });

    // --- Rute Khusus Operator ---
    Route::middleware('isOperator')->prefix('operator')->name('operator.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'operator'])->name('dashboard');
        // Operator akan mengakses pemeliharaan & stok opname via rute utama, dikontrol Policy
        // Jika ada laporan spesifik operator:
        // Route::get('laporan/peminjaman', [LaporanController::class, 'peminjamanOperator'])->name('laporan.peminjaman');
    });

    // --- Rute Khusus Guru ---
    Route::middleware('isGuru')->prefix('guru')->name('guru.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'guru'])->name('dashboard');
        // Rute AJAX untuk form peminjaman guru
        Route::get('/get-units-by-ruangan/{ruangan}', [PeminjamanController::class, 'getAvailableUnitsByRuangan'])->name('peminjaman.getAvailableUnitsByRuangan'); // Nama lebih spesifik
    });

    // Dark Mode Toggle
    Route::post('/set-dark-mode', function (Request $request) {
        session(['darkMode' => $request->input('darkMode', 'light')]);
        return response()->json(['success' => true]);
    })->name('set-dark-mode');
});
