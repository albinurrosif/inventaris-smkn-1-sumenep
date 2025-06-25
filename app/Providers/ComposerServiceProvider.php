<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class ComposerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            // Pastikan user login sebelum mencoba mengambil notifikasi
            if (Auth::check()) {
                $user = Auth::user();
                // Gunakan property unreadNotifications (tanpa kurung)
                $unreadNotifications = $user->unreadNotifications;
                $view->with('unreadNotifications', $unreadNotifications);
            } else {
                // Jika user tidak login, kirim collection kosong agar tidak error
                $view->with('unreadNotifications', collect());
            }
        });
    }
}
