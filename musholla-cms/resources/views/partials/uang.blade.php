{{--
    Pemformat nominal rupiah untuk input teks (data-uang).

    Angka TIDAK dikunci ke kelipatan tertentu: nominal seperti 69.500, 69500, atau 69500,50
    semuanya boleh. JavaScript hanya menambahkan titik pemisah ribuan saat mengetik;
    sisi server menyaring ulang sehingga bentuk apa pun tetap tersimpan benar.
--}}
@once
    @push('skrip')
        <script>
        (function () {
            function rapikan(el) {
                var angka = el.value.replace(/\D/g, '');
                if (angka.length > 15) angka = angka.slice(0, 15);
                var rapi = angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                if (el.value === rapi) return;

                var kursor = el.selectionStart === null ? rapi.length : el.selectionStart;
                var digitDiDepan = el.value.slice(0, kursor).replace(/\D/g, '').length;
                el.value = rapi;

                var pos = 0, hitung = 0;
                while (pos < rapi.length && hitung < digitDiDepan) {
                    if (/\d/.test(rapi[pos])) hitung++;
                    pos++;
                }
                try { el.setSelectionRange(pos, pos); } catch (e) {}
            }

            document.querySelectorAll('[data-uang]').forEach(function (el) {
                el.addEventListener('input', function () { rapikan(el); });
                el.addEventListener('blur', function () { rapikan(el); });
                if (el.value) rapikan(el);
            });
        })();
        </script>
    @endpush
@endonce
