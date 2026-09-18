<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // penyaji berkas tanpa sesi/CSRF (cegah sesi baru dari permintaan gambar)
            require __DIR__.'/../routes/berkas.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman terkunci diarahkan ke pintu masuk aplikasi (/masuk),
        // bukan ke rute bawaan `login` yang tidak ada di aplikasi ini.
        $middleware->redirectGuestsTo(fn () => route('masuk'));
        $middleware->redirectUsersTo(fn () => route('anggota'));

        $middleware->alias([
            'panel.admin' => App\Http\Middleware\PanelAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
