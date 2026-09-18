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
    Route::get('/anggota/profil', [AnggotaController::class, 'formProfil'])->name('profil');
    Route::post('/anggota/profil', [AnggotaController::class, 'simpanProfil'])->name('profil.simpan');
    Route::post('/keluar', [AnggotaController::class, 'keluar'])->name('keluar');
});

/* Panel pengelola — rute buatan sendiri (bukan bawaan Filament), hanya peran admin */
Route::middleware(['auth', 'panel.admin'])->prefix('kelola')->name('panel.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Panel\DasborController::class, 'index'])->name('dasbor');

    Route::get('/pengaturan-situs', [\App\Http\Controllers\Panel\PengaturanController::class, 'index'])->name('pengaturan');
    Route::post('/pengaturan-situs', [\App\Http\Controllers\Panel\PengaturanController::class, 'simpan'])->name('pengaturan.simpan');

    Route::get('/{modul}', [\App\Http\Controllers\Panel\PanelController::class, 'daftar'])->name('daftar');
    Route::get('/{modul}/tambah', [\App\Http\Controllers\Panel\PanelController::class, 'tambah'])->name('tambah');
    Route::post('/{modul}', [\App\Http\Controllers\Panel\PanelController::class, 'simpan'])->name('simpan');
    Route::get('/{modul}/{id}/ubah', [\App\Http\Controllers\Panel\PanelController::class, 'ubah'])->name('ubah');
    Route::put('/{modul}/{id}', [\App\Http\Controllers\Panel\PanelController::class, 'perbarui'])->name('perbarui');
    Route::delete('/{modul}/{id}', [\App\Http\Controllers\Panel\PanelController::class, 'hapus'])->name('hapus');
});

Route::get('/{slug}', [PublikController::class, 'halaman'])->name('halaman');
