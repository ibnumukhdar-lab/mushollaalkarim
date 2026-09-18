<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Kajian;
use App\Models\Page;
use App\Models\Pengaturan;
use App\Models\WakafProgram;

class PublikController extends Controller
{
    /** Singgahan nama pengaturan agar tidak berulang kali dibaca. */
    private function pengaturan(): array
    {
        return Pengaturan::query()->pluck('nilai', 'kunci')->all();
    }

    /** Halaman yang tidak ditampilkan di menu publik (bawaan plugin WordPress). */
    private const BUKAN_MENU = [
        'home', 'user', 'login', 'register', 'members', 'logout', 'account',
        'password-reset', 'sukses-daftar', 'privacy-policy',
    ];

    private function menu(): array
    {
        return Page::query()
            ->whereNotNull('terbit_at')
            ->whereNotIn('slug', self::BUKAN_MENU)
            ->orderBy('urutan_menu')
            ->orderBy('id')
            ->get(['judul', 'slug'])
            ->map(fn ($p) => ['judul' => $p->judul, 'slug' => $p->slug])
            ->all();
    }

    public function beranda()
    {
        $hal = Page::query()->whereIn('slug', ['home-page', 'home'])->first();

        return view('publik.beranda', [
            'hal' => $hal,
            'menu' => $this->menu(),
            'berita' => Berita::query()->orderByDesc('terbit_at')->limit(3)->get(),
            'wakaf' => WakafProgram::query()->where('aktif', true)->orderBy('urutan')->get(),
            'kajian' => Kajian::query()->where('aktif', true)->orderByDesc('tanggal')->limit(4)->get(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }

    public function halaman(string $slug)
    {
        $hal = Page::query()->where('slug', $slug)->whereNotNull('terbit_at')->firstOrFail();

        return view('publik.halaman', [
            'hal' => $hal,
            'menu' => $this->menu(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }

    public function berita()
    {
        return view('publik.berita-index', [
            'menu' => $this->menu(),
            'daftar' => Berita::query()->orderByDesc('terbit_at')->paginate(9),
            'pengaturan' => $this->pengaturan(),
        ]);
    }

    public function beritaSatu(string $slug)
    {
        $tulisan = Berita::query()->where('slug', $slug)->firstOrFail();

        return view('publik.berita', [
            'tulisan' => $tulisan,
            'menu' => $this->menu(),
            'lain' => Berita::query()->where('id', '!=', $tulisan->id)->orderByDesc('terbit_at')->limit(3)->get(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }
}
