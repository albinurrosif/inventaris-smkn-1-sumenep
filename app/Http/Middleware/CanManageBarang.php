<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanManageBarang
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $routeName = $request->route()?->getName(); // safe call in case it's null

        if ($user->role === 'Admin') {
            // Admin punya akses penuh
            return $next($request);
        }

        if ($user->role === 'Operator') {
            // Operator hanya bisa kelola barang & ruangan yang sesuai dengan ruangannya
            // (Asumsinya: ada kolom ruangan_id atau relasi di model Barang & Ruangan)

            $routeName = $request->route()->getName();

            if (str_starts_with($routeName, 'barang.') || str_starts_with($routeName, 'ruangan.')) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak.');
    }
}
