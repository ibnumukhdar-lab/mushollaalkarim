<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Support\Panel;
use Illuminate\Http\Request;

/**
 * Pengaturan situs: pasangan kunci–nilai yang dipakai halaman publik.
 */
class PengaturanController extends Controller
{
    /** Kunci baku + keterangan supaya pengurus tahu isian penting. */
    public const BAKU = [
        'nama_situs' => ['label' => 'Nama situs', 'bantuan' => 'Tampil di kepala & kaki situs, mis. Musholla Al Karim.'],
        'slogan' => ['label' => 'Slogan', 'bantuan' => 'Kalimat singkat di bawah nama situs.'],
        'kontak_wa' => ['label' => 'Nomor WhatsApp pengurus', 'bantuan' => 'Format 628xxxxxxxxxx — dipakai tombol hubungi & konfirmasi infaq.'],
        'alamat' => ['label' => 'Alamat musholla', 'bantuan' => 'Alamat lengkap untuk ditampilkan di situs.'],
        'jam_operasional' => ['label' => 'Jam kegiatan', 'bantuan' => 'mis. Setiap hari 04.30–21.00 WIB.'],
    ];

    /** Kunci yang punya medan sendiri (bukan textarea "isian tambahan"). */
    private const KUNCI_TERSENDIRI = [Pengaturan::KUNCI_KATEGORI_INFAQ];

    public function index()
    {
        $tersimpan = Pengaturan::query()->pluck('nilai', 'kunci')->all();

        $tambahan = Pengaturan::query()
            ->whereNotIn('kunci', array_merge(array_keys(self::BAKU), self::KUNCI_TERSENDIRI))
            ->orderBy('kunci')
            ->pluck('nilai', 'kunci')
            ->all();

        return view('panel.pengaturan', [
            'grup' => Panel::menuSamping(),
            'baku' => self::BAKU,
            'tersimpan' => $tersimpan,
            'tambahan' => $tambahan,
            // daftar kategori ditampilkan satu per baris (textarea ringkas)
            'kategoriInfaq' => implode("\n", Pengaturan::infaqKategori()),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'isi' => ['array'],
            'isi.*' => ['nullable', 'string', 'max:2000'],
            'baru_kunci' => ['nullable', 'string', 'max:80'],
            'baru_nilai' => ['nullable', 'string', 'max:2000'],
        ]);

        $isi = $data['isi'] ?? [];

        // Daftar kategori tujuan infaq disimpan sebagai JSON (satu baris = satu kategori).
        if (array_key_exists(Pengaturan::KUNCI_KATEGORI_INFAQ, $isi)) {
            Pengaturan::simpanInfaqKategori((string) $isi[Pengaturan::KUNCI_KATEGORI_INFAQ]);
            unset($isi[Pengaturan::KUNCI_KATEGORI_INFAQ]);
        }

        foreach ($isi as $kunci => $nilai) {
            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        $kunciBaru = trim((string) ($data['baru_kunci'] ?? ''));
        if ($kunciBaru !== '') {
            Pengaturan::updateOrCreate(['kunci' => $kunciBaru], ['nilai' => $data['baru_nilai'] ?? '']);
        }

        return redirect()->route('panel.pengaturan')->with('sukses', 'Pengaturan situs tersimpan.');
    }
}
