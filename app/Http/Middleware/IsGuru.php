<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsGuru
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === \App\Models\User::ROLE_GURU) {
            return $next($request);
        }

        abort(403, 'Akses khusus Guru.');
    }
}
