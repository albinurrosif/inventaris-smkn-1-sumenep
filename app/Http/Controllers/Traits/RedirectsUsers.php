<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

trait RedirectsUsers
{
    /**
     * Mengembalikan URL absolut yang benar berdasarkan peran pengguna.
     * @param string $baseUri Bagian URI dari route (misal: 'peminjaman' atau 'peminjaman/1')
     * @return string URL tujuan yang lengkap.
     */
    private function getRedirectUrl(string $baseUri): string
    {
        $user = Auth::user();
        if (!$user) {
            return url('/login');
        }

        $rolePrefix = '';
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $rolePrefix = 'admin';
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            $rolePrefix = 'operator';
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            $rolePrefix = 'guru';
        }

        $fullUri = $rolePrefix ? "{$rolePrefix}/{$baseUri}" : $baseUri;
        return url($fullUri);
    }

    /**
     * Mengembalikan prefix nama rute berdasarkan peran pengguna.
     * @return string Contoh: 'admin.', 'operator.', 'guru.'
     */
    private function getRolePrefix(): string
    {
        $user = Auth::user();
        if (!$user) return '';

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return 'admin.';
        } elseif ($user->hasRole(User::ROLE_OPERATOR)) {
            return 'operator.';
        } elseif ($user->hasRole(User::ROLE_GURU)) {
            return 'guru.';
        }
        return '';
    }
}
