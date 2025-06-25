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
use Illuminate\Auth\Access\AuthorizationException; // Pastikan ini di-import
use Illuminate\Support\Facades\Auth; // Pastikan ini di-import
use App\Models\User; // Pastikan ini di-import untuk cek peran
use App\Console\Commands\CheckOverduePeminjaman;
use App\Console\Commands\CheckPeminjamanStatus;


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
            //'pages.peminjaman.edit'     // Halaman Edit Pengajuan
        ], function ($view) {
            // Cek Auth untuk keamanan, meskipun rute sudah dilindungi middleware
            if (Auth::check()) {
                $keranjangIds = session()->get('keranjang_peminjaman', []);
                $view->with('jumlahDiKeranjang', count($keranjangIds));
            }
        });
        Carbon::setLocale('id');
        Paginator::useBootstrapFive();


        $this->commands([
            CheckOverduePeminjaman::class,
            CheckPeminjamanStatus::class
        ]);





        // Daftarkan callback untuk merender exception.
        $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->renderable(function (AuthorizationException $e, $request) {
                //
                // Cek jika pengguna terautentikasi dan memiliki peran Operator.
                //
                if (Auth::check() && Auth::user()->hasRole(User::ROLE_OPERATOR)) {
                    // Cek jika rute yang diakses adalah halaman detail unit barang.
                    // Pola '*' menangkap prefix rute 'admin.' atau 'operator.'.
                    //
                    if ($request->routeIs('*.barang-qr-code.show')) {
                        // Log peristiwa penolakan akses
                        Log::warning('Operator attempt to access unauthorized unit detail page.', [
                            'user_id' => Auth::id(),
                            'route_name' => $request->route()->getName(),
                            'url' => $request->fullUrl(),
                            'exception_message' => $e->getMessage()
                        ]);

                        // Redirect pengguna kembali ke dashboard operator dengan pesan error yang jelas.
                        //
                        return redirect()->route('operator.dashboard')
                            ->with('error', 'Anda tidak memiliki izin untuk melihat detail unit barang ini karena tidak berada di ruangan yang Anda kelola.');
                    }
                }

                // Fallback ke rendering exception default Laravel jika kondisi di atas tidak terpenuhi.
                // return parent::render($request, $e); // Ini tidak diperlukan di sini karena callback renderable tidak menimpa render()
            });
    }
}
