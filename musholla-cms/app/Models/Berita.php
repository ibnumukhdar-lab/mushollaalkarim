<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Berita extends Model
{
    protected $table = 'berita';

    protected $fillable = [
        'judul',
        'slug',
        'ringkasan',
        'isi',
        'kategori',
        'gambar_path',
        'terbit_at',
        'penulis_id',
    ];

    protected $casts = [
        'terbit_at' => 'datetime',
    ];

    public function penulis()
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    /** Kategori banyak (pola masfahri.online). */
    public function kategoriBanyak()
    {
        return $this->belongsToMany(Kategori::class, 'berita_kategori', 'berita_id', 'kategori_id');
    }

    public function scopeTerbit($q)
    {
        return $q->whereNotNull('terbit_at')->where('terbit_at', '<=', now());
    }

    /** Nama kategori utama: dari tabel kategori, jika kosong pakai kolom lama. */
    public function getKategoriUtamaAttribute(): ?string
    {
        $kategori = $this->relationLoaded('kategoriBanyak') ? $this->kategoriBanyak : $this->kategoriBanyak()->get();

        return $kategori->first()->nama ?? ($this->kategori ?: null);
    }

    /** Semua nama kategori sebagai daftar teks. */
    public function getKategoriDaftarAttribute(): array
    {
        $kategori = $this->relationLoaded('kategoriBanyak') ? $this->kategoriBanyak : $this->kategoriBanyak()->get();
        $nama = $kategori->pluck('nama')->all();

        if (! $nama && $this->kategori) {
            $nama = [$this->kategori];
        }

        return $nama;
    }

    /**
     * Tautan gambar sampul: hanya berkas yang benar-benar ada di disk publik
     * (atau URL penuh) yang dipakai. Selain itu kembalikan null supaya kartu
     * memakai penanda kosong, bukan gambar rusak — data lama dari impor WP
     * menyimpan nilai seperti "wp-uploads" yang bukan berkas gambar.
     */
    public function getGambarSampulAttribute(): ?string
    {
        $isi = $this->attributes['gambar_path'] ?? null;

        if (blank($isi)) {
            return null;
        }

        if (Str::startsWith($isi, ['http://', 'https://'])) {
            return $isi;
        }

        $isi = ltrim($isi, '/');

        if (! preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $isi)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($isi)
            ? url('/berkas/' . $isi)
            : null;
    }
}
