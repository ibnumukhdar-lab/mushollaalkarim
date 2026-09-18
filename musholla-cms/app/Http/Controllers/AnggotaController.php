<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Satu pintu keanggotaan Musholla Al Karim.
 *
 * - Pendaftaran publik (`/daftar`) langsung membuat akun berperan `subscriber`.
 * - Masuk (`/masuk`) mengarahkan subscriber ke `/anggota`, admin ke panel `/kelola`.
 * - Tidak ada verifikasi surel (pengiriman surel di hosting belum diaktifkan).
 */
class AnggotaController extends Controller
{
    /** Halaman yang tidak boleh tampil di menu. */
    private const BUKAN_MENU = [
        'home-page', 'home', 'laporan-kas', 'laporan-keuangan', 'user', 'login', 'register',
        'masuk', 'daftar', 'anggota', 'members', 'logout', 'keluar', 'account', 'password-reset',
        'privacy-policy',
    ];

    private function menu(): array
    {
        return \App\Support\Menu::utama();
    }

    private function pengaturan(): array
    {
        return Pengaturan::query()->pluck('nilai', 'kunci')->all();
    }

    /** Formulir pendaftaran subscriber. */
    public function formDaftar()
    {
        return view('publik.daftar', [
            'menu' => $this->menu(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }

    /** Simpan pendaftaran subscriber baru lalu langsung masuk. */
    public function daftar(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')],
            'no_wa' => ['nullable', 'string', 'max:25'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Alamat email tidak sah.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau pakai email lain.',
            'password.required' => 'Sandi wajib diisi.',
            'password.min' => 'Sandi minimal 8 huruf.',
            'password.confirmed' => 'Ulangan sandi belum sama.',
        ]);

        $user = new User();
        $user->name = $data['nama_lengkap'];
        $user->nama_lengkap = $data['nama_lengkap'];
        $user->email = Str::lower(trim($data['email']));
        $user->no_wa = isset($data['no_wa']) ? trim($data['no_wa']) : null;
        $user->password = Hash::make($data['password']);
        $user->peran = 'subscriber';
        $user->aktif = true;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('anggota')->with('baru_daftar', true);
    }

    /** Formulir masuk. */
    public function formMasuk()
    {
        return view('publik.masuk', [
            'menu' => $this->menu(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }

    /** Proses masuk; admin diarahkan ke panel, subscriber ke halaman anggota. */
    public function masuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Alamat email tidak sah.',
            'password.required' => 'Sandi wajib diisi.',
        ]);

        $berhasil = Auth::attempt([
            'email' => Str::lower(trim($data['email'])),
            'password' => $data['password'],
            'aktif' => true,
        ], $request->boolean('ingat'));

        if (! $berhasil) {
            return back()
                ->withErrors(['email' => 'Email atau sandi tidak cocok.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->punyaPeran('admin')) {
            return redirect('/kelola');
        }

        return redirect()->intended(route('anggota'));
    }

    /** Keluar. */
    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /** Halaman anggota (subscriber). */
    public function beranda()
    {
        return view('publik.anggota', [
            'anggota' => Auth::user(),
            'menu' => $this->menu(),
            'pengaturan' => $this->pengaturan(),
        ]);
    }
}
