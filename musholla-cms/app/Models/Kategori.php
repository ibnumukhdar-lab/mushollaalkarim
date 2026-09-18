<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Kategori berita — pola sama dengan CMS masfahri.online.
 */
class Kategori extends Model
{
    protected $table = 'kategori';

    protected $fillable = ['nama', 'slug', 'urut'];

    protected static function booted(): void
    {
        static::saving(function (Kategori $k) {
            if (blank($k->slug)) {
                $slug = Str::slug((string) $k->nama) ?: 'kategori';
                $n = 2;
                while (static::query()->where('slug', $slug)->where('id', '!=', $k->id)->exists()) {
                    $slug = Str::slug((string) $k->nama) . '-' . $n++;
                }
                $k->slug = $slug;
            }
        });
    }

    public function berita()
    {
        return $this->belongsToMany(Berita::class, 'berita_kategori', 'kategori_id', 'berita_id');
    }

    /** Warna chip kategori (mengikuti pola masfahri.online). */
    public function getChipAttribute(): string
    {
        return match ($this->slug) {
            'kajian' => 'hijau',
            'kegiatan' => 'emas',
            'pengumuman' => 'biru',
            'program' => 'lembut',
            default => 'lembut',
        };
    }
}
