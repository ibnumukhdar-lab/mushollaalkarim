<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Kajian;
use App\Models\Page;
use App\Models\Pengaturan;
use App\Models\Program;
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
        'home', 'user', 'login', 'masuk', 'register', 'daftar', 'anggota', 'members', 'logout', 'keluar', 'account',
        'password-reset', 'sukses-daftar', 'privacy-policy',
    ];

    private function menu(): array
    {
        return \App\Support\Menu::utama();
    }

    public function beranda()
    {
        // Program sepekan, disusun mulai dari hari ini
        $urutanHari = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariIni = $urutanHari[now()->dayOfWeek] ?? 'Ahad';
        $mulai = array_search($hariIni, $urutanHari, true);
        $mulai = $mulai === false ? 0 : $mulai;
        $urutTampil = array_merge(array_slice($urutanHari, $mulai), array_slice($urutanHari, 0, $mulai));

        $hal = Page::query()->whereIn('slug', ['home-page', 'home'])->first();

        return view('publik.beranda', [
            'hal' => $hal,
            'menu' => $this->menu(),
            'berita' => Berita::query()->orderByDesc('terbit_at')->limit(3)->get(),
            'wakaf' => WakafProgram::query()->where('aktif', true)->orderBy('urutan')->get(),
            'kajian' => Kajian::query()->where('aktif', true)->orderByDesc('tanggal')->limit(4)->get(),
            'programHari' => [
                'hariIni' => $hariIni,
                'urut' => $urutTampil,
                'data' => Program::query()->where('aktif', true)->orderBy('urutan')->orderBy('id')->get()->groupBy('hari'),
            ],
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
