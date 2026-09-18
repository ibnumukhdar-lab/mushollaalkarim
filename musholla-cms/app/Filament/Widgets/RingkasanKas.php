<?php

namespace App\Filament\Widgets;

use App\Models\Kas;
use Filament\Widgets\Widget;

/**
 * Ringkasan kas di atas daftar transaksi: pemasukan, pengeluaran, saldo.
 * Semua dihitung dari tabel `kas` aplikasi ini.
 */
class RingkasanKas extends Widget
{
    protected string $view = 'filament.ringkasan-kas';

    /** Tampil langsung bersama halaman (tidak dimuat malas). */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $masuk = (float) Kas::query()->where('jenis', 'masuk')->sum('jumlah');
        $keluar = (float) Kas::query()->where('jenis', 'keluar')->sum('jumlah');
        $bulanIni = Kas::query()
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month);

        return [
            'masuk' => $masuk,
            'keluar' => $keluar,
            'saldo' => $masuk - $keluar,
            'masukBulanIni' => (float) (clone $bulanIni)->where('jenis', 'masuk')->sum('jumlah'),
            'keluarBulanIni' => (float) (clone $bulanIni)->where('jenis', 'keluar')->sum('jumlah'),
            'jumlahCatatan' => Kas::query()->count(),
        ];
    }
}
