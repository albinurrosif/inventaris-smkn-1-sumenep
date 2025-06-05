<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Pastikan User model dan konstanta role sudah benar
        // Menggunakan konstanta dari model User lebih aman
        if (Auth::check() && Auth::user()->role === \App\Models\User::ROLE_ADMIN) { // Perubahan di sini
            return $next($request);
        }

        abort(403, 'Akses khusus Admin.');
    }
}
