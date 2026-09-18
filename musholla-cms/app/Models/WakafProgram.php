<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WakafProgram extends Model
{
    protected $table = 'wakaf_program';

    protected $fillable = [
        'nama',
        'keterangan',
        'target',
        'terkumpul',
        'gambar_path',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'target' => 'decimal:2',
        'terkumpul' => 'decimal:2',
        'aktif' => 'boolean',
    ];
}
