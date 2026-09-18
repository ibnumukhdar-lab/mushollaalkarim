<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman terkunci diarahkan ke pintu masuk aplikasi (/masuk),
        // bukan ke rute bawaan `login` yang tidak ada di aplikasi ini.
        $middleware->redirectGuestsTo(fn () => route('masuk'));
        $middleware->redirectUsersTo(fn () => route('anggota'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
