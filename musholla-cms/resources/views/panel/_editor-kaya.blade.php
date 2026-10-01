
{{-- Isian teks panjang dengan editor kaya (TinyMCE).
     Isi dikirim ke peladen dalam bentuk base64 (diberi awalan "b64:") supaya
     tidak ditolak WAF hosting, lalu diurai kembali oleh PanelController. --}}
<textarea id="f-{{ $nama }}" name="{{ $nama }}" rows="{{ $baris ?? 14 }}" data-kaya="1" class="editor-kaya">{{ $nilai }}</textarea>
<span class="bantuan">
    Editor lengkap: gaya dan ukuran huruf, tebal/miring, judul, daftar bernomor, kutipan,
    tautan, gambar (lewat alamat), dan tabel. Tombol <b>&lt;/&gt;</b> (Code) untuk melihat
    atau menulis HTML langsung — sama seperti di masfahri.online.
</span>
