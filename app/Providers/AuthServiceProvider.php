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
use App\Policies\KategoriBarangPolicy;
use App\Policies\PeminjamanPolicy;
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
        User::class => UserPolicy::class,
        LogAktivitas::class => LogAktivitasPolicy::class,

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
    }
}
