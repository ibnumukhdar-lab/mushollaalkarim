<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'judul',
        'slug',
        'isi',
        'ringkasan',
        'tampil_di_menu',
        'urutan_menu',
        'meta_judul',
        'meta_deskripsi',
        'terbit_at',
    ];

    protected $casts = [
        'terbit_at' => 'datetime',
        'tampil_di_menu' => 'boolean',
    ];
}
