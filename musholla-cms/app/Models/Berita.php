<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
