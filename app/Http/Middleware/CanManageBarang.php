<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route; // Import the Route facade

class CanManageBarang
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $routeName = Route::currentRouteName(); // Use Route::currentRouteName()

        if ($user->role === 'Admin') {
            // Admin memiliki akses penuh
            return $next($request);
        }

        if ($user->role === 'Operator') {
            // Operator hanya bisa kelola barang & ruangan.
            if (str_starts_with($routeName, 'barang.') || str_starts_with($routeName, 'ruangan.')) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak.');
    }
}
