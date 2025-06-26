<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Barang;
use App\Policies\BarangPolicy;
use App\Models\Ruangan;
use App\Policies\RuanganPolicy;
use App\Models\BarangQrCode;
use App\Policies\BarangQrCodePolicy;
use App\Models\KategoriBarang;
use App\Models\Peminjaman;
use App\Policies\PeminjamanPolicy;
use App\Policies\KategoriBarangPolicy;
use App\Policies\LaporanPolicy;

use App\Policies\UserPolicy;
use App\Models\ArsipBarang;
use App\Policies\ArsipBarangPolicy;
use App\Models\BarangStatus;
use App\Policies\BarangStatusPolicy;
use App\Models\Pemeliharaan;
use App\Policies\PemeliharaanPolicy;
use App\Models\RekapStok;
use App\Policies\RekapStokPolicy;
use App\Models\LogAktivitas;
use App\Policies\LogAktivitasPolicy;
use App\Models\DetailPeminjaman;
use App\Models\MutasiBarang;
use App\Policies\DetailPeminjamanPolicy;
use App\Policies\MutasiBarangPolicy;
use App\Policies\PengaturanPolicy;




class AuthServiceProvider extends ServiceProvider

{

    /**

     * Policy mappings for the application.

     *

     * Jika Anda menggunakan policy model-based, daftarkan di sini

     */

    protected $policies = [

        // Contoh:
        Barang::class => BarangPolicy::class,
        Ruangan::class => RuanganPolicy::class,
        BarangQrCode::class => BarangQrCodePolicy::class,
        ArsipBarang::class => ArsipBarangPolicy::class,
        BarangStatus::class => BarangStatusPolicy::class,
        Pemeliharaan::class => PemeliharaanPolicy::class,
        RekapStok::class => RekapStokPolicy::class,
        KategoriBarang::class => KategoriBarangPolicy::class,
        Peminjaman::class => PeminjamanPolicy::class,
        DetailPeminjaman::class => DetailPeminjamanPolicy::class,
        User::class => UserPolicy::class,
        LogAktivitas::class => LogAktivitasPolicy::class,
        MutasiBarang::class => MutasiBarangPolicy::class


        // \App\Models\Post::class => \App\Policies\PostPolicy::class,

    ];



    /**

     * Register any authentication / authorization services.

     */

    public function boot(): void

    {

        $this->registerPolicies(); // Untuk mendukung policies jika Anda menggunakannya



        // Definisikan Gate untuk role

        Gate::define('isAdmin', fn(User $user) => $user->role === User::ROLE_ADMIN);

        Gate::define('isOperator', fn(User $user) => $user->role === User::ROLE_OPERATOR);

        Gate::define('isGuru', fn(User $user) => $user->role === User::ROLE_GURU);


        // Gate ini akan memanggil method 'viewInventaris' di dalam LaporanPolicy
        Gate::define('view-laporan-inventaris', [LaporanPolicy::class, 'viewInventaris']);

        // Gate ini akan memanggil method 'viewPeminjaman' di dalam LaporanPolicy
        Gate::define('view-laporan-peminjaman', [LaporanPolicy::class, 'viewPeminjaman']);

        // Gate ini akan memanggil method 'viewPemeliharaan' di dalam LaporanPolicy
        Gate::define('view-laporan-pemeliharaan', [LaporanPolicy::class, 'viewPemeliharaan']);

        Gate::define('view-laporan-mutasi', function (User $user) {
            // Hanya Admin dan Operator yang bisa melihat laporan ini
            return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
        });

        /**
         * Mendefinisikan hak akses untuk melihat halaman "Aktivitas Saya".
         * Hanya relevan untuk Operator dan Guru.
         */
        Gate::define('view-my-activity', function (User $user) {
            return $user->hasAnyRole([User::ROLE_OPERATOR, User::ROLE_GURU]);
        });

        // Daftarkan Gate untuk Pengaturan
        Gate::define('view-pengaturan', [PengaturanPolicy::class, 'viewAny']);
        Gate::define('update-pengaturan', [PengaturanPolicy::class, 'update']);
    }
}
