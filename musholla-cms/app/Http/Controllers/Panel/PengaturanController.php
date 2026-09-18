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
        'operasional_bulanan' => ['label' => 'Kebutuhan operasional per bulan (Rp)', 'bantuan' => 'Dipakai halaman Mari Berinfaq untuk menampilkan kebutuhan biaya bulanan.'],
        'makan_harian' => ['label' => 'Biaya Makan Gratis per hari (Rp)', 'bantuan' => 'Dipakai halaman Mari Berinfaq sebagai keterangan program makan gratis.'],
    ];

    public function index()
    {
        $tersimpan = Pengaturan::query()->pluck('nilai', 'kunci')->all();

        $tambahan = Pengaturan::query()
            ->whereNotIn('kunci', array_keys(self::BAKU))
            ->orderBy('kunci')
            ->pluck('nilai', 'kunci')
            ->all();

        return view('panel.pengaturan', [
            'grup' => Panel::menuSamping(),
            'baku' => self::BAKU,
            'tersimpan' => $tersimpan,
            'tambahan' => $tambahan,
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

        foreach (($data['isi'] ?? []) as $kunci => $nilai) {
            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        $kunciBaru = trim((string) ($data['baru_kunci'] ?? ''));
        if ($kunciBaru !== '') {
            Pengaturan::updateOrCreate(['kunci' => $kunciBaru], ['nilai' => $data['baru_nilai'] ?? '']);
        }

        return redirect()->route('panel.pengaturan')->with('sukses', 'Pengaturan situs tersimpan.');
    }
}
