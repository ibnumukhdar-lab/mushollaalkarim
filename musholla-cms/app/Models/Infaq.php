<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Infaq extends Model
{
    protected $table = 'infaq';

    protected $fillable = [
        'nama_donatur', 'no_wa', 'nominal', 'tanggal', 'tujuan',
        'bukti_path', 'status', 'keterangan', 'diverifikasi_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /**
     * Tandai infaq terverifikasi dan catat otomatis ke Kas (uang masuk).
     * Dijalankan sekali saja — ditandai penanda [kas:#id] pada keterangan,
     * supaya tidak terjadi pencatatan ganda bila ditekan dua kali.
     */
    public function verifikasi(?int $userId = null): ?Kas
    {
        $this->status = 'terverifikasi';
        $this->diverifikasi_oleh = $userId ?: auth()->id();
        $this->save();

        if (str_contains((string) $this->keterangan, '[kas:#')) {
            return null;
        }

        $kas = Kas::query()->create([
            'tanggal' => $this->tanggal ?: now()->toDateString(),
            'jenis' => 'masuk',
            'kategori' => $this->tujuan ?: 'Infaq & sedekah',
            'jumlah' => $this->nominal,
            'keterangan' => 'Infaq dari '.$this->nama_donatur.($this->keterangan ? ' — '.$this->keterangan : ''),
            'dicatat_oleh' => $userId ?: auth()->id(),
        ]);

        $this->keterangan = trim(((string) $this->keterangan).' [kas:#'.$kas->id.']');
        $this->save();

        return $kas;
    }
}
