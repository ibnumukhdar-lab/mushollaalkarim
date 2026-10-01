<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ProgramRincian;
use App\Models\WakafProgram;
use Illuminate\Http\Request;

/**
 * Rincian kebutuhan tiap program donasi (wakaf & infaq).
 *
 * Satu halaman panel: tiap program ditampilkan bersama baris rinciannya
 * (nama kebutuhan, jumlah, satuan, harga satuan). Subtotal & total dihitung
 * aplikasi (jumlah × harga satuan) sehingga halaman publik selalu konsisten —
 * pengurus tidak perlu mengalikan manual, dan angka total tidak bisa salah.
 */
class ProgramRincianController extends Controller
{
    public function index()
    {
        $program = WakafProgram::query()
            ->with('rincian')
            ->orderBy('jenis')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        return view('panel.program-rincian', [
            'program' => $program,
            'totalSemua' => $program->sum(fn ($p) => (float) $p->total_rincian),
        ]);
    }

    /** Tambah baris baru, perbarui baris, atau hapus baris (tombol "Hapus" pada baris). */
    public function simpanBaris(Request $request, WakafProgram $program)
    {
        $data = $request->validate([
            'baris_id' => ['nullable', 'integer'],
            'aksi' => ['nullable', 'in:simpan,hapus'],
            'nama' => ['nullable', 'string', 'max:200'],
            'jumlah' => ['nullable', 'string', 'max:20'],
            'satuan' => ['nullable', 'string', 'max:30'],
            'harga_satuan' => ['nullable', 'string', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ], [], [
            'nama' => 'nama kebutuhan',
            'harga_satuan' => 'harga satuan',
        ]);

        $kembali = fn (string $pesan) => redirect()
            ->route('panel.rincian.index', ['program' => $program->id])->with('sukses', $pesan);

        $adaBaris = ! empty($data['baris_id'])
            ? ProgramRincian::query()->where('wakaf_program_id', $program->id)->find($data['baris_id'])
            : null;

        // Tombol hapus pada baris yang sudah ada
        if (($data['aksi'] ?? 'simpan') === 'hapus') {
            if (! $adaBaris) {
                return $kembali('Baris tidak ditemukan — tidak ada yang dihapus.');
            }
            $nama = $adaBaris->nama;
            $adaBaris->delete();

            return $kembali('Baris “'.$nama.'” dihapus. Total kebutuhan '.$program->nama.' kini Rp '
                .number_format($program->fresh()->total_rincian, 0, ',', '.').'.');
        }

        if (trim((string) ($data['nama'] ?? '')) === '') {
            return back()->withErrors(['nama' => 'Nama kebutuhan belum diisi.'])->withInput();
        }

        // Nominal diterima dalam bentuk apa pun ("100.000", "100000", "Rp 100.000")
        $jumlah = $this->angka($data['jumlah'] ?? null, 1.0);
        $harga = $this->angka($data['harga_satuan'] ?? null, 0.0);

        if ($jumlah <= 0) {
            $jumlah = 1.0;
        }

        $isi = [
            'nama' => trim((string) $data['nama']),
            'jumlah' => $jumlah,
            'satuan' => trim((string) ($data['satuan'] ?? '')) ?: null,
            'harga_satuan' => $harga,
            'catatan' => trim((string) ($data['catatan'] ?? '')) ?: null,
        ];

        if ($adaBaris) {
            $isi['urutan'] = $data['urutan'] ?? $adaBaris->urutan;
            $adaBaris->update($isi);
            $pesan = 'Baris “'.$isi['nama'].'” diperbarui.';
        } else {
            $isi['urutan'] = $data['urutan'] ?? ((int) $program->rincian()->max('urutan') + 1);
            $program->rincian()->create($isi);
            $pesan = 'Baris “'.$isi['nama'].'” ditambahkan.';
        }

        return $kembali($pesan.' Total kebutuhan '.$program->nama.' kini Rp '
            .number_format($program->fresh()->total_rincian, 0, ',', '.').'.');
    }

    /** Hapus satu baris rincian (jalur tautan/aksi lain). */
    public function hapusBaris(ProgramRincian $baris)
    {
        $program = $baris->program;
        $nama = $baris->nama;
        $baris->delete();

        return redirect()
            ->route('panel.rincian.index', ['program' => $program?->id])
            ->with('sukses', 'Baris “'.$nama.'” dihapus.');
    }

    /**
     * Ubah isian jadi angka dengan aturan Indonesia:
     *   "1.250.000" / "Rp 1.250.000" / "1250000" → 1250000
     *   "1,5" / "1,5 kali"                       → 1.5
     * Titik dianggap pemisah ribuan bila di belakangnya ada tepat 3 angka
     * ("125.000" → 125000), koma selalu desimal.
     */
    private function angka(?string $isi, float $bawaan): float
    {
        $teks = preg_replace('/\s+/', '', trim((string) $isi)) ?? '';
        $teks = preg_replace('~[^0-9.,]~', '', $teks) ?? '';

        if ($teks === '') {
            return $bawaan;
        }

        if (str_contains($teks, ',')) {
            $teks = str_replace(['.', ','], ['', '.'], $teks);
        } else {
            $titikTerakhir = strrpos($teks, '.');
            if ($titikTerakhir !== false && strlen($teks) - $titikTerakhir - 1 === 3) {
                $teks = str_replace('.', '', $teks);
            }
        }

        return (float) $teks;
    }
}
