<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Impor isi WordPress (tabel berawalan wpvr_) ke tabel Laravel baru.
 * Idempoten: dijalankan ulang akan memperbarui, bukan menggandakan.
 *
 *   php artisan impor:wordpress --kering     (uji, tidak menulis)
 *   php artisan impor:wordpress              (menulis sungguhan)
 */
class ImporWordPress extends Command
{
    protected $signature = 'impor:wordpress {--kering : uji tanpa menulis} {--prefix=wpvr_}';

    protected $description = 'Memindahkan isi WordPress musholla Al Karim ke tabel aplikasi Laravel';

    private string $p;

    private bool $kering;

    private array $laporan = [];

    public function handle(): int
    {
        $this->p = $this->option('prefix');
        $this->kering = (bool) $this->option('kering');
        $this->info($this->kering ? '— MODE UJI (tidak menulis) —' : '— MODE TULIS —');

        $this->imporPengaturan();
        $this->imporPengguna();
        $this->imporHalaman();
        $this->imporBerita();
        $this->imporUstadzDanSantri();
        $this->imporWakaf();
        $this->imporDonatur();
        $this->imporPerkembangan();

        $this->newLine();
        $this->table(['Bagian', 'Dibaca', 'Ditulis', 'Dilewati', 'Catatan'], $this->laporan);

        return self::SUCCESS;
    }

    private function catat(string $bagian, int $baca, int $tulis, int $lewat, string $catatan = ''): void
    {
        $this->laporan[] = [$bagian, $baca, $tulis, $lewat, $catatan];
        $this->line(sprintf('  %-22s %3d dibaca · %3d ditulis · %3d dilewati %s', $bagian, $baca, $tulis, $lewat, $catatan));
    }

    /** Simpan/ambil data dengan kunci unik; hormati mode uji. */
    private function simpan(string $tabel, array $kunci, array $nilai, array $tambahan = []): int
    {
        if ($this->kering) {
            return DB::table($tabel)->where($kunci)->exists() ? 0 : 1;
        }
        $row = DB::table($tabel)->where($kunci)->first();
        if ($row) {
            DB::table($tabel)->where('id', $row->id)->update($nilai + $tambahan + ['updated_at' => now()]);

            return 0;
        }
        DB::table($tabel)->insert($kunci + $nilai + $tambahan + ['created_at' => now(), 'updated_at' => now()]);

        return 1;
    }

    /** Ambil teks dari data Elementor (page builder) → HTML sederhana. */
    private function teksElementor(?string $json): string
    {
        if (! $json) {
            return '';
        }
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return '';
        }
        $keluar = [];
        $jalan = function ($simpul) use (&$jalan, &$keluar) {
            if (! is_array($simpul)) {
                return;
            }
            if (! empty($simpul['widgetType'])) {
                $s = $simpul['settings'] ?? [];
                $judul = $s['title'] ?? $s['heading'] ?? null;
                $teks = $s['editor'] ?? $s['text'] ?? $s['description'] ?? $s['html'] ?? null;
                if (is_string($judul) && trim($judul) !== '') {
                    $keluar[] = '<h2>'.e(strip_tags($judul)).'</h2>';
                }
                if (is_string($teks) && trim(strip_tags($teks)) !== '') {
                    $keluar[] = '<p>'.strip_tags($teks, '<b><strong><i><em><br><a>').'</p>';
                }
                if (($simpul['widgetType'] === 'image') && ! empty($s['image']['url'])) {
                    $keluar[] = '<p><img src="'.$s['image']['url'].'" alt="'.e($s['image']['alt'] ?? '').'"></p>';
                }
            }
            foreach ($simpul as $anak) {
                if (is_array($anak)) {
                    $jalan($anak);
                }
            }
        };
        $jalan($data);

        return implode("\n", array_unique($keluar));
    }

    /** Bersihkan isi post klasik: buang shortcode & blok komentar. */
    private function bersihkan(string $isi): string
    {
        $isi = preg_replace('/\[[^\]]*\]/', '', $isi);
        $isi = preg_replace('/<!--.*?-->/s', '', $isi);
        $isi = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $isi);
        $isi = trim($isi);

        return $isi === '' ? '' : $isi;
    }

    private function meta(int $postId, string $kunci): ?string
    {
        $r = DB::table($this->p.'postmeta')->where('post_id', $postId)->where('meta_key', $kunci)->value('meta_value');

        return is_string($r) ? $r : null;
    }

    private function imporPengaturan(): void
    {
        $opsi = DB::table($this->p.'options')->whereIn('option_name', ['blogname', 'blogdescription', 'admin_email', 'timezone_string'])->pluck('option_value', 'option_name');
        $isi = [
            'nama_musholla' => $opsi['blogname'] ?? 'Al Karim Islamic Center',
            'slogan' => $opsi['blogdescription'] ?? '',
            'email_situs' => $opsi['admin_email'] ?? '',
            'zona_waktu' => $opsi['timezone_string'] ?? 'Asia/Jakarta',
        ];
        $tulis = 0;
        foreach ($isi as $k => $v) {
            $tulis += $this->simpan('pengaturan', ['kunci' => $k], ['nilai' => $v]);
        }
        $this->catat('pengaturan', count($isi), $tulis, count($isi) - $tulis);
    }

    private function imporPengguna(): void
    {
        // Peran disederhanakan atas permintaan pemilik: hanya Admin & Anggota.
        $peta = ['administrator' => 'admin', 'administrator_utama' => 'admin'];
        $users = DB::table($this->p.'users')->get();
        $tulis = $lewat = 0;
        foreach ($users as $u) {
            $cap = $this->metaUser($u->ID, $this->p.'capabilities');
            // Akun modul lomba (juri) sudah dihapus dari sistem — jangan diimpor kembali.
            // Catatan: di WordPress akun ini berperan um_ustadz, jadi penandanya harus daftar surel.
            if (in_array(strtolower((string) $u->user_email), self::PENGGUNA_DILEWATI, true)) {
                $lewat++;
                continue;
            }
            $peran = 'anggota';
            foreach ($peta as $wp => $lrv) {
                if ($cap && str_contains($cap, '"'.$wp.'"')) {
                    $peran = $lrv;
                    break;
                }
            }
            $ada = DB::table('users')->where('email', $u->user_email)->exists();
            if ($ada) {
                $lewat++;
            } else {
                if (! $this->kering) {
                    DB::table('users')->insert([
                        'name' => $u->display_name ?: $u->user_login,
                        'email' => $u->user_email,
                        'password' => '$2y$12$'.Str::random(50),
                        'peran' => $peran,
                        'nama_lengkap' => $u->display_name ?: $u->user_login,
                        'aktif' => ! str_contains((string) $this->metaUser($u->ID, 'wpvr_user_status') ?: 'approved', 'inactive'),
                        'created_at' => $u->user_registered,
                        'updated_at' => now(),
                    ]);
                }
                $tulis++;
            }
        }
        $this->catat('pengguna', count($users), $tulis, $lewat, 'sandi direset (WP memakai hash lama)');
    }

    private function metaUser(int $userId, string $kunci): ?string
    {
        $r = DB::table($this->p.'usermeta')->where('user_id', $userId)->where('meta_key', $kunci)->value('meta_value');

        return is_string($r) ? $r : null;
    }

    /** Halaman modul lomba — dihapus atas permintaan pemilik (18 Sep 2026).
     *  Daftar ini mencegahnya muncul kembali bila importer dijalankan ulang. */
    /** Akun yang sengaja tidak diimpor (bagian lomba/juri dihapus atas permintaan pemilik). */
    private const PENGGUNA_DILEWATI = ['juri1@mushollaalkarim.web.id'];

    private const HALAMAN_DILEWATI = [
        // modul lomba (dihapus atas permintaan pemilik)
        'lomba-story-telling', 'sukses-daftar', 'data-peserta', 'lomba-muadzin', 'juri-muadzin',
        // halaman bawaan plugin WordPress (login/akun) — di aplikasi baru ditangani panel
        'user', 'login', 'register', 'members', 'logout', 'account', 'password-reset',
        // dashboard lama ustadz & orang tua — dihapus atas permintaan pemilik
        'dashboard-ustadz', 'dashboard-ortu',
    ];

    private function imporHalaman(): void
    {
        $hal = DB::table($this->p.'posts')->where('post_type', 'page')->whereIn('post_status', ['publish', 'draft'])->get();
        $menu = DB::table($this->p.'posts')->where('post_type', 'nav_menu_item')->where('post_status', 'publish')->pluck('post_title')->all();
        $tulis = $lewat = 0;
        foreach ($hal as $h) {
            if (in_array($h->post_name, self::HALAMAN_DILEWATI, true)) {
                $lewat++;
                continue;
            }
            $el = $this->teksElementor($this->meta($h->ID, '_elementor_data'));
            $isi = $el !== '' ? $el : $this->bersihkan((string) $h->post_content);
            if (! $this->kering) {
                $r = DB::table('pages')->where('slug', $h->post_name)->first();
                $data = [
                    'judul' => $h->post_title,
                    'isi' => $isi,
                    'ringkasan' => Str::limit(trim(strip_tags($this->meta($h->ID, '_yoast_wpseo_metadesc') ?? '')), 480) ?: null,
                    'meta_judul' => $this->meta($h->ID, '_yoast_wpseo_title'),
                    'meta_deskripsi' => $this->meta($h->ID, '_yoast_wpseo_metadesc'),
                    'terbit_at' => $h->post_status === 'publish' ? ($h->post_date ?: now()) : null,
                    'tampil_di_menu' => in_array($h->post_title, $menu, true),
                    'updated_at' => now(),
                ];
                if ($r) {
                    DB::table('pages')->where('id', $r->id)->update($data);
                    $lewat++;
                } else {
                    DB::table('pages')->insert($data + ['slug' => $h->post_name, 'urutan_menu' => 0, 'created_at' => now()]);
                    $tulis++;
                }
            } else {
                $tulis++;
            }
        }
        $this->catat('halaman', count($hal), $tulis, $lewat, 'isi dari Elementor/HTML bersih');
    }

    private function imporBerita(): void
    {
        $b = DB::table($this->p.'posts')->where('post_type', 'berita_musholla')->whereIn('post_status', ['publish', 'draft'])->get();
        $tulis = $lewat = 0;
        foreach ($b as $x) {
            $slug = $x->post_name ?: Str::slug($x->post_title);
            $ada = DB::table('berita')->where('slug', $slug)->exists();
            if ($ada) {
                $lewat++;
                continue;
            }
            if (! $this->kering) {
                DB::table('berita')->insert([
                    'judul' => $x->post_title,
                    'slug' => $slug,
                    'ringkasan' => Str::limit(trim(strip_tags((string) $x->post_excerpt ?: (string) $x->post_content)), 300),
                    'isi' => $this->teksElementor($this->meta($x->ID, '_elementor_data')) ?: $this->bersihkan((string) $x->post_content),
                    'kategori' => 'berita',
                    'gambar_path' => $this->meta($x->ID, '_thumbnail_id') ? 'wp-uploads' : null,
                    'terbit_at' => $x->post_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $tulis++;
        }
        $this->catat('berita', count($b), $tulis, $lewat);
    }

    private function imporUstadzDanSantri(): void
    {
        foreach ([['ustadz', 'ustadz'], ['santri', 'santri']] as [$pt, $tabel]) {
            $posts = DB::table($this->p.'posts')->where('post_type', $pt)->whereIn('post_status', ['publish', 'draft'])->get();
            $tulis = $lewat = 0;
            $metaKunci = [];
            foreach ($posts as $x) {
                $nama = $x->post_title;
                if (DB::table($tabel)->where('nama', $nama)->exists()) {
                    $lewat++;
                    continue;
                }
                $meta = DB::table($this->p.'postmeta')->where('post_id', $x->ID)
                    ->whereNotIn('meta_key', ['_edit_lock', '_edit_last', '_elementor_data', '_elementor_edit_mode', '_elementor_version', '_wp_page_template'])->pluck('meta_value', 'meta_key')->all();
                $metaKunci = array_merge($metaKunci, array_keys($meta));
                if (! $this->kering) {
                    $kolom = ['nama' => $nama, 'aktif' => true, 'created_at' => now(), 'updated_at' => now()];
                    if ($tabel === 'santri') {
                        $nama = $meta['_santri_nama_lengkap'] ?? $nama;
                        $kolom['nama'] = $nama;
                        $kolom['tempat_lahir'] = $meta['_santri_tempat_lahir'] ?? null;
                        $kolom['tanggal_lahir'] = ($meta['_santri_tanggal_lahir'] ?? null) ?: null;
                        $kolom['kelompok'] = $meta['_santri_kelas'] ?? $meta['kelompok'] ?? $meta['kelas'] ?? null;
                        $keluarga = array_filter([
                            'ayah' => $meta['_santri_nama_ayah'] ?? null,
                            'ibu' => $meta['_santri_nama_ibu'] ?? null,
                            'wali' => $meta['_santri_nama_wali'] ?? null,
                        ]);
                        $sisa = array_diff_key($meta, array_flip(['_santri_nama_lengkap', '_santri_tempat_lahir', '_santri_tanggal_lahir', '_santri_nama_ayah', '_santri_nama_ibu', '_santri_nama_wali', '_santri_kelas', 'kelompok', 'kelas']));
                        $kolom['catatan'] = trim(($keluarga ? json_encode($keluarga, JSON_UNESCAPED_UNICODE) : '').' '.($sisa ? json_encode($sisa, JSON_UNESCAPED_UNICODE) : '')) ?: null;
                    } else {
                        $namalengkap = trim(($meta['_ustadz_first_name'] ?? '').' '.($meta['_ustadz_last_name'] ?? ''));
                        $kolom['nama'] = $namalengkap !== '' ? $namalengkap : $nama;
                        $kolom['no_wa'] = $meta['_ustadz_telepon'] ?? $meta['no_wa'] ?? null;
                        $kolom['bidang'] = $meta['_ustadz_bidang'] ?? null;
                        $sisa = array_diff_key($meta, array_flip(['_ustadz_first_name', '_ustadz_last_name', '_ustadz_telepon', '_ustadz_bidang', 'no_wa', 'nomor_wa']));
                        $kolom['catatan'] = json_encode($sisa, JSON_UNESCAPED_UNICODE) ?: null;
                    }
                    DB::table($tabel)->insert($kolom);
                }
                $tulis++;
            }
            $this->catat($tabel, count($posts), $tulis, $lewat, $metaKunci ? 'meta: '.implode(',', array_slice(array_unique($metaKunci), 0, 6)) : '');
        }
    }

    private function imporWakaf(): void
    {
        $posts = DB::table($this->p.'posts')->where('post_type', 'program_wakaf')->get();
        $tulis = $lewat = 0;
        foreach ($posts as $x) {
            if (DB::table('wakaf_program')->where('nama', $x->post_title)->exists()) {
                $lewat++;
                continue;
            }
            if (! $this->kering) {
                DB::table('wakaf_program')->insert([
                    'nama' => $x->post_title,
                    'keterangan' => $this->bersihkan((string) $x->post_content) ?: null,
                    'target' => (float) ($this->meta($x->ID, 'target') ?: 0),
                    'terkumpul' => (float) ($this->meta($x->ID, 'terkumpul') ?: 0),
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $tulis++;
        }
        $this->catat('program wakaf', count($posts), $tulis, $lewat);
    }

    private function imporDonatur(): void
    {
        $d = DB::table($this->p.'wa_donatur')->get();
        $tulis = $lewat = 0;
        foreach ($d as $x) {
            if (DB::table('donatur')->where('nama', $x->nama)->where('no_wa', $x->nomor_wa)->exists()) {
                $lewat++;
                continue;
            }
            if (! $this->kering) {
                DB::table('donatur')->insert([
                    'nama' => $x->nama,
                    'no_wa' => $x->nomor_wa,
                    'kategori' => $x->kategori,
                    'keterangan' => null,
                    'aktif' => (bool) $x->is_subscribed,
                    'created_at' => $x->tanggal_dibuat ?: now(),
                    'updated_at' => now(),
                ]);
            }
            $tulis++;
        }
        $this->catat('donatur', count($d), $tulis, $lewat);

        $t = DB::table($this->p.'wa_templates')->get();
        $tulis = $lewat = 0;
        foreach ($t as $x) {
            if (DB::table('wa_template')->where('judul', $x->judul)->exists()) {
                $lewat++;
                continue;
            }
            if (! $this->kering) {
                DB::table('wa_template')->insert([
                    'judul' => $x->judul,
                    'isi' => (string) $x->isi_template,
                    'aktif' => true,
                    'created_at' => $x->tanggal_dibuat ?: now(),
                    'updated_at' => now(),
                ]);
            }
            $tulis++;
        }
        $this->catat('template WA', count($t), $tulis, $lewat);
    }

    private function imporPerkembangan(): void
    {
        $tabel = $this->p.'simta_perkembangan';
        if (! DB::getSchemaBuilder()->hasTable($tabel)) {
            $this->catat('perkembangan santri', 0, 0, 0, 'tabel sumber tidak ada');

            return;
        }
        $rows = DB::table($tabel)->get();
        $tulis = $lewat = 0;
        foreach ($rows as $r) {
            // santri_id WordPress ≠ id Laravel → cocokkan lewat nama bila mungkin
            $santriLama = DB::table($this->p.'posts')->where('ID', $r->santri_id)->value('post_title');
            $santri = $santriLama ? DB::table('santri')->where('nama', $santriLama)->value('id') : null;
            // tanggal sumber memuat jam, yang tersimpan hanya tanggal → samakan dulu saat memeriksa,
            // kalau tidak, pemeriksaan "sudah ada" selalu gagal dan data tergandakan tiap dijalankan.
            $tanggal = substr((string) $r->tanggal, 0, 10) ?: null;
            $ada = DB::table('penilaian')
                ->where('santri_id', $santri)
                ->where('kategori', $r->kategori)
                ->where(fn ($q) => $q->whereDate('tanggal', $tanggal)->orWhere('tanggal', $tanggal))
                ->where(fn ($q) => $q->where('detail_materi', $r->detail_materi)->orWhereNull('detail_materi'))
                ->exists();
            if ($ada) {
                $lewat++;
                continue;
            }
            if (! $this->kering) {
                DB::table('penilaian')->insert([
                    'santri_id' => $santri,
                    'tanggal' => $tanggal,
                    'kategori' => $r->kategori,
                    'detail_materi' => $r->detail_materi,
                    'nilai_1' => $r->nilai_1, 'nilai_2' => $r->nilai_2, 'nilai_3' => $r->nilai_3,
                    'ustadz_nama' => $r->ustadz_nama,
                    'catatan_ustadz' => $r->catatan_ustadz,
                    'created_at' => $r->tanggal ?: now(),
                    'updated_at' => now(),
                ]);
            }
            $tulis++;
        }
        $this->catat('perkembangan santri', count($rows), $tulis, $lewat, 'santri dicocokkan lewat nama');
    }
}
