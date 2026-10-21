<?php

use App\Http\Middleware\HeaderKeselamatan;
use App\Http\Middleware\PenggunaAktif;
use App\Http\Middleware\SemakPeranan;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'peranan' => SemakPeranan::class,
        ]);
        $middleware->web(append: [
            PenggunaAktif::class,
            HeaderKeselamatan::class,
        ]);
        $middleware->redirectGuestsTo('/log-masuk');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (DomainException $e, $request) {
            return back()->withInput()->with('ralat', $e->getMessage());
        });
    })->create();
