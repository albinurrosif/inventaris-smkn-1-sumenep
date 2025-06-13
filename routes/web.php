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

use App\Http\Controllers\RekapStokController; // Tambahkan jika belum ada

use App\Http\Controllers\BarangStatusController; // Tambahkan jika belum ada

use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\Auth\PasswordController;



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

    // Rute logout standar menggunakan metode POST
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');



    // =====================================================================

    //    SUMBER DAYA UTAMA (DIKONTROL OLEH POLICY)

    // =====================================================================


    // --- Rute untuk Manajemen Profil ---
    // --- Rute untuk Manajemen Profil ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rute khusus untuk update password, mengarah ke PasswordController
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // --- Rute untuk Halaman Aktivitas Saya ---
    Route::get('/profil/aktivitas-saya', [ProfileController::class, 'myActivity'])->name('profile.activity');






    // =====================================================================

    //    RUTE EKSKLUSIF BERDASARKAN PERAN

    // =====================================================================

    // --- Rute Khusus Admin ---

    Route::middleware('isAdmin')->prefix('admin')->name('admin.')->group(function () {

        Route::get('dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        // user
        Route::resource('users', UserController::class);
        Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');

        // Tambahkan Route untuk Ruangan di sini
        Route::resource('ruangan', RuanganController::class)->parameters([
            'ruangan' => 'ruangan' // Sesuaikan nama parameter jika berbeda di controller
        ]);
        Route::post('ruangan/{id}/restore', [RuanganController::class, 'restore'])->name('ruangan.restore');

        // Route untuk Kategori Barang
        Route::resource('kategori-barang', KategoriBarangController::class)->parameters([
            'kategori_barang' => 'kategoriBarang'
        ]);
        Route::get('kategori-barang/{kategoriBarang}/items', [KategoriBarangController::class, 'getItems'])
            ->name('kategori-barang.items');
        Route::get('kategori-barang-stats/all', [KategoriBarangController::class, 'getStatistics'])
            ->name('kategori-barang.statistics');
        Route::post('kategori-barang/{id}/restore', [KategoriBarangController::class, 'restore'])->name('kategori-barang.restore');

        // --- MANAJEMEN BARANG (ADMIN - AKSES PENUH) -- {{ DIPINDAHKAN DARI GLOBAL }} ---
        Route::resource('barang', BarangController::class);
        Route::get('barang/{barang}/print-all-qrcodes', [BarangController::class, 'printAllQrCodes'])->name('barang.print-all-qrcodes');
        Route::get('/barang/suggest-serials-for-new', [BarangController::class, 'suggestSerialsForNew'])->name('barang.suggest-serials-for-new');
        Route::post('/barang/import-all', [BarangController::class, 'importAll'])->name('barang.import.all');
        Route::resource('barang-qr-code', BarangQrCodeController::class)->parameters(['barang-qr-code' => 'barangQrCode'])->withTrashed();;
        // Custom routes untuk Barang & BarangQrCode
        Route::get('/barang-qr-code/search-for-maintenance', [App\Http\Controllers\BarangQrCodeController::class, 'searchForMaintenance'])->name('barang-qr-code.search-for-maintenance');

        Route::post('barang-qr-code/{barangQrCode}/mutasi', [BarangQrCodeController::class, 'mutasi'])->name('barang-qr-code.mutasi');
        Route::post('barang-qr-code/{barangQrCode}/archive', [BarangQrCodeController::class, 'archive'])->name('barang-qr-code.archive');
        Route::post('barang-qr-code/{barangQrCode}/restore', [BarangQrCodeController::class, 'restore'])->name('barang-qr-code.restore');
        Route::get('barang-qr-code/{barangQrCode}/download', [BarangQrCodeController::class, 'download'])->name('barang-qr-code.download');
        Route::post('barang-qr-code/print-multiple', [BarangQrCodeController::class, 'printMultiple'])->name('barang-qr-code.print-multiple');
        Route::get('/barang-qr-code/export-pdf', [BarangQrCodeController::class, 'exportPdf'])->name('barang-qr-code.export-pdf');
        Route::get('barang-qr-code/export-excel', [BarangQrCodeController::class, 'exportExcel'])->name('barang-qr-code.export-excel');
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



        // PEMINJAMAN (ADMIN)
        Route::resource('peminjaman', PeminjamanController::class); // Admin punya full control

        Route::post('peminjaman/detail/{detailPeminjaman}/approve-item', [PeminjamanController::class, 'approveItem'])->name('peminjaman.item.approve');
        Route::post('peminjaman/detail/{detailPeminjaman}/reject-item', [PeminjamanController::class, 'rejectItem'])->name('peminjaman.item.reject');
        Route::post('peminjaman/{peminjaman}/finalize', [PeminjamanController::class, 'finalizeApproval'])->name('peminjaman.finalize');
        Route::post('peminjaman/detail/{detailPeminjaman}/handover', [PeminjamanController::class, 'processItemHandover'])->name('peminjaman.item.handover');
        Route::post('peminjaman/detail/{detailPeminjaman}/return', [PeminjamanController::class, 'processItemReturn'])->name('peminjaman.item.return');
        Route::post('peminjaman/{peminjaman}/cancel-by-user', [PeminjamanController::class, 'cancelByUser'])->name('peminjaman.cancelByUser'); // Admin bisa cancel atas nama user
        Route::post('peminjaman/{id}/restore', [PeminjamanController::class, 'restore'])->name('peminjaman.restore')->withTrashed();

        // Pemeliharaan (Admin punya full control)
        Route::resource('pemeliharaan', PemeliharaanController::class)->parameters([
            'pemeliharaan' => 'pemeliharaan'
        ]);
        Route::post('pemeliharaan/{id}/restore', [PemeliharaanController::class, 'restore'])->name('pemeliharaan.restore');

        // Grup untuk Stok Opname
        Route::prefix('stok-opname')->name('stok-opname.')->group(function () {
            Route::get('/', [StokOpnameController::class, 'index'])->name('index');
            Route::get('/create', [StokOpnameController::class, 'create'])->name('create');

            // PINDAHKAN ROUTE SPESIFIK INI KE ATAS
            Route::get('/search-barang-qr', [StokOpnameController::class, 'searchBarangQr'])->name('search-barang-qr'); // Tidak perlu {stokOpname} di URL jika dikirim via query param
            Route::post('/add-barang-temuan', [StokOpnameController::class, 'addBarangTemuan'])->name('add-barang-temuan'); // Tidak perlu {stokOpname} di URL jika dikirim via form data

            // ROUTE UMUM/DINAMIS TETAP DI BAWAH
            Route::post('/', [StokOpnameController::class, 'store'])->name('store');
            Route::get('/{stokOpname}', [StokOpnameController::class, 'show'])->name('show');
            Route::get('/{stokOpname}/edit', [StokOpnameController::class, 'edit'])->name('edit');
            Route::put('/{stokOpname}', [StokOpnameController::class, 'update'])->name('update');
            Route::delete('/{stokOpname}', [StokOpnameController::class, 'destroy'])->name('destroy');
            Route::post('/{stokOpname}/restore', [StokOpnameController::class, 'restore'])->name('restore'); // Diubah dari {id} ke {stokOpname} untuk konsistensi
            Route::post('/{stokOpname}/finalize', [StokOpnameController::class, 'finalize'])->name('finalize');
            Route::post('/{stokOpname}/cancel', [StokOpnameController::class, 'cancel'])->name('cancel');
            Route::put('/{stokOpname}/detail/{detail}', [StokOpnameController::class, 'updateDetail'])->name('updateDetail');
        });

        // Grup untuk Rekap Stok
        Route::resource('rekap-stok', RekapStokController::class);

        // Barang Status
        Route::resource('barang-status', BarangStatusController::class)->only(['index', 'show']);;

        // Arsip Barang
        Route::resource('arsip-barang', ArsipBarangController::class)->only(['index', 'show']);
        Route::post('arsip-barang/{arsipBarang}/restore', [ArsipBarangController::class, 'restore'])->name('arsip-barang.restore');

        // Log Aktivitas
        Route::get('log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas.index');
        Route::get('log-aktivitas/{logAktivitas}', [LogAktivitasController::class, 'show'])->name('log-aktivitas.show');

        // Pengaturan Umum
        Route::resource('pengaturan', PengaturanController::class)->only(['index', 'store']);

        // Grup Laporan untuk Admin
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/inventaris', [App\Http\Controllers\LaporanController::class, 'inventaris'])->name('inventaris');
            Route::get('/inventaris/pdf', [App\Http\Controllers\LaporanController::class, 'exportInventarisPdf'])->name('inventaris.pdf');
            Route::get('/inventaris/excel', [App\Http\Controllers\LaporanController::class, 'exportInventarisExcel'])->name('inventaris.excel');

            Route::get('/peminjaman', [App\Http\Controllers\LaporanController::class, 'peminjaman'])->name('peminjaman');
            Route::get('/peminjaman/pdf', [App\Http\Controllers\LaporanController::class, 'exportPeminjamanPdf'])->name('peminjaman.pdf');
            Route::get('/peminjaman/excel', [App\Http\Controllers\LaporanController::class, 'exportPeminjamanExcel'])->name('peminjaman.excel');

            Route::get('/pemeliharaan', [App\Http\Controllers\LaporanController::class, 'pemeliharaan'])->name('pemeliharaan');
            Route::get('/pemeliharaan/pdf', [App\Http\Controllers\LaporanController::class, 'exportPemeliharaanPdf'])->name('pemeliharaan.pdf');
            Route::get('/pemeliharaan/excel', [App\Http\Controllers\LaporanController::class, 'exportPemeliharaanExcel'])->name('pemeliharaan.excel');
        });

        Route::resource('mutasi-barang', App\Http\Controllers\MutasiBarangController::class)->only(['index', 'show']);

        Route::get('/pengaturan', [App\Http\Controllers\PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::post('/pengaturan', [App\Http\Controllers\PengaturanController::class, 'update'])->name('pengaturan.update');
    });





    // --- Rute Khusus Operator ---

    Route::middleware('isOperator')->prefix('operator')->name('operator.')->group(function () {

        Route::get('dashboard', [DashboardController::class, 'operator'])->name('dashboard');


        Route::resource('ruangan', RuanganController::class)->parameters([
            'ruangan' => 'ruangan' // Sesuaikan nama parameter jika berbeda di controller
        ]);

        Route::resource('kategori-barang', KategoriBarangController::class)->parameters([
            'kategori_barang' => 'kategoriBarang'
        ]);

        Route::resource('barang', BarangController::class);
        Route::get('barang/{barang}/print-all-qrcodes', [BarangController::class, 'printAllQrCodes'])->name('barang.print-all-qrcodes');

        Route::resource('barang-qr-code', BarangQrCodeController::class)->parameters(['barang-qr-code' => 'barangQrCode'])->withTrashed();;
        Route::post('barang-qr-code/{barangQrCode}/mutasi', [BarangQrCodeController::class, 'mutasi'])->name('barang-qr-code.mutasi');
        Route::post('barang-qr-code/{barangQrCode}/archive', [BarangQrCodeController::class, 'archive'])->name('barang-qr-code.archive');
        Route::post('barang-qr-code/{barangQrCode}/restore', [BarangQrCodeController::class, 'restore'])->name('barang-qr-code.restore');
        Route::get('barang-qr-code/{barangQrCode}/download', [BarangQrCodeController::class, 'download'])->name('barang-qr-code.download');
        Route::post('barang-qr-code/print-multiple', [BarangQrCodeController::class, 'printMultiple'])->name('barang-qr-code.print-multiple');
        Route::get('/barang-qr-code/export-pdf', [BarangQrCodeController::class, 'exportPdf'])->name('barang-qr-code.export-pdf');
        Route::get('barang-qr-code/export-excel', [BarangQrCodeController::class, 'exportExcel'])->name('barang-qr-code.export-excel');
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

        Route::resource('pemeliharaan', PemeliharaanController::class)->parameters([
            'pemeliharaan' => 'pemeliharaan'
        ]);

        // PEMINJAMAN (OPERATOR)
        Route::get('peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
        Route::get('peminjaman/{peminjaman}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
        Route::get('peminjaman/{peminjaman}/edit', [PeminjamanController::class, 'edit'])->name('peminjaman.edit'); // Untuk edit catatan operator
        Route::put('peminjaman/{peminjaman}', [PeminjamanController::class, 'update'])->name('peminjaman.update'); // Untuk update catatan operator


        Route::post('peminjaman/detail/{detailPeminjaman}/approve-item', [PeminjamanController::class, 'approveItem'])->name('peminjaman.item.approve');
        Route::post('peminjaman/detail/{detailPeminjaman}/reject-item', [PeminjamanController::class, 'rejectItem'])->name('peminjaman.item.reject');
        Route::post('peminjaman/{peminjaman}/finalize', [PeminjamanController::class, 'finalizeApproval'])->name('peminjaman.finalize');
        Route::post('peminjaman/detail/{detailPeminjaman}/handover', [PeminjamanController::class, 'processItemHandover'])->name('peminjaman.item.handover');
        Route::post('peminjaman/detail/{detailPeminjaman}/return', [PeminjamanController::class, 'processItemReturn'])->name('peminjaman.item.return');

        // Grup untuk Stok Opname
        Route::prefix('stok-opname')->name('stok-opname.')->group(function () {
            Route::get('/', [StokOpnameController::class, 'index'])->name('index');
            Route::get('/create', [StokOpnameController::class, 'create'])->name('create');

            // PINDAHKAN ROUTE SPESIFIK INI KE ATAS
            Route::get('/search-barang-qr', [StokOpnameController::class, 'searchBarangQr'])->name('search-barang-qr');
            Route::post('/add-barang-temuan', [StokOpnameController::class, 'addBarangTemuan'])->name('add-barang-temuan');

            // ROUTE UMUM/DINAMIS TETAP DI BAWAHNYA
            Route::post('/', [StokOpnameController::class, 'store'])->name('store');
            Route::get('/{stokOpname}', [StokOpnameController::class, 'show'])->name('show');
            Route::get('/{stokOpname}/edit', [StokOpnameController::class, 'edit'])->name('edit');
            Route::put('/{stokOpname}', [StokOpnameController::class, 'update'])->name('update');
            Route::delete('/{stokOpname}', [StokOpnameController::class, 'destroy'])->name('destroy');
            Route::post('/{stokOpname}/restore', [StokOpnameController::class, 'restore'])->name('restore'); // Diubah dari {id} ke {stokOpname} untuk konsistensi
            Route::post('/{stokOpname}/finalize', [StokOpnameController::class, 'finalize'])->name('finalize');
            Route::post('/{stokOpname}/cancel', [StokOpnameController::class, 'cancel'])->name('cancel');
            Route::put('/{stokOpname}/detail/{detail}', [StokOpnameController::class, 'updateDetail'])->name('updateDetail');
        });

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/inventaris', [App\Http\Controllers\LaporanController::class, 'inventaris'])->name('inventaris');
            Route::get('/inventaris/pdf', [App\Http\Controllers\LaporanController::class, 'exportInventarisPdf'])->name('inventaris.pdf');
            Route::get('/inventaris/excel', [App\Http\Controllers\LaporanController::class, 'exportInventarisExcel'])->name('inventaris.excel');

            Route::get('/peminjaman', [App\Http\Controllers\LaporanController::class, 'peminjaman'])->name('peminjaman');
            Route::get('/peminjaman/pdf', [App\Http\Controllers\LaporanController::class, 'exportPeminjamanPdf'])->name('peminjaman.pdf');
            Route::get('/peminjaman/excel', [App\Http\Controllers\LaporanController::class, 'exportPeminjamanExcel'])->name('peminjaman.excel');

            Route::get('/pemeliharaan', [App\Http\Controllers\LaporanController::class, 'pemeliharaan'])->name('pemeliharaan');
            Route::get('/pemeliharaan/pdf', [App\Http\Controllers\LaporanController::class, 'exportPemeliharaanPdf'])->name('pemeliharaan.pdf');
            Route::get('/pemeliharaan/excel', [App\Http\Controllers\LaporanController::class, 'exportPemeliharaanExcel'])->name('pemeliharaan.excel');
        });

        Route::resource('mutasi-barang', App\Http\Controllers\MutasiBarangController::class)->only(['index', 'show']);

        Route::get('/barang-qr-code/search-for-maintenance', [App\Http\Controllers\BarangQrCodeController::class, 'searchForMaintenance'])->name('barang-qr-code.search-for-maintenance');
    });



    // --- Rute Khusus Guru ---

    // di dalam file routes/web.php

    Route::middleware('isGuru')->prefix('guru')->name('guru.')->group(function () {

        Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'guru'])->name('dashboard');

        Route::get('katalog-barang', [\App\Http\Controllers\KatalogController::class, 'index'])->name('katalog.index');

        Route::get('peminjaman/search-items', [\App\Http\Controllers\PeminjamanController::class, 'searchAvailableItems'])->name('peminjaman.search-items');

        Route::prefix('keranjang-peminjaman')->name('keranjang.')->group(function () {
            Route::post('/tambah', [App\Http\Controllers\KeranjangPeminjamanController::class, 'tambahItem'])->name('tambah');
            Route::post('/hapus/{id_barang_qr_code}', [App\Http\Controllers\KeranjangPeminjamanController::class, 'hapusItem'])->name('hapus');
            Route::post('/reset', [App\Http\Controllers\KeranjangPeminjamanController::class, 'resetKeranjang'])->name('reset');
        });

        // --- PEMINJAMAN ---
        // Resource ini sudah mencakup: index, create, store, show, edit, update
        Route::resource('peminjaman', App\Http\Controllers\PeminjamanController::class)->only([
            'index',
            'create',
            'store',
            'show',
            'edit',
            'update'
        ]);

        // File: routes/web.php (di dalam grup 'isGuru')

        // Route custom untuk membatalkan peminjaman
        Route::post('peminjaman/{peminjaman}/cancel-by-user', [App\Http\Controllers\PeminjamanController::class, 'cancelByUser'])->name('peminjaman.cancelByUser');

        // --- PEMELIHARAAN (TAMBAHAN BARU) ---
        // Guru hanya bisa membuat laporan baru dan melihat/mengedit laporannya sendiri.
        Route::resource('pemeliharaan', App\Http\Controllers\PemeliharaanController::class)->only([
            'index',
            'create',
            'store',
            'show',
            'edit',
            'update'
        ]);

        Route::resource('barang-qr-code', BarangQrCodeController::class)
            ->only(['show']) // Guru hanya butuh melihat detail, tidak mengelola
            ->parameters(['barang-qr-code' => 'barangQrCode'])->withTrashed();;


        // Rute AJAX jika diperlukan di masa depan (tidak perlu diubah)
        Route::get('/get-units-by-ruangan/{ruangan}', [App\Http\Controllers\PeminjamanController::class, 'getAvailableUnitsByRuangan'])->name('peminjaman.getAvailableUnitsByRuangan');
        Route::get('/barang-qr-code/search-for-maintenance', [App\Http\Controllers\BarangQrCodeController::class, 'searchForMaintenance'])->name('barang-qr-code.search-for-maintenance');
    });



    // Dark Mode Toggle

    Route::post('/set-dark-mode', function (Request $request) {

        session(['darkMode' => $request->input('darkMode', 'light')]);

        return response()->json(['success' => true]);
    })->name('set-dark-mode');
});
