<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $fillable = [
        'santri_id',
        'ustadz_id',
        'tanggal',
        'jenis',
        'kategori',
        'detail_materi',
        'nilai_1',
        'nilai_2',
        'nilai_3',
        'ustadz_nama',
        'juz',
        'surah',
        'ayat',
        'iqro_jilid',
        'iqro_halaman',
        'nilai',
        'deskripsi',
        'catatan_ustadz',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function ustadz()
    {
        return $this->belongsTo(Ustadz::class, 'ustadz_id');
    }

    public function getRataAttribute(): ?float
    {
        $n = array_filter([$this->nilai_1, $this->nilai_2, $this->nilai_3], fn ($v) => $v !== null);
        return $n ? round(array_sum($n) / count($n), 1) : null;
    }
}
