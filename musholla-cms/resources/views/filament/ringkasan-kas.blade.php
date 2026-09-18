<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Ringkasan Kas</x-slot>
        <x-slot name="description">
            Dihitung langsung dari catatan kas aplikasi — {{ number_format($jumlahCatatan, 0, ',', '.') }} catatan.
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl p-4" style="background:linear-gradient(135deg,#1f7a4d,#2f9e68);color:#fff">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.9">Pemasukan</div>
                <div style="font-size:1.3rem;font-weight:700">Rp {{ number_format($masuk, 0, ',', '.') }}</div>
                <div style="font-size:.72rem;opacity:.9;margin-top:.2rem">
                    bulan ini: Rp {{ number_format($masukBulanIni, 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl p-4" style="background:linear-gradient(135deg,#a4373f,#c0505a);color:#fff">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.9">Pengeluaran</div>
                <div style="font-size:1.3rem;font-weight:700">Rp {{ number_format($keluar, 0, ',', '.') }}</div>
                <div style="font-size:.72rem;opacity:.9;margin-top:.2rem">
                    bulan ini: Rp {{ number_format($keluarBulanIni, 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl p-4" style="background:linear-gradient(160deg,#1f3a5f,#2c4f7c);color:#fff">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.9">Saldo akhir</div>
                <div style="font-size:1.3rem;font-weight:700">Rp {{ number_format($saldo, 0, ',', '.') }}</div>
                <div style="font-size:.72rem;opacity:.9;margin-top:.2rem">
                    tampil juga di halaman publik /laporan-kas
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
