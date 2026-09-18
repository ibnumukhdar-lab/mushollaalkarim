<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Pengaturan;
use App\Models\Santri;
use Illuminate\Http\Request;

/**
 * Pendaftaran Santri — form dalam aplikasi (dulu memakai form plugin WordPress).
 * Kiriman masuk ke tabel `santri` dengan status belum aktif, menunggu pemeriksaan pengurus.
 */
class PendaftaranController extends Controller
{
    private const BUKAN_MENU = [
        'home-page', 'home', 'laporan-kas', 'laporan-keuangan', 'user', 'login', 'register',
        'members', 'logout', 'account', 'password-reset', 'privacy-policy',
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

    private function pengaturan(): array
    {
        return Pengaturan::query()->pluck('nilai', 'kunci')->all();
    }

    public function tampilkan()
    {
        $hal = Page::query()->where('slug', 'pendaftaran-santri')->first();

        return view('publik.pendaftaran-santri', [
            'hal' => $hal,
            'menu' => $this->menu(),
            'pengaturan' => $this->pengaturan(),
            'terkirim' => session('santri_terkirim'),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:80'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'kelas_sekolah' => ['nullable', 'string', 'max:60'],
            'nama_ortu' => ['required', 'string', 'max:120'],
            'no_wa' => ['required', 'string', 'max:25'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'nama' => 'nama ananda',
            'jenis_kelamin' => 'jenis kelamin',
            'nama_ortu' => 'nama orang tua/wali',
            'no_wa' => 'nomor WhatsApp',
        ]);

        Santri::query()->create([
            'nama' => $data['nama'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'tempat_lahir' => $data['tempat_lahir'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'kelas_sekolah' => $data['kelas_sekolah'] ?? null,
            'aktif' => false,
            'catatan' => trim(implode(' · ', array_filter([
                'Orang tua/wali: '.$data['nama_ortu'],
                'WA: '.$data['no_wa'],
                'Menunggu pemeriksaan pengurus',
                $data['catatan'] ?? null,
            ]))),
        ]);

        return redirect('/pendaftaran-santri')->with('santri_terkirim', true);
    }
}
