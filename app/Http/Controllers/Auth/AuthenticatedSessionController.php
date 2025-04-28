<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('redirect-dashboard'));
    }


    /**
     * Show the logout confirmation view.
     */
    /**
 * Show the logout confirmation page and perform logout.
 */
public function showLogout(Request $request): View
{
    $sessionId = $request->session()->getId();

    Log::info('Logout triggered from showLogout', [
        'session_id' => $sessionId,
        'user_id' => Auth::id()
    ]);

    Auth::guard('web')->logout();

    // Invalidate and regenerate token
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // If using database session driver, delete this session from the DB
    // if (config('session.driver') === 'database') {
    //     DB::table('sessions')->where('id', $sessionId)->delete();
    //     Log::info('Session deleted from DB', ['id' => $sessionId]);
    // }

    // Remove remember_me cookie
    Cookie::queue(Cookie::forget(Auth::getRecallerName()));

    Log::info('After logout', [
        'auth_check' => Auth::check(),
        'session_id' => Session::getId(),
        'user_still_logged_in' => Auth::user()
    ]);

    return view('auth.logout');
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $sessionId = $request->session()->getId(); // Get ID before invalidation

        Log::info('Logout triggered', [
            'session_id' => $sessionId,
            'user_id' => Auth::id()
        ]);

        Auth::guard('web')->logout();

        // Invalidate and regenerate token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // If using database session driver, delete this session from the DB
        // if (config('session.driver') === 'database') {
        //     DB::table('sessions')->where('id', $sessionId)->delete();
        //     Log::info('Session deleted from DB', ['id' => $sessionId]);
        // }

        // Remove remember_me cookie
        Cookie::queue(Cookie::forget(Auth::getRecallerName()));

        Log::info('After logout', [
            'auth_check' => Auth::check(),
            'session_id' => Session::getId(),
            'user_still_logged_in' => Auth::user()
        ]);

        return redirect('/');
    }
}