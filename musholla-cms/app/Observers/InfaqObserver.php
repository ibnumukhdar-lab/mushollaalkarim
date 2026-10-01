<?php

namespace App\Observers;

use App\Models\Infaq;
use App\Models\WakafProgram;
use App\Services\WhatsApp;

/**
 * Infaq diverifikasi → ucapan terima kasih ke donatur.
 * Memakai observer supaya berlaku dari jalur mana pun (panel, Filament, skrip).
 *
 * CATATAN: kedua method di bawah SENGAJA memakai variabel yang sama-sama
 * didefinisikan di dalam methodnya sendiri (tidak ada variabel "warisan").
 * Penambahan variabel lewat str_replace pernah menyentuh dua method sekaligus
 * sehingga muncul ErrorException dan kas tidak tercatat.
 */
class InfaqObserver
{
    /**
     * Infaq baru masuk → kirim notifikasi WhatsApp ke pengurus supaya bisa
     * diverifikasi. Berlaku sama saja apakah donatur mengunggah bukti atau tidak;
     * status bukti ditulis di pesan agar pengurus tahu harus menunggu apa.
     */
    public function created(Infaq $infaq): void
    {
        $adaBukti = filled($infaq->bukti_path);

        // Donasi program wakaf masuk lewat tabel yang sama (tujuan = nama program wakaf)
        $programWakaf = WakafProgram::untukTujuan($infaq->tujuan);

        WhatsApp::notifikasi('infaq_masuk', [
            'nama' => $infaq->nama_donatur,
            'nama_donatur' => $infaq->nama_donatur,
            'nominal' => 'Rp '.number_format((float) $infaq->nominal, 0, ',', '.'),
            'tujuan' => $infaq->tujuan ?: 'Infaq umum',
            'jenis' => $programWakaf ? 'WAKAF — '.$programWakaf->nama : 'INFAQ',
            'tanggal' => $infaq->tanggal?->translatedFormat('j F Y') ?? now()->translatedFormat('j F Y'),
            'jam' => now()->format('H:i'),
            'bukti' => $adaBukti ? 'ada — siap diperiksa' : 'belum ada (donatur tidak mengunggah)',
            'no_wa' => (string) $infaq->no_wa,
            'keterangan' => (string) ($infaq->keterangan ?? ''),
        ]);
    }

    public function updated(Infaq $infaq): void
    {
        $jadiTerverifikasi = $infaq->wasChanged('status') && $infaq->status === 'terverifikasi';

        if (! $jadiTerverifikasi) {
            return;
        }

        // Menutup lubang "ubah status lewat form Ubah panel": status menjadi
        // terverifikasi tetapi baris kas belum ada → catat lewat SATU PINTU yang
        // sama (Infaq::verifikasi(), idempoten). Penanda verifikasiBerjalan menjaga
        // agar pemanggilan dari dalam verifikasi() sendiri tidak berputar lagi.
        if (blank($infaq->kas_id) && ! $infaq->verifikasiBerjalan) {
            $infaq->verifikasi($infaq->diverifikasi_oleh ?: auth()->id());
        }

        // Notifikasi hanya bila donatur meninggalkan nomor WhatsApp.
        if (! $infaq->no_wa) {
            return;
        }

        // Dipakai untuk menandai pesan terima kasih bila tujuannya program wakaf
        $programWakaf = WakafProgram::untukTujuan($infaq->tujuan);

        WhatsApp::notifikasi('infaq_verifikasi', [
            'nama' => $infaq->nama_donatur,
            'nama_donatur' => $infaq->nama_donatur,
            'nominal' => 'Rp '.number_format((float) $infaq->nominal, 0, ',', '.'),
            'tujuan' => $infaq->tujuan ?: 'Infaq umum',
            'jenis' => $programWakaf ? 'WAKAF — '.$programWakaf->nama : 'INFAQ',
            'tanggal' => $infaq->tanggal?->translatedFormat('j F Y') ?? now()->translatedFormat('j F Y'),
        ], $infaq->no_wa, $infaq->nama_donatur);
    }
}
