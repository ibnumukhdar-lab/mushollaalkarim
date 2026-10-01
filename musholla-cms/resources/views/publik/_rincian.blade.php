{{--
    Tabel RINCIAN KEBUTUHAN sebuah program donasi (dipakai halaman /infaq/<slug> dan /wakaf/<slug>).

    Tiga kolom saja supaya rapi: Kebutuhan | Rincian | Subtotal.
    Kolom "Rincian" menuliskan jumlah, satuan, dan harga satuannya sekaligus
    (mis. "4 kali x Rp 125.000") sehingga angka tidak tampil dua kali.
    Subtotal & total dihitung aplikasi (jumlah x harga satuan) — tidak ada hitungan manual.
    Gaya tabelnya ada di blok <style> layouts/publik.blade.php (bukan @push di sini,
    sebab partial ini dirender setelah <head>).
--}}
@if ($program->rincian->isNotEmpty())
    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        // "Kebutuhan setiap bulan" -> "setiap bulan" supaya enak dibaca di dalam kalimat
        $periode = trim((string) $program->periode_label);
        $periodePendek = trim(preg_replace('~^kebutuhan\s+~i', '', $periode) ?? $periode);
    @endphp

    <div class="rincian-kepala">
        <h2 class="bagian" style="margin:0">Rincian kebutuhan</h2>
        @if ($periode !== '')
            <span class="rincian-periode">{{ $periode }}</span>
        @endif
    </div>

    <table class="rincian-tabel">
        <thead>
        <tr>
            <th scope="col">Kebutuhan</th>
            <th scope="col">Rincian</th>
            <th scope="col" class="kanan">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($program->rincian as $b)
            @php
                $bagian = array_filter([
                    $b->ringkas_jumlah,
                    (float) $b->harga_satuan > 0 ? $rp($b->harga_satuan) : null,
                ]);
                $keterangan = implode(' × ', $bagian) ?: '—';
            @endphp
            <tr>
                <th scope="row">
                    <span class="rincian-nama">{{ $b->nama }}</span>
                    @if (trim((string) $b->catatan) !== '')
                        <span class="rincian-catatan">{{ $b->catatan }}</span>
                    @endif
                    <span class="rincian-hp">{{ $keterangan }}</span>
                </th>
                <td class="rincian-rinci">{{ $keterangan }}</td>
                <td class="kanan rincian-sub">{{ $rp($b->subtotal) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="2" class="rincian-total-label">
                <span class="rincian-total-teks">Total kebutuhan</span>
                @if ($periodePendek !== '')
                    <span class="rincian-total-periode">{{ strtolower($periodePendek) }}</span>
                @endif
            </td>
            <td class="kanan rincian-total-angka">{{ $rp($program->total_rincian) }}</td>
        </tr>
        </tfoot>
    </table>
@endif
