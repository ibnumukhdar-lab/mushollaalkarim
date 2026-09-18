<?php

namespace App\Providers;

use Filament\Tables\Table;
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
        // Tabel menumpuk di layar HP (pelajaran panel masfahri: kalau tidak, kolom
        // berjejer dan halaman jadi geser ke kanan).
        Table::configureUsing(fn (Table $table) => $table->stackedOnMobile());

        //
    }
}
