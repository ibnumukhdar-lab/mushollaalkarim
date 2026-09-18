<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\Donatur;
use App\Models\Infaq;
use App\Models\Kas;
use App\Models\Page;
use App\Models\Program;
use App\Models\User;
use App\Support\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Dasbor panel pengelola: ringkasan angka penting + catatan terbaru.
 */
class DasborController extends Controller
{
    public function index(Request $request)
    {
        $awalBulan = Carbon::now()->startOfMonth();
        $kasBulan = Kas::query()
            ->whereBetween('tanggal', [$awalBulan->toDateString(), Carbon::now()->toDateString()])
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis='masuk' THEN jumlah ELSE 0 END),0) as masuk")
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END),0) as keluar")
            ->first();

        $saldo = Kas::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis='masuk' THEN jumlah ELSE -jumlah END),0) as saldo")
            ->value('saldo');

        $infaqMenunggu = Infaq::query()->where('status', 'menunggu')->count();
        $infaqBulan = (float) Infaq::query()
            ->where('status', 'terverifikasi')
            ->whereBetween('tanggal', [$awalBulan->toDateString(), Carbon::now()->toDateString()])
            ->sum('nominal');

        return view('panel.dasbor', [
            'stat' => [
                'berita' => Berita::query()->count(),
                'halaman' => Page::query()->count(),
                'program' => Program::query()->where('aktif', true)->count(),
                'donatur' => Donatur::query()->where('aktif', true)->count(),
                'subscriber' => User::query()->where('peran', 'subscriber')->count(),
                'admin' => User::query()->where('peran', 'admin')->count(),
                'kasMasuk' => (float) ($kasBulan->masuk ?? 0),
                'kasKeluar' => (float) ($kasBulan->keluar ?? 0),
                'saldo' => (float) $saldo,
                'infaqMenunggu' => $infaqMenunggu,
                'infaqBulan' => $infaqBulan,
            ],
            'kasTerbaru' => Kas::query()->orderByDesc('tanggal')->orderByDesc('id')->limit(6)->get(),
            'infaqTerbaru' => Infaq::query()->orderByDesc('tanggal')->orderByDesc('id')->limit(5)->get(),
            'programHariIni' => Program::query()->where('aktif', true)->orderBy('urutan')->limit(6)->get(),
            'grup' => Panel::menuSamping(),
        ]);
    }
}
