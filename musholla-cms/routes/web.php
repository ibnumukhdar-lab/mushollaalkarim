<?php

use App\Http\Controllers\DonasiController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\PublikController;
use Illuminate\Support\Facades\Route;

/*
| Halaman publik Musholla Al Karim.
| Semua data dilayani dari basis data aplikasi sendiri — tanpa integrasi luar.
| Penting: rute /{slug} harus paling bawah agar tidak menelan rute lain.
*/

Route::get('/', [PublikController::class, 'beranda'])->name('beranda');
Route::get('/berita', [PublikController::class, 'berita'])->name('berita');
Route::get('/berita/{slug}', [PublikController::class, 'beritaSatu'])->name('berita.satu');

Route::get('/laporan-kas', [KeuanganController::class, 'publik'])->name('laporan-kas');

Route::get('/mari-berinfaq', [DonasiController::class, 'tampilkan'])->name('berinfaq');
Route::post('/mari-berinfaq', [DonasiController::class, 'simpan'])->name('berinfaq.simpan');

Route::get('/pendaftaran-santri', [PendaftaranController::class, 'tampilkan'])->name('pendaftaran');
Route::post('/pendaftaran-santri', [PendaftaranController::class, 'simpan'])->name('pendaftaran.simpan');

Route::get('/{slug}', [PublikController::class, 'halaman'])->name('halaman');
