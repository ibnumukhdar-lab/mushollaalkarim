{{--
    Pratinjau berkas dalam popup — markup + skrip.

    CATATAN PENTING: CSS-nya tinggal di panel/layout.blade.php (blok <style> utama), sebab partial
    ini di-include DARI layout SETELAH <head> selesai dirender — @push('gaya') di sini tidak akan
    pernah tercetak karena @stack('gaya') sudah dikeluarkan lebih dulu. Skrip tetap aman di sini
    karena @stack('skrip') berada SETELAH include ini.

    Cara pakai: beri atribut data-pratinjau pada tautan/gambar yang bisa ditekan.
        <a href="/berkas/bukti.jpg" data-pratinjau="/berkas/bukti.jpg" data-pratinjau-nama="bukti.jpg" target="_blank">…</a>
    Tanpa JavaScript tautannya tetap jalan seperti biasa (terbuka di tab baru).
--}}
@push('skrip')
    <script>
    (function () {
        var selubung = document.querySelector('.pratinjau-selubung');
        if (!selubung) return;

        var judul = selubung.querySelector('[data-pratinjau-judul]');
        var isi = selubung.querySelector('[data-pratinjau-isi]');
        var tautan = selubung.querySelector('[data-pratinjau-tautan]');
        var unduh = selubung.querySelector('[data-pratinjau-unduh]');
        var tombolTutup = selubung.querySelector('[data-pratinjau-tutup]');
        var terakhirFokus = null;

        function namaDari(url) {
            var bersih = String(url).split('?')[0].split('/').pop();
            return decodeURIComponent(bersih || 'berkas');
        }

        function buka(url, nama) {
            var pdf = /\.pdf$/i.test(url);
            isi.innerHTML = '';

            if (pdf) {
                var bingkai = document.createElement('iframe');
                bingkai.src = url;
                bingkai.setAttribute('title', nama || 'Dokumen');
                isi.appendChild(bingkai);
            } else {
                var gambar = document.createElement('img');
                gambar.src = url;
                gambar.alt = nama || 'Bukti';
                isi.appendChild(gambar);
            }

            judul.textContent = nama || namaDari(url);
            tautan.setAttribute('href', url);
            unduh.setAttribute('href', url);
            unduh.setAttribute('download', nama || namaDari(url));
            terakhirFokus = document.activeElement;
            document.body.classList.add('pratinjau-terbuka');
            if (tombolTutup) tombolTutup.focus();
        }

        function tutup() {
            document.body.classList.remove('pratinjau-terbuka');
            isi.innerHTML = '';   // hentikan pemuatan gambar / dokumen
            if (terakhirFokus && terakhirFokus.focus) terakhirFokus.focus();
        }

        document.addEventListener('click', function (e) {
            var pemicu = e.target.closest('[data-pratinjau]');
            if (pemicu) {
                e.preventDefault();
                buka(pemicu.getAttribute('data-pratinjau'), pemicu.getAttribute('data-pratinjau-nama'));
                return;
            }
            if (e.target.closest('[data-pratinjau-tutup]') || e.target === selubung) {
                tutup();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('pratinjau-terbuka')) tutup();
        });
    })();
    </script>
@endpush

<div class="pratinjau-selubung" role="dialog" aria-modal="true" aria-label="Pratinjau berkas">
    <div class="pratinjau-kotak">
        <div class="pratinjau-kepala">
            <span class="pratinjau-judul" data-pratinjau-judul>Pratinjau berkas</span>
            <a class="pratinjau-tombol" data-pratinjau-tautan href="#" target="_blank" rel="noopener" title="Buka di tab baru">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 5h5v5"/><path d="M19 5l-6.5 6.5"/><path d="M19 14v5H5V5h5"/></svg>
                Tab baru
            </a>
            <a class="pratinjau-tombol" data-pratinjau-unduh href="#" title="Unduh berkas">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="m7.5 11 4.5 4 4.5-4"/><path d="M5 19h14"/></svg>
                Unduh
            </a>
            <button type="button" class="pratinjau-tombol tutup" data-pratinjau-tutup aria-label="Tutup pratinjau">✕</button>
        </div>
        <div class="pratinjau-isi" data-pratinjau-isi></div>
        <div class="pratinjau-ket">Tekan Esc atau klik di luar gambar untuk menutup.</div>
    </div>
</div>
