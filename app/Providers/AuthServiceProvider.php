<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider

{

    /**

     * Policy mappings for the application.

     *

     * Jika Anda menggunakan policy model-based, daftarkan di sini

     */

    protected $policies = [

        // Contoh:

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
