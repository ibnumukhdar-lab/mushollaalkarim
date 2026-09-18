<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\DonasiController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\PublikController;
use Illuminate\Support\Facades\Route;

/*
| Halaman publik Musholla Al Karim.
| Semua data dilayani dari basis data aplikasi sendiri — tanpa integrasi luar.
| Keanggotaan memakai SATU pintu: /daftar (subscriber) dan /masuk.
| Penting: rute /{slug} harus paling bawah agar tidak menelan rute lain.
*/

Route::get('/', [PublikController::class, 'beranda'])->name('beranda');
Route::get('/berita', [PublikController::class, 'berita'])->name('berita');
Route::get('/berita/{slug}', [PublikController::class, 'beritaSatu'])->name('berita.satu');

Route::get('/laporan-kas', [KeuanganController::class, 'publik'])->name('laporan-kas');

Route::get('/mari-berinfaq', [DonasiController::class, 'tampilkan'])->name('berinfaq');
Route::post('/mari-berinfaq', [DonasiController::class, 'simpan'])->name('berinfaq.simpan');

/* Keanggotaan — satu pintu registrasi sebagai subscriber */
Route::middleware('guest')->group(function () {
    Route::get('/daftar', [AnggotaController::class, 'formDaftar'])->name('daftar');
    Route::post('/daftar', [AnggotaController::class, 'daftar'])->middleware('throttle:10,1');
    Route::get('/masuk', [AnggotaController::class, 'formMasuk'])->name('masuk');
    Route::post('/masuk', [AnggotaController::class, 'masuk'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/anggota', [AnggotaController::class, 'beranda'])->name('anggota');
    Route::post('/keluar', [AnggotaController::class, 'keluar'])->name('keluar');
});

Route::get('/{slug}', [PublikController::class, 'halaman'])->name('halaman');
