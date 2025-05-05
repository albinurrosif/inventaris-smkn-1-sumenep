<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Redirect ke dashboard sesuai role
                $role = Auth::user()->role;

                return match ($role) {
                    'Admin' => redirect()->route('admin.dashboard'),
                    'Operator' => redirect()->route('operator.dashboard'),
                    'Guru' => redirect()->route('guru.dashboard'),
                    default => redirect('/'),
                };
            }
        }

        return $next($request);
    }
}
