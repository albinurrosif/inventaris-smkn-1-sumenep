<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EnsureDarkModeIsSet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user has cookie for dark mode
        if ($request->cookie('darkMode') && !Session::has('darkMode')) {
            Session::put('darkMode', $request->cookie('darkMode'));
        }

        // If no session or cookie, set default
        if (!Session::has('darkMode')) {
            Session::put('darkMode', 'light');
        }

        return $next($request);
    }
}
