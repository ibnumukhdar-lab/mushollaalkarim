<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris rincian kebutuhan sebuah program donasi
 * (mis. "Tagihan listrik — 1 bulan × Rp 100.000").
 *
 * Subtotal dihitung aplikasi (jumlah × harga satuan) supaya angka pada tabel
 * selalu cocok dengan totalnya — pengurus tidak perlu mengalikan manual.
 */
class ProgramRincian extends Model
{
    protected $table = 'program_rincian';

    protected $fillable = [
        'wakaf_program_id',
        'nama',
        'jumlah',
        'satuan',
        'harga_satuan',
        'catatan',
        'urutan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'urutan' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(WakafProgram::class, 'wakaf_program_id');
    }

    /** Jumlah × harga satuan. */
    public function getSubtotalAttribute(): float
    {
        return round((float) $this->jumlah * (float) $this->harga_satuan, 2);
    }

    /** "2 kali" / "1 bulan" / "3 unit" — null bila jumlah & satuan tidak diisi. */
    public function getRingkasJumlahAttribute(): ?string
    {
        $jumlah = (float) ($this->attributes['jumlah'] ?? 0);
        $satuan = trim((string) ($this->attributes['satuan'] ?? ''));

        if ($jumlah <= 0) {
            return null;
        }

        $angka = fmod($jumlah, 1.0) === 0.0 ? number_format($jumlah, 0, ',', '.') : number_format($jumlah, 2, ',', '.');

        return $satuan !== '' ? $angka.' '.$satuan : $angka;
    }
}
