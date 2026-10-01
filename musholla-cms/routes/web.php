<?php


use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\DonasiController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\PublikController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiTulisController;

/*
| Halaman publik Musholla Al Karim.
| Semua data dilayani dari basis data aplikasi sendiri — tanpa integrasi luar.
| Keanggotaan memakai SATU pintu: /daftar (subscriber) dan /masuk.
| Penting: rute /{slug} harus paling bawah agar tidak menelan rute lain.
*/

Route::get('/', [PublikController::class, 'beranda'])->name('beranda');
Route::get('/berita', [PublikController::class, 'berita'])->name('berita');
Route::get('/berita/{slug}', [PublikController::class, 'beritaSatu'])->name('berita.satu');

// Halaman sendiri untuk tiap program wakaf (/wakaf/<slug>) — bisa dibagikan
// dan menampilkan gambar unggulan. WAJIB didaftarkan SEBELUM rute
// tangkap-semua /{slug} di bawah, kalau tidak akan tertelan.
Route::get('/wakaf/{slug}', [PublikController::class, 'wakaf'])->name('publik.wakaf');

// Pintu penerbitan untuk bot asisten penulis (jangan dihapus).
Route::post('/api/tulis', [ApiTulisController::class, 'simpan'])->middleware('throttle:20,1');

Route::get('/laporan-kas', [KeuanganController::class, 'publik'])->name('laporan-kas');

Route::get('/mari-berinfaq', [DonasiController::class, 'tampilkan'])->name('berinfaq');
Route::post('/mari-berinfaq', [DonasiController::class, 'simpan'])->name('berinfaq.simpan');

// Notifikasi PWA — langganan per perangkat.
Route::get('/api/push/vapid', [\App\Http\Controllers\PushController::class, 'vapid']);
Route::post('/api/push/simpan', [\App\Http\Controllers\PushController::class, 'simpan'])->middleware('throttle:30,1');
Route::post('/api/push/hapus', [\App\Http\Controllers\PushController::class, 'hapus'])->middleware('throttle:30,1');

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

/*
| Pengalihan URL salah ketik: /kelola/beritas (jamak) dipakai bot penulis di
| tautan panel (TerbitBerita::hasil()). Rute yang benar adalah daftar modul
| custom /kelola/berita dan penyunting Filament /panel-filament/beritas/{id}/edit.
| Diletakkan SEBELUM grup kelola/{modul} di atas? Tidak — grup di atas memakai
| prefix 'kelola' dan pattern /{modul} akan menangkap 'beritas', jadi pengalihan
| ini HARUS didaftarkan lebih dulu. Karena ditulis sebelum grup, Laravel
| mendaftarkannya lebih awal dan cocok sebelum /{modul}.
*/
Route::get('/kelola/beritas', function () {
    return redirect()->to('/kelola/berita', 301);
})->name('panel.beritas-salah-ketik');

Route::get('/kelola/beritas/{id}/edit', function ($id) {
    return redirect()->to('/panel-filament/beritas/'.((int) $id).'/edit', 301);
})->whereNumber('id')->name('panel.beritas-edit-salah-ketik');

Route::middleware(['auth', 'panel.admin'])->prefix('kelola')->name('panel.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Panel\DasborController::class, 'index'])->name('dasbor');

    Route::get('/pengaturan-situs', [\App\Http\Controllers\Panel\PengaturanController::class, 'index'])->name('pengaturan');
    Route::post('/pengaturan-situs', [\App\Http\Controllers\Panel\PengaturanController::class, 'simpan'])->name('pengaturan.simpan');
    Route::post('/push/uji', [\App\Http\Controllers\PushController::class, 'uji'])->name('push.uji');

    Route::get('/infaq-qris', [\App\Http\Controllers\Panel\InfaqQrisController::class, 'index'])->name('qris');
    Route::post('/infaq-qris', [\App\Http\Controllers\Panel\InfaqQrisController::class, 'simpan'])->name('qris.simpan');
    Route::delete('/infaq-qris', [\App\Http\Controllers\Panel\InfaqQrisController::class, 'hapusQris'])->name('qris.hapus');
    Route::post('/infaq/{id}/verifikasi', [\App\Http\Controllers\Panel\InfaqController::class, 'verifikasi'])->name('infaq.verifikasi');
    Route::post('/infaq/{id}/tolak', [\App\Http\Controllers\Panel\InfaqController::class, 'tolak'])->name('infaq.tolak');

    Route::post('/kas/tutup-bulan', [\App\Http\Controllers\Panel\KasBulanController::class, 'tutup'])->name('kas.tutup');
    Route::post('/kas/hitung-ulang', [\App\Http\Controllers\Panel\KasBulanController::class, 'hitungUlang'])->name('kas.hitungUlang');
    Route::post('/kas/buka-bulan', [\App\Http\Controllers\Panel\KasBulanController::class, 'buka'])->name('kas.buka');

    // Pusat WhatsApp (didahulukan sebelum modul generik /{modul})
    Route::get('/wa', [\App\Http\Controllers\Panel\WaController::class, 'pusat'])->name('wa.pusat');
    Route::post('/wa/kirim', [\App\Http\Controllers\Panel\WaController::class, 'kirim'])->name('wa.kirim');
    Route::get('/wa/antrean', [\App\Http\Controllers\Panel\WaController::class, 'antrean'])->name('wa.antrean');
    Route::post('/wa/pesan/{pesan}/tandai', [\App\Http\Controllers\Panel\WaController::class, 'tandai'])->name('wa.tandai');
    Route::post('/wa/pesan/{pesan}/lewati', [\App\Http\Controllers\Panel\WaController::class, 'lewati'])->name('wa.lewati');
    Route::post('/wa/kampanye/{kampanye}/proses', [\App\Http\Controllers\Panel\WaController::class, 'proses'])->name('wa.proses');
    Route::get('/wa/pengaturan', [\App\Http\Controllers\Panel\WaController::class, 'pengaturan'])->name('wa.pengaturan');
    Route::post('/wa/pengaturan', [\App\Http\Controllers\Panel\WaController::class, 'simpanPengaturan'])->name('wa.pengaturan.simpan');
    Route::post('/wa/uji-kirim', [\App\Http\Controllers\Panel\WaController::class, 'ujiKirim'])->name('wa.uji');
    Route::get('/wa/aturan', [\App\Http\Controllers\Panel\WaController::class, 'aturan'])->name('wa.aturan');
    Route::post('/wa/aturan', [\App\Http\Controllers\Panel\WaController::class, 'simpanAturan'])->name('wa.aturan.simpan');
    Route::get('/wa/pratinjau', [\App\Http\Controllers\Panel\WaController::class, 'pratinjau'])->name('wa.pratinjau');

    Route::get('/{modul}', [\App\Http\Controllers\Panel\PanelController::class, 'daftar'])->name('daftar');
    Route::get('/{modul}/tambah', [\App\Http\Controllers\Panel\PanelController::class, 'tambah'])->name('tambah');
    Route::post('/{modul}', [\App\Http\Controllers\Panel\PanelController::class, 'simpan'])->name('simpan');
    Route::get('/{modul}/{id}/ubah', [\App\Http\Controllers\Panel\PanelController::class, 'ubah'])->name('ubah');
    Route::put('/{modul}/{id}', [\App\Http\Controllers\Panel\PanelController::class, 'perbarui'])->name('perbarui');
    Route::delete('/{modul}/{id}', [\App\Http\Controllers\Panel\PanelController::class, 'hapus'])->name('hapus');
});

// Alias bahasa Indonesia untuk halaman kebijakan privasi.
Route::redirect('/kebijakan-privasi', '/privacy-policy');

Route::get('/{slug}', [PublikController::class, 'halaman'])->name('halaman');
