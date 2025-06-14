<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use App\Models\BarangQrCode;
use Illuminate\Support\Facades\Auth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https') || app()->environment('production')) {
            URL::forceScheme('https');
        }
        //
        if (config('app.debug')) {
            DB::listen(function ($query) {
                Log::info(
                    $query->sql,
                    $query->bindings,
                    $query->time
                );
            });
        }

        View::composer([
            'guru.dashboard',           // Halaman Dashboard Guru
            'pages.katalog.index',      // Halaman Katalog Barang
            'pages.peminjaman.index',   // Halaman Daftar Peminjaman
            // Halaman Form Pengajuan
            'pages.peminjaman.edit'     // Halaman Edit Pengajuan
        ], function ($view) {
            // Cek Auth untuk keamanan, meskipun rute sudah dilindungi middleware
            if (Auth::check()) {
                $keranjangIds = session()->get('keranjang_peminjaman', []);
                $view->with('jumlahDiKeranjang', count($keranjangIds));
            }
        });
        Carbon::setLocale('id');
        Paginator::useBootstrapFive();
    }
}
