<?php

namespace App\Services;

use App\Models\Berita;
use Illuminate\Contracts\Support\Stringable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mesin penerbit BERITA musholla Al Karim: dipakai endpoint POST /api/tulis.
 * Pola sama dengan TerbitTulisan masfahri.online, disesuaikan ke tabel berita:
 * - HTML dibersihkan (script/style/atribut class & style dibuang).
 * - Idempoten: slug sama = berita DIPERBARUI, bukan digandakan.
 * - Hanya menambah/memperbarui — tidak ada penghapusan.
 */
class TerbitBerita
{
    /** Terisi setelah simpan(): true bila berita lama diperbarui. */
    public bool $diperbarui = false;

    private const TAG = '<p><h2><h3><h4><ul><ol><li><strong><em><u><s><a><br><blockquote><table><thead><tbody><tr><th><td><hr><code><pre>';

    public function simpan(array $data): Berita
    {
        $judul = trim((string) ($data['judul'] ?? ''));
        $isi = trim((string) ($data['isi'] ?? ''));

        if ($judul === '') {
            throw new \InvalidArgumentException('Judul wajib diisi.');
        }
        if (mb_strlen(strip_tags($isi)) < 40) {
            throw new \InvalidArgumentException('Isi berita terlalu pendek (minimal 40 huruf).');
        }

        $terbit = ! empty($data['terbit']);
        $slug = Str::slug((string) ($data['slug'] ?? '')) ?: Str::slug($judul);
        $bersih = $this->bersihkanHtml($isi);
        $kategori = $this->teksDariInput($data['kategori'] ?? null, 60) ?: null;

        $berita = Berita::query()->where('slug', $slug)->first() ?: new Berita;

        DB::transaction(function () use ($berita, $judul, $slug, $bersih, $terbit, $kategori, $data) {
            $this->diperbarui = $berita->exists;
            $berita->judul = $judul;
            $berita->slug = $slug;
            $ringkasan = $this->teksDariInput($data['ringkasan'] ?? null, 65535);
            $berita->ringkasan = ($ringkasan !== '' ? $ringkasan : Str::limit(strip_tags($bersih), 180));
            $berita->isi = $bersih;
            $berita->kategori = $kategori;
            $gambar = $this->teksDariInput($data['gambar'] ?? null, 255);
            if ($gambar !== '') {
                $berita->gambar_path = $gambar;
            }
            if ($terbit) {
                if (! $berita->terbit_at) {
                    $berita->terbit_at = now();
                }
            } else {
                $berita->terbit_at = null;   // draf: tidak tampil publik
            }
            $berita->save();
        });

        return $berita->fresh();
    }

    /**
     * Normalisasi medan teks dari bot penulis: bentuk apa pun jadi string.
     * - null        -> '' (kosong)
     * - string      -> trim
     * - array       -> gabungkan elemen (string/angka/nullable) yang tidak
     *                  kosong dengan pemisah ', ' — bot kadang mengirim
     *                  kategori/ringkasan/gambar sebagai array JSON.
     * Hasil dipotong ke $maks karakter supaya muat di kolom VARCHAR/TEXT.
     */
    private function teksDariInput(mixed $nilai, int $maks = 255): string
    {
        if ($nilai === null || is_scalar($nilai)) {
            $teks = trim((string) $nilai);
        } elseif (is_array($nilai)) {
            $bagus = [];
            foreach ($nilai as $e) {
                if ($e instanceof Stringable) {
                    $e = (string) $e;
                }
                if (is_scalar($e) && trim((string) $e) !== '') {
                    $bagus[] = trim((string) $e);
                }
            }
            $teks = implode(', ', $bagus);
        } else {
            $teks = '';
        }

        return mb_strlen($teks) > $maks ? rtrim(mb_substr($teks, 0, $maks)) : $teks;
    }

    /** Buang yang berbahaya & rapikan atribut; sisakan tag yang diizinkan. */
    public function bersihkanHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|form|input|svg)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed|form|input|svg)\b[^>]*/?>#i', '', $html);
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#\s(class|id|style|dir|role|contenteditable|data-[a-z-]+|aria-[a-z-]+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = strip_tags($html, self::TAG);
        $html = preg_replace('#<p>\s*(&nbsp;|\s)*</p>#i', '', $html);
        $html = preg_replace("#\n{3,}#", "\n\n", $html);

        return trim($html);
    }

    /** Ringkasan hasil untuk dibalas ke pemanggil (bot/CLI). */
    public function hasil(Berita $berita): array
    {
        return [
            'ok' => true,
            'id' => $berita->id,
            'judul' => $berita->judul,
            'slug' => $berita->slug,
            'status' => $berita->terbit_at ? 'terbit' : 'draf',
            'diperbarui' => $this->diperbarui,
            'tautan' => $berita->terbit_at ? url('/berita/'.$berita->slug) : null,
            'tautan_panel' => url('/kelola/beritas/'.$berita->id.'/edit'),
        ];
    }
}
