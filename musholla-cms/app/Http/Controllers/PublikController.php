<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Kajian;
use App\Models\Kategori;
use App\Models\Page;
use App\Models\Pengaturan;
use App\Models\Program;
use App\Models\WakafProgram;
use Illuminate\Http\Request;

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
            'berita' => Berita::query()->terbit()->with('kategoriBanyak')->orderByDesc('terbit_at')->limit(3)->get(),
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

    public function berita(Request $request)
    {
        $cari = trim((string) $request->input('q', ''));
        $slugKategori = (string) $request->input('kategori', '');

        $daftar = Berita::query()
            ->terbit()
            ->with('kategoriBanyak')
            ->when($cari !== '', function ($w) use ($cari) {
                $w->where(function ($x) use ($cari) {
                    $x->where('judul', 'like', '%' . $cari . '%')
                        ->orWhere('ringkasan', 'like', '%' . $cari . '%')
                        ->orWhere('isi', 'like', '%' . $cari . '%');
                });
            })
            ->when($slugKategori !== '', fn ($w) => $w->whereHas('kategoriBanyak', fn ($x) => $x->where('slug', $slugKategori)))
            ->orderByDesc('terbit_at')
            ->paginate(9)
            ->withQueryString();

        return view('publik.berita-index', [
            'menu' => $this->menu(),
            'daftar' => $daftar,
            'cari' => $cari,
            'kategoriAktif' => $slugKategori,
            'kategori' => Kategori::query()->orderBy('urut')->orderBy('nama')->get(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }

    public function beritaSatu(string $slug)
    {
        $tulisan = Berita::query()->where('slug', $slug)->with(['kategoriBanyak', 'penulis'])->firstOrFail();

        return view('publik.berita', [
            'tulisan' => $tulisan,
            'menu' => $this->menu(),
            'lain' => Berita::query()->terbit()->with('kategoriBanyak')
                ->where('id', '!=', $tulisan->id)->orderByDesc('terbit_at')->limit(3)->get(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }
}
