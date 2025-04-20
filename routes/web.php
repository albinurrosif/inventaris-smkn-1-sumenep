<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini adalah rute utama aplikasi. Termasuk autentikasi, dashboard, dan
| fitur-fitur lainnya yang memerlukan login.
|
*/

// Halaman utama: Login
Route::get('/', function () {
    return view('auth.login');
})->middleware('guest'); // Hanya bisa diakses oleh yang belum login

// Show logout page (GET request)
Route::middleware('auth')->get('/logout', [AuthenticatedSessionController::class, 'showLogout'])->name('logout.show');

// Process actual logout (POST request)
Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Debugging: Cek status login
Route::get('/debug-auth', function () {
    Log::info('Auth check', ['user' => Auth::user()]);
    return Auth::check() ? '✅ Logged in as ' . Auth::user()->email : '❌ Not logged in';
});

// Dashboard utama setelah login
Route::get('/dashboard', function () {
    logger('Test logging on dashboard');
    $jumlahBarang = \App\Models\Barang::count(); // Menghitung total jumlah barang
    return view('dashboard', compact('jumlahBarang'));
})->middleware(['auth', 'verified'])->name('dashboard'); // Hanya untuk user yang sudah login & terverifikasi

// Export dan import barang dari excel
Route::get('/barang/export', [BarangController::class, 'export'])->name('barang.export');
Route::post('/barang/import', [BarangController::class, 'import'])->name('barang.import');

// Grup rute yang hanya bisa diakses jika sudah login
Route::middleware('auth')->group(function () {
    // Profil pengguna
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Barang (CRUD)
    Route::resource('barang', BarangController::class);
});

// Rute otentikasi (login, register, lupa password, dll) hanya untuk tamu
Route::middleware('guest')->group(function () {
    require __DIR__.'/auth.php';
});
