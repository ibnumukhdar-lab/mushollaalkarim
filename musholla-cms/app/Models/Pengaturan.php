<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    /** Kunci pengaturan berisi daftar kategori tujuan infaq (JSON). */
    public const KUNCI_KATEGORI_INFAQ = 'infaq_kategori';

    /** Isi awal — PERSIS seperti tiga kategori tetap di form publik sebelumnya. */
    public const KATEGORI_INFAQ_BAWAAN = ['Infaq umum', 'Infaq Makan Gratis', 'Infaq Operasional'];

    protected $fillable = [
        'kunci',
        'nilai',
    ];

    /**
     * Daftar kategori tujuan infaq untuk form publik /mari-berinfaq.
     * Bila pengaturan belum diisi / rusak, kembali ke daftar bawaan supaya
     * pilihan form tidak pernah kosong.
     *
     * @return list<string>
     */
    public static function infaqKategori(): array
    {
        $nilai = static::query()->where('kunci', self::KUNCI_KATEGORI_INFAQ)->value('nilai');

        $daftar = json_decode((string) $nilai, true);

        if (! is_array($daftar)) {
            return self::KATEGORI_INFAQ_BAWAAN;
        }

        $bersih = self::rakitBersih($daftar);

        return $bersih ?: self::KATEGORI_INFAQ_BAWAAN;
    }

    /**
     * Simpan daftar kategori (satu kategori per baris dari textarea panel).
     *
     * @param  string|array<int, string>  $isi
     */
    public static function simpanInfaqKategori(string|array $isi): void
    {
        $baris = is_array($isi) ? $isi : preg_split('/\r\n|\r|\n/', (string) $isi);

        static::updateOrCreate(
            ['kunci' => self::KUNCI_KATEGORI_INFAQ],
            ['nilai' => json_encode(self::rakitBersih((array) $baris), JSON_UNESCAPED_UNICODE)]
        );
    }

    /**
     * Buang baris kosong & kembar, rapikan spasi.
     *
     * @param  array<int, mixed>  $baris
     * @return list<string>
     */
    private static function rakitBersih(array $baris): array
    {
        $bersih = [];

        foreach ($baris as $satu) {
            $satu = trim((string) $satu);

            if ($satu !== '' && ! in_array($satu, $bersih, true)) {
                $bersih[] = $satu;
            }
        }

        return $bersih;
    }
}
