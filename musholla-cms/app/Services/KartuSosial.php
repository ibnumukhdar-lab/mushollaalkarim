<?php

namespace App\Services;

use App\Models\Berita;
use App\Models\WakafProgram;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kartu sosial 1200×630 untuk pratinjau saat tautan dibagikan
 * (WhatsApp, Facebook, X, Telegram).
 *
 * Kartu dibuat sendiri dari judul + gambar sampul tulisan, jadi setiap
 * tulisan punya pratinjau yang rapi — bukan sekadar gambar sampul kotak.
 * Berkasnya dihasilkan sekali lalu dipakai ulang; namanya memuat cap
 * (hash) judul sehingga otomatis diperbarui bila tulisan diubah.
 */
class KartuSosial
{
    public const LEBAR = 1200;
    public const TINGGI = 630;

    /** Naikkan angka ini setiap kali desain kartu berubah, agar kartu lama dibangun ulang. */
    private const VERSI = 2;

    /** 630/1200 ≈ 1.905 — dipakai untuk memotong gambar sampul. */
    private const RASIO = 1.9047619;

    /** Lokasi font di penyimpanan aplikasi. */
    public static function font(string $jenis = 'tebal'): ?string
    {
        $peta = [
            'tebal' => ['fonts/PlusJakartaSans.ttf', 'fonts/DejaVuSans-Bold.ttf'],
            'judul' => ['fonts/playfair.ttf', 'fonts/PlusJakartaSans.ttf', 'fonts/DejaVuSans-Bold.ttf'],
        ];

        foreach ($peta[$jenis] ?? $peta['tebal'] as $relatif) {
            $jalur = storage_path('app/'.$relatif);
            if (file_exists($jalur)) {
                return $jalur;
            }
        }

        return null;
    }

    public static function tersedia(): bool
    {
        return function_exists('imagettftext') && self::font() !== null;
    }

    /**
     * URL kartu untuk sebuah tulisan. Dibuat saat pertama diminta.
     * Balikan null bila GD/font tidak tersedia — pemanggil memakai gambar bawaan.
     */
    public static function untuk(Berita $berita): ?string
    {
        if (! self::tersedia()) {
            return null;
        }

        $cap = substr(md5($berita->judul.'|'.($berita->getRawOriginal('gambar_path') ?? '').'|'.mb_strlen((string) $berita->isi).'|'.(self::fotoMentah(self::berkasSampul($berita)) ? 'asli' : '').'|v'.self::VERSI), 0, 8);
        $relatif = 'og/berita-'.$berita->id.'-'.$cap.'.jpg';

        if (Storage::disk('public')->exists($relatif)) {
            return url('/berkas/'.$relatif);
        }

        if (! self::buatBerita($berita, storage_path('app/public/'.$relatif))) {
            return null;
        }

        // rapikan kartu lama milik tulisan yang sama
        foreach (Storage::disk('public')->files('og') as $lama) {
            if (Str::startsWith($lama, 'og/berita-'.$berita->id.'-') && $lama !== $relatif) {
                Storage::disk('public')->delete($lama);
            }
        }

        return url('/berkas/'.$relatif);
    }

    /**
     * URL kartu untuk satu program wakaf (nama program + target + progres).
     * Balikan null bila GD/font tidak tersedia — pemanggil memakai gambar bawaan.
     */
    public static function wakaf(WakafProgram $program): ?string
    {
        if (! self::tersedia()) {
            return null;
        }

        $gambar = (string) ($program->getRawOriginal('gambar_path') ?? '');
        $cap = substr(md5($program->nama.'|'.$gambar.'|'.(float) $program->target.'|'.(float) $program->terkumpul.'|v'.self::VERSI), 0, 8);
        $relatif = 'og/wakaf-'.$program->id.'-'.$cap.'.jpg';

        if (Storage::disk('public')->exists($relatif)) {
            return url('/berkas/'.$relatif);
        }

        if (! self::buatWakaf($program, storage_path('app/public/'.$relatif))) {
            return null;
        }

        // rapikan kartu lama milik program yang sama
        foreach (Storage::disk('public')->files('og') as $lama) {
            if (Str::startsWith($lama, 'og/wakaf-'.$program->id.'-') && $lama !== $relatif) {
                Storage::disk('public')->delete($lama);
            }
        }

        return url('/berkas/'.$relatif);
    }

    /** Bangun kartu untuk satu program wakaf. */
    public static function buatWakaf(WakafProgram $program, string $keluar): bool
    {
        $sampul = self::berkasGambar($program->getRawOriginal('gambar_path'));
        $mentah = self::fotoMentah($sampul);
        if ($mentah) {
            $sampul = $mentah;
        }

        $target = (float) $program->target;
        $terkumpul = (float) $program->terkumpul;

        $bagian = [];
        if ($target > 0) {
            $bagian[] = 'Target Rp '.number_format($target, 0, ',', '.');
            $bagian[] = 'Terkumpul Rp '.number_format($terkumpul, 0, ',', '.').' ('.$program->persen.'%)';
        } else {
            $bagian[] = 'Terkumpul Rp '.number_format($terkumpul, 0, ',', '.');
        }

        return self::buatKanvas($keluar, 'Wakaf: '.$program->nama, implode(' • ', $bagian), $sampul, 'mushollaalkarim.web.id');
    }

    /** Gambar bawaan untuk halaman yang tak punya gambar sendiri. */
    public static function bawaan(): string
    {
        $relatif = 'og/bawaan.png';

        if (! Storage::disk('public')->exists($relatif) && self::tersedia()) {
            self::buatKanvas(
                storage_path('app/public/'.$relatif),
                'Musholla Al Karim',
                "Kajian rutin, pendidikan Al-Qur'an, dan laporan kas yang terbuka",
                null,
                'mushollaalkarim.web.id'
            );
        }

        return url('/berkas/'.$relatif);
    }

    /** Jalur berkas gambar di disk (null bila kosong, berupa URL, atau tidak ada). */
    private static function berkasGambar(?string $isi): ?string
    {
        if (! $isi || Str::startsWith($isi, ['http://', 'https://'])) {
            return null;
        }

        $jalur = storage_path('app/public/'.ltrim($isi, '/'));

        return file_exists($jalur) ? $jalur : null;
    }

    /** Jalur berkas sampul tulisan (null bila tidak ada atau berupa URL). */
    private static function berkasSampul(Berita $berita): ?string
    {
        return self::berkasGambar($berita->getRawOriginal('gambar_path') ?: null);
    }

    /**
     * Foto mentah di samping sampul: <nama-sampul>-asli.<ext>.
     *
     * Kartu sudah menambahkan judul dan tanggalnya sendiri, jadi memakai sampul yang sudah
     * berdesain teks membuat tulisan bertumpuk. Bila foto mentahnya ada, itulah yang dipakai.
     */
    private static function fotoMentah(?string $sampul): ?string
    {
        if (! $sampul) {
            return null;
        }

        $info = pathinfo($sampul);
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ekstensi) {
            $kandidat = $info['dirname'].'/'.$info['filename'].'-asli.'.$ekstensi;
            if (file_exists($kandidat)) {
                return $kandidat;
            }
        }

        return null;
    }

    /** Bangun kartu untuk satu tulisan. */
    public static function buatBerita(Berita $berita, string $keluar): bool
    {
        $sampul = self::berkasSampul($berita);
        $mentah = self::fotoMentah($sampul);
        if ($mentah) {
            $sampul = $mentah;
        }

        $ringkasan = \App\Support\Tulis::ringkas($berita->ringkasan ?: $berita->isi, 150);
        $tanggal = $berita->terbit_at ? $berita->terbit_at->translatedFormat('j F Y') : null;

        return self::buatKanvas($keluar, $berita->judul, $ringkasan, $sampul, $tanggal);
    }

    /** Kanvas 1200×630: sampul (bila ada) + lapis gelap + judul & ringkasan. */
    private static function buatKanvas(string $keluar, string $judul, ?string $ringkasan, ?string $sampul, ?string $kaki): bool
    {
        $W = self::LEBAR;
        $H = self::TINGGI;
        $img = imagecreatetruecolor($W, $H);

        // (1) latar: sampul dipotong mengisi kanvas, atau gradasi merek
        if ($sampul && self::tempelSampul($img, $sampul)) {
            // sampul berhasil ditempel
        } else {
            self::gradasi($img);
            $samar = imagecolorallocatealpha($img, 255, 255, 255, 120);
            imagefilledellipse($img, 1060, 70, 380, 380, $samar);
            imagefilledellipse($img, 120, 610, 300, 300, $samar);
        }

        // (2) lapis gelap hijau merek agar tulisan terbaca (semakin pekat ke bawah)
        $merek = [12, 44, 28]; // hijau Al Karim yang digelapkan
        for ($y = 0; $y < $H; $y++) {
            if ($y < $H * 0.28) {
                $kekuatan = 22;
            } else {
                $jalan = ($y - $H * 0.28) / ($H * 0.72);
                $kekuatan = (int) round(22 + 168 * pow($jalan, 1.3));
            }
            $kekuatan = min(190, $kekuatan);
            $lapis = imagecolorallocatealpha($img, $merek[0], $merek[1], $merek[2], 127 - (int) round($kekuatan * 127 / 255));
            imagefilledrectangle($img, 0, $y, $W, $y, $lapis);
        }

        // (2b) bayangan tambahan di jalur judul (sepertiga bawah) supaya teksnya tegas
        for ($y = (int) ($H * 0.60); $y < $H; $y++) {
            $jalan = ($y - $H * 0.60) / ($H * 0.40);
            $kekuatan = (int) round(78 * $jalan);
            $lapis = imagecolorallocatealpha($img, $merek[0], $merek[1], $merek[2], 127 - (int) round($kekuatan * 127 / 255));
            imagefilledrectangle($img, 0, $y, $W, $y, $lapis);
        }
        imagealphablending($img, true);

        $putih = imagecolorallocate($img, 255, 255, 255);
        $emas = imagecolorallocate($img, 226, 205, 150);
        $hijauTua = imagecolorallocate($img, 20, 54, 40);

        $fontTebal = self::font('tebal');
        $fontJudul = self::font('judul') ?: $fontTebal;

        // (3) merek di kiri atas
        $x = 70;
        $yMerek = 96;
        $lambang = self::lambang();
        if ($lambang) {
            $ikon = @imagecreatefrompng($lambang);
            if ($ikon) {
                $tinggi = 78;
                $lebar = (int) round(imagesx($ikon) / imagesy($ikon) * $tinggi);
                $putihBulat = imagecolorallocatealpha($img, 255, 255, 255, 26);
                imagefilledellipse($img, $x + 39, $yMerek - 20, 92, 92, $putihBulat);
                imagecopyresampled($img, $ikon, $x + 39 - (int) ($lebar / 2), $yMerek - 20 - (int) ($tinggi / 2), 0, 0, $lebar, $tinggi, imagesx($ikon), imagesy($ikon));
                $x += 118;
            }
        }
        if ($fontTebal) {
            imagettftext($img, 25, 0, $x, $yMerek - 8, $putih, $fontTebal, 'Musholla Al Karim');
            imagettftext($img, 17, 0, $x, $yMerek + 20, $emas, $fontTebal, 'mushollaalkarim.web.id');
        }

        // (4) judul + ringkasan di bawah
        $lebarTeks = $W - 140;
        $yDasar = $H - 92;

        if ($ringkasan && $fontTebal) {
            $barisRingkas = self::bungkus($ringkasan, $fontTebal, 22, $lebarTeks, 2);
            $yRingkas = $yDasar;
            foreach (array_reverse($barisRingkas) as $baris) {
                imagettftext($img, 22, 0, 70, $yRingkas, self::pudar($img, $putih), $fontTebal, $baris);
                $yRingkas -= 34;
            }
            $yJudulBawah = $yRingkas - 8;
        } else {
            $yJudulBawah = $yDasar;
        }

        foreach ([54, 48, 42, 38, 34] as $ukuran) {
            $barisJudul = self::bungkus($judul, $fontJudul, $ukuran, $lebarTeks, 3);
            if (count($barisJudul) <= 3) {
                break;
            }
        }
        $yJudul = $yJudulBawah - (count($barisJudul) - 1) * ($ukuran + 12);
        foreach ($barisJudul as $baris) {
            imagettftext($img, $ukuran, 0, 70, $yJudul, $putih, $fontJudul, $baris);
            $yJudul += $ukuran + 12;
        }

        // garis emas kecil di atas judul
        imagefilledrectangle($img, 70, $yJudulBawah - (count($barisJudul) * ($ukuran + 12)) - 22, 70 + 92, $yJudulBawah - (count($barisJudul) * ($ukuran + 12)) - 17, $emas);

        // (5) keterangan kaki (tanggal / alamat)
        if ($kaki && $fontTebal) {
            $lebar = imagettfbbox(18, 0, $fontTebal, $kaki);
            $kiri = $W - 70 - ($lebar[2] - $lebar[0]);
            $bulat = imagecolorallocatealpha($img, 0, 0, 0, 70);
            imagefilledrectangle($img, $kiri - 18, $H - 62, $W - 52, $H - 24, $bulat);
            imagettftext($img, 18, 0, $kiri, $H - 36, $emas, $fontTebal, $kaki);
        }

        @mkdir(dirname($keluar), 0755, true);
        $ok = imagejpeg($img, $keluar, 86);
        imagedestroy($img);

        return (bool) $ok;
    }

    /** Tempel gambar sampul memenuhi kanvas (dipotong tengah). */
    private static function tempelSampul($img, string $berkas): bool
    {
        $info = @getimagesize($berkas);
        if (! $info) {
            return false;
        }

        $sumber = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($berkas),
            IMAGETYPE_PNG => @imagecreatefrompng($berkas),
            IMAGETYPE_WEBP => @imagecreatefromwebp($berkas),
            IMAGETYPE_GIF => @imagecreatefromgif($berkas),
            default => null,
        };
        if (! $sumber) {
            return false;
        }

        $sw = imagesx($sumber);
        $sh = imagesy($sumber);

        // potong mengikuti rasio kartu
        if ($sw / $sh > self::RASIO) {
            $ch = $sh;
            $cw = (int) round($sh * self::RASIO);
            $cx = (int) (($sw - $cw) / 2);
            $cy = 0;
        } else {
            $cw = $sw;
            $ch = (int) round($sw / self::RASIO);
            $cx = 0;
            $cy = (int) (($sh - $ch) / 2);
        }

        imagecopyresampled($img, $sumber, 0, 0, $cx, $cy, self::LEBAR, self::TINGGI, $cw, $ch);
        imagedestroy($sumber);

        return true;
    }

    /** Gradasi hijau merek. */
    private static function gradasi($img): void
    {
        for ($y = 0; $y < self::TINGGI; $y++) {
            $t = $y / self::TINGGI;
            $warna = imagecolorallocate(
                $img,
                (int) round(47 + (30 - 47) * $t),
                (int) round(96 + (74 - 96) * $t),
                (int) round(70 + (54 - 70) * $t)
            );
            imageline($img, 0, $y, self::LEBAR, $y, $warna);
        }
    }

    /** Lambang Al Karim bila ada. */
    private static function lambang(): ?string
    {
        foreach ([
            storage_path('app/logo-alkarim.png'),
            base_path('public/icons/icon-512.png'),
            base_path('public/logo-alkarim.png'),
        ] as $jalur) {
            if (file_exists($jalur)) {
                return $jalur;
            }
        }

        return null;
    }

    /** Warna putih agak pudar. */
    private static function pudar($img, $putih)
    {
        return imagecolorallocatealpha($img, 255, 255, 255, 30);
    }

    /** Bungkus teks jadi beberapa baris sesuai lebar kanvas. */
    private static function bungkus(string $teks, string $font, int $ukuran, int $lebarMaks, int $maksBaris): array
    {
        $teks = trim(preg_replace('~\s+~', ' ', $teks));
        $kata = explode(' ', $teks);
        $baris = [];
        $sekarang = '';

        foreach ($kata as $k) {
            $coba = $sekarang === '' ? $k : $sekarang.' '.$k;
            $kotak = imagettfbbox($ukuran, 0, $font, $coba);
            if (($kotak[2] - $kotak[0]) > $lebarMaks && $sekarang !== '') {
                $baris[] = $sekarang;
                $sekarang = $k;
                if (count($baris) >= $maksBaris) {
                    break;
                }
            } else {
                $sekarang = $coba;
            }
        }

        if ($sekarang !== '' && count($baris) < $maksBaris) {
            $baris[] = $sekarang;
        }

        // tandai terpotong
        if (count($baris) >= $maksBaris) {
            $terakhir = $baris[count($baris) - 1];
            $sisaKata = count($kata) > count(explode(' ', implode(' ', $baris)));
            $kotak = imagettfbbox($ukuran, 0, $font, $terakhir.' …');
            if ($sisaKata && ($kotak[2] - $kotak[0]) <= $lebarMaks) {
                $baris[count($baris) - 1] = $terakhir.' …';
            }
        }

        return $baris;
    }
}
