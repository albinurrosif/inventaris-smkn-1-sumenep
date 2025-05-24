<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Providers\RouteServiceProvider;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'isAdmin' => \App\Http\Middleware\IsAdmin::class,
            'isOperator' => \App\Http\Middleware\IsOperator::class,
            'isGuru' => \App\Http\Middleware\IsGuru::class,
            'canManageBarang' => \App\Http\Middleware\CanManageBarang::class,
            'checkIncompleteBarang' => \App\Http\Middleware\CheckIncompleteBarang::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureDarkModeIsSet::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        RouteServiceProvider::class, //

    ])
    ->create();
