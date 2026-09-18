<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaTemplate extends Model
{
    protected $table = 'wa_template';

    protected $fillable = [
        'judul',
        'isi',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}
