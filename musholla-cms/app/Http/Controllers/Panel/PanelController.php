<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pengelola modul panel: daftar, tambah, ubah, hapus.
 * Semua bentuk diambil dari App\Support\Panel (satu sumber definisi).
 */
class PanelController extends Controller
{
    public function daftar(Request $request, string $modul)
    {
        $def = Panel::satu($modul);
        $q = $def['model']::query();

        $cari = trim((string) $request->input('cari'));
        if ($cari !== '' && ! empty($def['cari'])) {
            $q->where(function ($w) use ($cari, $def) {
                foreach ($def['cari'] as $kolom) {
                    $w->orWhere($kolom, 'like', '%' . $cari . '%');
                }
            });
        }

        foreach (($def['urut'] ?? []) as $kolom => $arah) {
            $q->orderBy($kolom, $arah);
        }

        $baris = $q->paginate(15)->withQueryString();

        $data = [
            'modul' => $modul,
            'def' => $def,
            'baris' => $baris,
            'cari' => $cari,
        ];

        // Kas: sertakan rekap bulanan (saldo bersambung + tutup kas)
        if ($modul === 'kas') {
            $data['kasBulanan'] = [
                'baris' => \App\Support\KasBulanan::untukTampilan(),
                'berjalan' => \App\Support\KasBulanan::bulanBerjalan(),
            ];
        }

        return view('panel.daftar', $data);
    }

    public function tambah(string $modul)
    {
        $def = Panel::satu($modul);
        abort_if(! empty($def['hanyaLihat']), 403);

        return view('panel.form', ['modul' => $modul, 'def' => $def, 'rekaman' => null]);
    }

    public function simpan(Request $request, string $modul)
    {
        $def = Panel::satu($modul);
        abort_if(! empty($def['hanyaLihat']), 403);

        $siap = $this->siapkanData($request, $modul, $def, null);
        $data = $siap['data'];

        $model = $def['model'];
        $rekaman = new $model();
        foreach ($data as $k => $v) {
            $rekaman->{$k} = $v;
        }
        $rekaman->save();

        $this->simpanRelasi($rekaman, $siap['relasi']);

        return redirect()->route('panel.daftar', $modul)->with('sukses', $def['judulSatu'] . ' baru tersimpan.');
    }

    public function ubah(string $modul, int $id)
    {
        $def = Panel::satu($modul);
        abort_if(! empty($def['hanyaLihat']), 403);
        $rekaman = $def['model']::findOrFail($id);

        return view('panel.form', ['modul' => $modul, 'def' => $def, 'rekaman' => $rekaman]);
    }

    public function perbarui(Request $request, string $modul, int $id)
    {
        $def = Panel::satu($modul);
        abort_if(! empty($def['hanyaLihat']), 403);
        $rekaman = $def['model']::findOrFail($id);

        $siap = $this->siapkanData($request, $modul, $def, $rekaman);
        foreach ($siap['data'] as $k => $v) {
            $rekaman->{$k} = $v;
        }
        $rekaman->save();

        $this->simpanRelasi($rekaman, $siap['relasi']);

        return redirect()->route('panel.daftar', $modul)->with('sukses', $def['judulSatu'] . ' berhasil diperbarui.');
    }

    /** Sinkronkan pilihan banyak (mis. kategori berita). */
    private function simpanRelasi($rekaman, array $relasi): void
    {
        foreach ($relasi as $nama => $ids) {
            if (method_exists($rekaman, $nama)) {
                $rekaman->{$nama}()->sync($ids);
            }
        }
    }

    public function hapus(string $modul, int $id)
    {
        $def = Panel::satu($modul);
        abort_if(! empty($def['hanyaLihat']), 403);
        $rekaman = $def['model']::findOrFail($id);

        // buang berkas lampiran milik rekaman ini
        foreach (['gambar_path', 'bukti_path', 'foto_path'] as $kolom) {
            $isi = $rekaman->{$kolom} ?? null;
            if ($isi && \Storage::disk('public')->exists($isi)) {
                \Storage::disk('public')->delete($isi);
            }
        }

        $rekaman->delete();

        return redirect()->route('panel.daftar', $modul)->with('sukses', $def['judulSatu'] . ' sudah dihapus.');
    }

    /**
     * Validasi + susun data yang akan disimpan.
     */
    private function siapkanData(Request $request, string $modul, array $def, $rekaman): array
    {
        // Nominal (tipe uang): terima bentuk apa pun — "69.500", "69500", "Rp 69.500" —
        // dan simpan angkanya saja. Tanpa ini, titik ribuan bisa dibaca 69,5 (salah) atau
        // ditolak aturan `numeric`.
        foreach ($def['field'] as $f) {
            if (($f['tipe'] ?? '') === 'uang') {
                $request->merge([
                    $f['nama'] => preg_replace('/[^0-9]/', '', (string) $request->input($f['nama'], '')),
                ]);
            }
        }

        $aturan = [];
        $pesan = [];
        foreach ($def['field'] as $f) {
            if ($f['tipe'] === 'berkas' || $f['tipe'] === 'sandi') {
                $aturan[$f['nama']] = $f['rules'] ?? ['nullable'];
                continue;
            }
            $aturan[$f['nama']] = $f['rules'] ?? ['nullable'];
        }

        // email pengguna wajib unik
        if ($modul === 'users') {
            $aturan['email'][] = 'unique:users,email' . ($rekaman ? ',' . $rekaman->id : '');
            $pesan['email.unique'] = 'Email ini sudah dipakai akun lain.';
        }
        // slug halaman/berita unik bila diisi
        if (in_array($modul, ['pages', 'berita'], true)) {
            $tabel = $modul;
            $aturan['slug'][] = 'unique:' . $tabel . ',slug' . ($rekaman ? ',' . $rekaman->id : '');
            $pesan['slug.unique'] = 'Slug ini sudah dipakai. Pakai kata lain.';
        }

        $bersih = $request->validate($aturan, $pesan);
        $data = [];
        $relasi = [];

        foreach ($def['field'] as $f) {
            $nama = $f['nama'];
            $tipe = $f['tipe'];

            if ($tipe === 'sandi') {
                $isi = (string) $request->input($nama, '');
                if ($isi !== '') {
                    $data[$nama] = Hash::make($isi);
                }
                continue;
            }

            if ($tipe === 'pilihan-banyak') {
                if (! empty($f['relasi'])) {
                    $relasi[$f['relasi']] = array_values(array_filter((array) $request->input($nama, [])));
                }
                continue;
            }

            if ($tipe === 'berkas') {
                if ($request->hasFile($nama) && $request->file($nama)->isValid()) {
                    $lama = $rekaman?->{$nama};
                    if ($lama && \Storage::disk('public')->exists($lama)) {
                        \Storage::disk('public')->delete($lama);
                    }
                    $data[$nama] = $request->file($nama)->store('panel/' . $modul, 'public');
                }
                continue;
            }

            if ($tipe === 'saklar') {
                $data[$nama] = $request->boolean($nama);
                continue;
            }

            $isi = $bersih[$nama] ?? null;
            $data[$nama] = ($isi === '' ? null : $isi);
        }

        // isian otomatis (mis. siapa yang mencatat)
        if (! $rekaman) {
            foreach (($def['isiOtomatis'] ?? []) as $kolom => $sumber) {
                if ($sumber === 'user' && auth()->check()) {
                    $data[$kolom] = auth()->id();
                }
            }
        }

        // salinan kolom (mis. name ← nama_lengkap pada pengguna)
        foreach (($def['salin'] ?? []) as $tujuan => $sumber) {
            if (array_key_exists($sumber, $data)) {
                $data[$tujuan] = $data[$sumber];
            }
        }

        // slug otomatis dari judul bila kosong
        if (array_key_exists('slug', $data) && blank($data['slug'])) {
            $sumber = $data['judul'] ?? $data['nama'] ?? null;
            if ($sumber) {
                $data['slug'] = $this->slugUnik($def['model'], Str::slug($sumber), $rekaman?->id);
            }
        }

        // halaman baru: default terbit sekarang bila tidak diisi
        if ($modul === 'pages' && ! $rekaman && empty($data['terbit_at'])) {
            $data['terbit_at'] = now();
        }

        // jumlah terverifikasi dicatat otomatis
        if ($modul === 'infaq') {
            $data['diverifikasi_oleh'] = ($data['status'] ?? null) === 'terverifikasi'
                ? auth()->id()
                : ($rekaman?->diverifikasi_oleh);
        }

        return ['data' => $data, 'relasi' => $relasi];
    }

    private function slugUnik(string $model, string $slug, ?int $kecuali = null): string
    {
        $dasar = $slug ?: 'halaman';
        $calon = $dasar;
        $n = 2;
        while ($model::query()->where('slug', $calon)->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))->exists()) {
            $calon = $dasar . '-' . $n++;
        }

        return $calon;
    }
}
