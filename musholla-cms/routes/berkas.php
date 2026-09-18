<?php

use Illuminate\Support\Facades\Route;

/*
| Penyaji berkas unggahan (/berkas/{path}).
|
| SENGAJA dipanggil di luar grup middleware `web` (lihat bootstrap/app.php):
| permintaan gambar tanpa cookie tidak boleh membuat sesi baru — itu penyebab
| keluhan "419 Page Expired" pada aplikasi ini.
*/
Route::get('/berkas/{path}', function (string $path) {
    $path = str_replace(["\0", '..'], '', $path);
    $akar = realpath(storage_path('app/public'));
    $berkas = realpath(storage_path('app/public/' . ltrim($path, '/')));

    abort_if(! $akar || ! $berkas || ! str_starts_with($berkas, $akar) || ! is_file($berkas), 404);

    return response()->file($berkas, ['Cache-Control' => 'public, max-age=604800']);
})->where('path', '.*')->name('berkas');
