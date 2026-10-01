{{--
    Tabel RINCIAN KEBUTUHAN sebuah program donasi (dipakai halaman /infaq/<slug> dan /wakaf/<slug>).

    Subtotal tiap baris & total dihitung aplikasi (jumlah × harga satuan) sehingga
    angka pada tabel selalu cocok dengan totalnya — tidak ada hitungan manual.
    Gaya tabelnya ada di blok <style> layouts/publik.blade.php (bukan @push di sini,
    sebab partial ini dirender setelah <head>).
--}}
@if ($program->rincian->isNotEmpty())
    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        // "Kebutuhan setiap bulan" → "setiap bulan" supaya enak dibaca di dalam kalimat
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
        <caption>
            Kebutuhan {{ $program->nama }} — total
            {{ $rp($program->total_rincian) }}
            @if ($periodePendek !== '') {{ strtolower($periodePendek) }} @endif
        </caption>
        <thead>
        <tr>
            <th scope="col">Kebutuhan</th>
            <th scope="col" class="kanan">Jumlah</th>
            <th scope="col" class="kanan">Harga satuan</th>
            <th scope="col" class="kanan">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($program->rincian as $b)
            <tr>
                <th scope="row">
                    {{ $b->nama }}
                    @if (trim((string) $b->catatan) !== '')
                        <span class="rincian-catatan">{{ $b->catatan }}</span>
                    @endif
                </th>
                <td class="kanan">{{ $b->ringkas_jumlah ?? '—' }}</td>
                <td class="kanan">{{ (float) $b->harga_satuan > 0 ? $rp($b->harga_satuan) : '—' }}</td>
                <td class="kanan">{{ $rp($b->subtotal) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3">Total kebutuhan{{ $periodePendek !== '' ? ' — '.strtolower($periodePendek) : '' }}</td>
            <td class="kanan">{{ $rp($program->total_rincian) }}</td>
        </tr>
        </tfoot>
    </table>
@endif
