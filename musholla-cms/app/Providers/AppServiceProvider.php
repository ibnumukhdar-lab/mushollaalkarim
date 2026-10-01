<?php

namespace App\Providers;

use Filament\Tables\Table;
use App\Models\Infaq;
use App\Models\Santri;
use App\Observers\InfaqObserver;
use App\Observers\BeritaObserver;
use App\Observers\SantriObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Notifikasi WhatsApp otomatis dari kejadian aplikasi.
        Infaq::observe(InfaqObserver::class);
        Santri::observe(SantriObserver::class);

        // Tulisan baru terbit → notifikasi PWA ke pembaca.
        \App\Models\Berita::observe(BeritaObserver::class);

        // Tabel menumpuk di layar HP (pelajaran panel masfahri: kalau tidak, kolom
        // berjejer dan halaman jadi geser ke kanan).
        Table::configureUsing(fn (Table $table) => $table->stackedOnMobile());

        //
    }
}
