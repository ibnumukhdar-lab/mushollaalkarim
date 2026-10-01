<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\WaBroadcast;
use App\Models\WaPesan;
use App\Services\WhatsApp;
use App\Support\KasBulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pusat WhatsApp panel kelola: kirim pesan massal, pantau antrean,
 * atur gateway otomatis, dan atur notifikasi otomatis.
 */
class WaController extends Controller
{
    /** Halaman utama: menyusun & mengirim pesan. */
    public function pusat(Request $request)
    {
        $grupAktif = (string) $request->query('grup', 'subscriber');
        $daftarGrup = ['subscriber', 'donatur', 'admin'];
        if (! in_array($grupAktif, $daftarGrup, true)) {
            $grupAktif = 'subscriber';
        }

        $semua = [];
        foreach ($daftarGrup as $g) {
            $semua[$g] = WhatsApp::penerima($g);
        }

        // Laporan kas satu bulan tertentu — mis. sudah Oktober tapi melaporkan September.
        $bulanLaporan = $this->bulanLaporan($request);
        $periode = $bulanLaporan['periode'] ?? null;

        $isiAwal = old('isi');
        $judulAwal = old('judul');
        if ($periode !== null && ! $isiAwal) {
            $isiAwal = WhatsApp::drafLaporanKas($periode);
            $judulAwal = 'Laporan Kas '.$bulanLaporan['label'];
        }

        return view('panel.wa.pusat', [
            'grupAktif' => $grupAktif,
            'semua' => $semua,
            'penerima' => $semua[$grupAktif],
            'template' => DB::table('wa_template')->where('aktif', true)->orderBy('judul')->get(),
            'variabel' => $periode !== null ? WhatsApp::variabelBulan($periode) : WhatsApp::variabelStandar(),
            'gatewaySiap' => WhatsApp::gatewaySiap(),
            'ringkasan' => WhatsApp::ringkasan(),
            'bulanLaporan' => $bulanLaporan,
            'daftarBulan' => $bulanLaporan['daftar'] ?? [],
            'isiAwal' => $isiAwal,
            'judulAwal' => $judulAwal,
        ]);
    }

    /**
     * Bulan yang dipilih untuk laporan kas.
     *
     * - `?periode=2026-09` → laporan bulan itu.
     * - `?laporan=1` (tombol "Kirim laporan kas") → bulan terakhir yang punya catatan,
     *   biasanya bulan lalu yang baru ditutup.
     * Tanpa keduanya → halaman kirim pesan biasa (null).
     */
    private function bulanLaporan(Request $request): ?array
    {
        $rantai = KasBulanan::rantai();

        $daftar = [];
        foreach (array_reverse($rantai, true) as $p => $b) {
            if ((float) $b['masuk'] == 0.0 && (float) $b['keluar'] == 0.0) {
                continue;   // bulan tanpa catatan tidak perlu dilaporkan
            }
            $daftar[] = ['periode' => $p, 'label' => $b['label'], 'ditutup' => (bool) $b['ditutup']];
        }

        $diminta = (string) $request->query('periode', '');
        if (! preg_match('~^\d{4}-\d{2}$~', $diminta)) {
            // tombol "Kirim laporan kas" → bulan terakhir yang SUDAH DITUTUP (biasanya
            // bulan lalu yang baru dilaporkan), kalau tidak ada baru bulan terakhir berisi.
            $tutup = collect($daftar)->firstWhere('ditutup', true);
            $diminta = $request->boolean('laporan') ? (string) (($tutup['periode'] ?? $daftar[0]['periode'] ?? '')) : '';
        }
        if ($diminta === '' || ! isset($rantai[$diminta])) {
            return null;
        }

        return [
            'periode' => $diminta,
            'label' => $rantai[$diminta]['label'],
            'ringkas' => $rantai[$diminta],
            'daftar' => $daftar,
        ];
    }

    /** Kirim: buat kampanye + isi antrean. */
    public function kirim(Request $request)
    {
        $data = $request->validate([
            'judul' => ['nullable', 'string', 'max:150'],
            'isi' => ['required', 'string', 'min:10'],
            'grup' => ['nullable', 'string', 'max:20'],
            'mode' => ['required', 'in:manual,gateway'],
            'wa_template_id' => ['nullable', 'integer'],
            'periode' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'pilih' => ['required', 'array', 'min:1'],
            'pilih.*' => ['string', 'max:200'],
        ], [], [
            'isi' => 'isi pesan',
            'pilih' => 'penerima',
        ]);

        // "pilih" berisi "nomor|nama" dari daftar yang dicentang di halaman.
        $penerima = collect($data['pilih'])->map(function ($x) {
            [$nomor, $nama] = array_pad(explode('|', $x, 2), 2, null);
            $bersih = WhatsApp::nomorBersih($nomor);

            return strlen($bersih) >= 9 ? ['nama' => $nama ?: 'Tanpa nama', 'nomor' => $bersih, 'data' => []] : null;
        })->filter()->unique(fn ($p) => $p['nomor'])->values();

        if ($penerima->isEmpty()) {
            return back()->withInput()->with('galat', 'Tidak ada penerima yang sah — periksa nomor WhatsApp-nya.');
        }

        if ($data['mode'] === 'gateway' && ! WhatsApp::gatewaySiap()) {
            return back()->withInput()->with('galat', 'Mode otomatis belum bisa dipakai: URL & token gateway belum diisi di Pengaturan. Pakai mode manual dulu.');
        }

        // Laporan bulan tertentu: angka pesan (pemasukan/pengeluaran/saldo) memakai
        // bulan yang dipilih, bukan selalu bulan berjalan.
        $tambahan = ! empty($data['periode']) ? WhatsApp::variabelBulan($data['periode']) : [];

        if (empty($data['judul']) && ! empty($data['periode'])) {
            $data['judul'] = 'Laporan Kas '.($tambahan['bulan_laporan'] ?? '');
        }

        $kampanye = WhatsApp::buatKampanye($data, $penerima, $tambahan);

        if ($data['mode'] === 'gateway') {
            $hasil = WhatsApp::prosesAntrean($kampanye);

            return redirect()->route('panel.wa.antrean', ['k' => $kampanye->id])
                ->with('sukses', 'Terkirim otomatis: '.$hasil['terkirim'].' pesan'
                    .($hasil['gagal'] ? ', gagal '.$hasil['gagal'].' (lihat langkah lanjutan di antrean)' : '').'.');
        }

        return redirect()->route('panel.wa.antrean', ['k' => $kampanye->id])
            ->with('sukses', $penerima->count().' pesan masuk antrean. Buka WhatsApp untuk tiap penerima, lalu tandai terkirim.');
    }

    /** Antrean & riwayat. */
    public function antrean(Request $request)
    {
        $kampanye = WaBroadcast::query()->orderByDesc('id')->limit(30)->get();

        $aktif = null;
        if ($request->filled('k')) {
            $aktif = WaBroadcast::query()->find($request->query('k'));
        }
        $aktif ??= $kampanye->first();

        $pesan = $aktif ? $aktif->pesan()->orderByRaw("case status when 'menunggu' then 0 when 'gagal' then 1 else 2 end")->orderBy('id')->get() : collect();

        // notifikasi otomatis (tidak terikat kampanye)
        $otomatis = WaPesan::query()->whereNull('wa_broadcast_id')->orderByDesc('id')->limit(30)->get();

        return view('panel.wa.antrean', [
            'kampanye' => $kampanye,
            'aktif' => $aktif,
            'pesan' => $pesan,
            'otomatis' => $otomatis,
            'kemajuan' => $aktif ? $aktif->kemajuan : null,
            'ringkasan' => WhatsApp::ringkasan(),
            'gatewaySiap' => WhatsApp::gatewaySiap(),
        ]);
    }

    /** Tandai satu pesan sudah dikirim (mode manual). */
    public function tandai(WaPesan $pesan)
    {
        $pesan->update(['status' => 'terkirim', 'dikirim_at' => now(), 'galat' => null]);
        $this->perbaruiKampanye($pesan->wa_broadcast_id);

        return back()->with('sukses', 'Ditandai terkirim: '.$pesan->nama.'.');
    }

    /** Tandai gagal / dilewati. */
    public function lewati(WaPesan $pesan)
    {
        $pesan->update(['status' => 'gagal', 'galat' => 'Dilewati pengurus']);
        $this->perbaruiKampanye($pesan->wa_broadcast_id);

        return back()->with('sukses', 'Ditandai gagal: '.$pesan->nama.'.');
    }

    /** Coba kirim lagi sisa antrean lewat gateway. */
    public function proses(WaBroadcast $kampanye)
    {
        if (! WhatsApp::gatewaySiap()) {
            return back()->with('galat', 'Gateway belum disetel — isi URL & token dulu di Pengaturan.');
        }

        $kampanye->pesan()->where('status', 'gagal')->update(['status' => 'menunggu', 'galat' => null]);
        $hasil = WhatsApp::prosesAntrean($kampanye);

        return back()->with('sukses', 'Gateway: '.$hasil['terkirim'].' terkirim, '.$hasil['gagal'].' gagal.');
    }

    /** Pengaturan WA Auto (ringkas: saklar, link gateway, token). */
    public function pengaturan()
    {
        return view('panel.wa.pengaturan', [
            'p' => WhatsApp::pengaturanGateway(),
            'aktif' => WhatsApp::gatewaySiap(),
            'nomorPengurus' => WhatsApp::nomorPengurus(),
            'ringkasan' => WhatsApp::ringkasan(),
            'hasilUji' => session('hasil_uji'),
        ]);
    }

    public function simpanPengaturan(Request $request)
    {
        $data = $request->validate([
            'wa_auto_aktif' => ['nullable', 'in:0,1'],
            'wa_gateway_url' => ['nullable', 'string', 'max:250'],
            'wa_gateway_token' => ['nullable', 'string', 'max:250'],
        ]);

        $data['wa_auto_aktif'] = (string) $request->input('wa_auto_aktif', '0') === '1' ? '1' : '0';
        $data['wa_gateway_url'] = trim((string) ($data['wa_gateway_url'] ?? '')) ?: WhatsApp::BAWAAN['wa_gateway_url'];
        $data['wa_gateway_token'] = trim((string) ($data['wa_gateway_token'] ?? ''));

        foreach ($data as $kunci => $nilai) {
            if (DB::table('pengaturan')->where('kunci', $kunci)->exists()) {
                DB::table('pengaturan')->where('kunci', $kunci)->update(['nilai' => $nilai, 'updated_at' => now()]);
            } else {
                DB::table('pengaturan')->insert(['kunci' => $kunci, 'nilai' => $nilai, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $pesan = WhatsApp::gatewaySiap()
            ? 'Tersimpan. WA Auto aktif — bisa dites dengan tombol "Uji kirim".'
            : 'Tersimpan, tetapi WA Auto belum aktif. Perlu: saklar aktif, link gateway, dan token.';

        return back()->with('sukses', $pesan);
    }

    /** Kirim satu pesan uji supaya pengaturan bisa dipastikan bekerja. */
    public function ujiKirim(Request $request)
    {
        $data = $request->validate([
            'uji_nomor' => ['required', 'string', 'max:25'],
            'uji_pesan' => ['nullable', 'string', 'max:500'],
        ], [], ['uji_nomor' => 'nomor tujuan uji']);

        $pesan = trim((string) ($data['uji_pesan'] ?? '')) ?: 'Uji koneksi WA Auto dari panel Musholla Al Karim. '.now()->translatedFormat('j F Y H:i');

        $hasil = WhatsApp::ujiKirim($data['uji_nomor'], $pesan);

        return back()->with('hasil_uji', $hasil + ['pesan' => $pesan, 'waktu' => now()->translatedFormat('H:i:s')]);
    }

    /** Aturan notifikasi otomatis. */
    public function aturan()
    {
        $aturan = DB::table('wa_aturan')->orderBy('id')->get();

        return view('panel.wa.aturan', [
            'aturan' => $aturan,
            'variabel' => WhatsApp::variabelStandar(),
            'nomorPengurus' => WhatsApp::nomorPengurus(),
            'gatewaySiap' => WhatsApp::gatewaySiap(),
        ]);
    }

    public function simpanAturan(Request $request)
    {
        $data = $request->validate([
            'kunci' => ['required', 'string', 'max:40'],
            'nama' => ['required', 'string', 'max:120'],
            'isi' => ['nullable', 'string', 'max:2000'],
            'aktif' => ['nullable', 'boolean'],
            'penerima' => ['required', 'in:otomatis,antrean'],
        ]);

        $nilai = [
            'nama' => $data['nama'],
            'isi' => $data['isi'] ?? null,
            'aktif' => (bool) ($data['aktif'] ?? false),
            'penerima' => $data['penerima'],
            'updated_at' => now(),
        ];

        if (DB::table('wa_aturan')->where('kunci', $data['kunci'])->exists()) {
            DB::table('wa_aturan')->where('kunci', $data['kunci'])->update($nilai);
        } else {
            DB::table('wa_aturan')->insert($nilai + ['kunci' => $data['kunci'], 'created_at' => now()]);
        }

        return back()->with('sukses', 'Aturan "'.$data['nama'].'" tersimpan.');
    }

    /** Pratinjau pesan terisi untuk penerima pertama (dipakai halaman kirim). */
    public function pratinjau(Request $request)
    {
        $isi = (string) $request->query('isi', '');
        $grup = (string) $request->query('grup', 'donatur');
        $contoh = WhatsApp::penerima($grup)->first();

        // pratinjau juga mengikuti bulan laporan yang dipilih
        $periode = (string) $request->query('periode', '');
        $tambahan = preg_match('~^\d{4}-\d{2}$~', $periode) ? WhatsApp::variabelBulan($periode) : [];

        $tidakDikenal = WhatsApp::variabelTidakDikenal($isi);

        if (! $contoh) {
            return response()->json([
                'pesan' => WhatsApp::isiVariabel($isi, $tambahan),
                'penerima' => null,
                'tidak_dikenal' => $tidakDikenal,
            ]);
        }

        return response()->json([
            'pesan' => WhatsApp::isiVariabel($isi, array_merge($tambahan, $contoh['data'], [
                'nama' => $contoh['nama'], 'nama_penerima' => $contoh['nama'],
                'nama_donatur' => $contoh['nama'], 'nama_ortu' => $contoh['nama'],
            ])),
            'penerima' => $contoh['nama'],
            'nomor' => $contoh['nomor'],
            'tidak_dikenal' => $tidakDikenal,
        ]);
    }

    /** Hitung ulang angka kemajuan kampanye. */
    private function perbaruiKampanye(?int $kampanyeId): void
    {
        if (! $kampanyeId) {
            return;
        }

        $k = WaBroadcast::query()->find($kampanyeId);
        if (! $k) {
            return;
        }

        $k->update([
            'terkirim' => $k->pesan()->where('status', 'terkirim')->count(),
            'gagal' => $k->pesan()->where('status', 'gagal')->count(),
            'status' => $k->pesan()->where('status', 'menunggu')->exists() ? 'berjalan' : 'selesai',
            'selesai_at' => $k->pesan()->where('status', 'menunggu')->exists() ? null : now(),
        ]);
    }
}
