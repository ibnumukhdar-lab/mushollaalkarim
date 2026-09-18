<?php
// SNIPPET WordPress #6: UPDATE JADWAL KAJIAN
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// =====================================================================
// MANAJEMEN JADWAL KAJIAN (ANTI-GAGAL & ZONA WIB) - MUSHOLLA AL KARIM
// =====================================================================

// 1. Load Script SweetAlert2 untuk Keperluan Pop-Up di Admin
if ( ! function_exists( 'load_custom_popup_admin' ) ) {
    function load_custom_popup_admin() {
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
        echo '<style>
            .swal2-popup { font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important; border-radius: 15px !important; }
            .swal2-confirm { border-radius: 50px !important; padding: 10px 25px !important; font-weight: bold !important; }
            .swal2-cancel { border-radius: 50px !important; padding: 10px 25px !important; font-weight: bold !important; }
        </style>';
    }
    add_action('admin_enqueue_scripts', 'load_custom_popup_admin');
}

// 2. Registrasi Menu Navigasi WP-Admin untuk Jadwal Kajian
function register_menu_kajian_sabtu() {
    add_menu_page(
        'Update Jadwal Kajian', 
        'Update Jadwal Kajian', 
        'update_kas_cap', 
        'manajemen-kajian', 
        'halaman_kajian_render', 
        'dashicons-welcome-learn-more', 
        7
    );
}
add_action('admin_menu', 'register_menu_kajian_sabtu');

// 3. Render Halaman Interface Form & Tabel Riwayat Kajian
function halaman_kajian_render() {
    ?>
    <div class="wrap">
        <h1>🗓️ Update Jadwal Kajian</h1>
        <p>Gunakan formulir ini untuk memperbarui informasi kajian mingguan dengan kalkulasi status otomatis berbasis WIB.</p>
        <hr>
        
        <style>
            .card-kajian { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 20px; border-top: 5px solid #2980b9; }
            .grid-kajian { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
            .waktu-range { display: flex; gap: 5px; align-items: center; }
            .waktu-range input { width: 47%; }
            .btn-simpan { padding: 12px 25px; background: #2980b9; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; }
            .btn-batal-kajian { padding: 12px 25px; background: #7f8c8d; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; display: none; }
            
            .table-kajian { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; margin-top: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            .table-kajian th, .table-kajian td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
            .table-kajian th { background: #f8f9fa; color: #555; font-weight: bold; }
            
            .badge { padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
            .badge-akan-datang { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; } 
            .badge-berlangsung { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; animation: pulse-glow 1.5s infinite; }
            .badge-terlaksana { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }

            @keyframes pulse-glow {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(243, 156, 18, 0.4); }
                70% { transform: scale(1.02); box-shadow: 0 0 0 8px rgba(243, 156, 18, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(243, 156, 18, 0); }
            }
            
            .btn-action { padding: 7px 12px; border-radius: 5px; cursor: pointer; border: none; color: white; font-size: 12px; font-weight: bold; }
            .btn-edit { background: #f39c12; margin-right: 5px; }
            .btn-delete { background: #e74c3c; }
        </style>

        <div class="card-kajian">
            <h3 style="margin-top: 0;">✍️ Form Jadwal Kajian</h3>
            <form id="form-kajian" class="grid-kajian">
                <input type="hidden" id="kajian-id">
                <div class="form-group"><label>Judul Kajian</label><input type="text" id="judul" placeholder="Contoh: Kajian Kitab Hikam" required></div>
                <div class="form-group"><label>Tema</label><input type="text" id="tema" placeholder="Contoh: Adab Berdoa" required></div>
                <div class="form-group"><label>Narasumber</label><input type="text" id="narasumber" placeholder="Nama Ustadz" required></div>
                <div class="form-group"><label>Hari / Tgl</label><input type="date" id="tgl_kajian" required></div>
                <div class="form-group">
                    <label>Waktu (Mulai - Selesai)</label>
                    <div class="waktu-range">
                        <input type="time" id="jam_mulai" required>
                        <span>-</span>
                        <input type="time" id="jam_selesai" required>
                    </div>
                </div>
                <div class="form-group"><label>Link Foto (URL)</label><input type="text" id="foto" placeholder="Link foto dari Media WordPress"></div>
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <button type="submit" id="btn-simpan-kajian" class="btn-simpan">Simpan Jadwal</button>
                    <button type="button" id="btn-batal-kajian" class="btn-batal-kajian" onclick="batalEditKajian()">Batal</button>
                </div>
            </form>
        </div>

        <h3 style="margin-top: 30px;">Daftar Riwayat & Jadwal Kajian</h3>
        <div style="overflow-x: auto;">
            <table class="table-kajian">
                <thead>
                    <tr>
                        <th>Info Kajian</th>
                        <th>Waktu & Tanggal</th>
                        <th>Status real-time (WIB)</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="list-kajian-admin">
                    <tr><td colspan="4" style="text-align:center; padding: 20px;">⏳ Memuat aman jadwal kajian dari database...</td></tr>
                </tbody>
            </table>
        </div>

        <script>
        const urlScriptKajian = 'https://script.google.com/macros/s/AKfycbzIDUg1PQd_Naiby7-uAzVKrTCiVZMOIqmXSz1aLyHHZZKWw_acsMk5zqrICg3yw2G1vw/exec'; 
        let rawDataKajian = [];

        // PERBAIKAN: Fungsi penarikan WIB yang lebih stabil di semua browser
        function dapatkanWaktuWIB() {
            const strWIB = new Date().toLocaleString("en-US", {timeZone: "Asia/Jakarta", hour12: false});
            return new Date(strWIB);
        }

        async function loadKajian() {
            const containerTabel = document.getElementById('list-kajian-admin');
            try {
                const res = await fetch(`${urlScriptKajian}?target=kajian&nocache=${Math.random()}`);
                
                const textData = await res.text();
                if (textData.trim().startsWith('<!DOCTYPE') || textData.trim() === "") {
                    throw new Error("Akses ditolak oleh Google. Pastikan Web App diset 'Anyone'.");
                }
                
                rawDataKajian = JSON.parse(textData);
                
                if(!Array.isArray(rawDataKajian)) {
                    rawDataKajian = [];
                }

                let html = '';
                const sekarangWIB = dapatkanWaktuWIB();

                rawDataKajian.forEach(item => {
                    let textStatus = 'Akan Datang';
                    let classBadge = 'badge-akan-datang';

                    if(!item.tanggal) return;
                    const stringTgl = new Date(item.tanggal).toISOString().split('T')[0];
                    let jamMulaiStr = "00:00";
                    let jamSelesaiStr = "23:59";

                    // PERBAIKAN: Regex diperbaiki dari /wib/ii menjadi /wib/i (mengatasi error fatal)
                    const bersihkanWaktu = (item.waktu || "").replace(/wib/i, '').trim();
                    const pisahJam = bersihkanWaktu.split('-');
                    
                    if (pisahJam.length >= 1 && pisahJam[0].trim().match(/^\d{2}:\d{2}$/)) {
                        jamMulaiStr = pisahJam[0].trim();
                    }
                    if (pisahJam.length >= 2 && pisahJam[1].trim().match(/^\d{2}:\d{2}$/)) {
                        jamSelesaiStr = pisahJam[1].trim();
                    } else if (pisahJam.length >= 1 && pisahJam[0].trim().match(/^\d{2}:\d{2}$/)) {
                        let [h, m] = jamMulaiStr.split(':').map(Number);
                        let hEnd = (h + 2) % 24;
                        jamSelesaiStr = String(hEnd).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                    }

                    const waktuMulaiKajian = new Date(`${stringTgl}T${jamMulaiStr}:00`);
                    const waktuSelesaiKajian = new Date(`${stringTgl}T${jamSelesaiStr}:00`);

                    if (sekarangWIB < waktuMulaiKajian) {
                        textStatus = 'Akan Datang';
                        classBadge = 'badge-akan-datang';
                    } else if (sekarangWIB >= waktuMulaiKajian && sekarangWIB <= waktuSelesaiKajian) {
                        textStatus = 'Sedang Berlangsung';
                        classBadge = 'badge-berlangsung';
                    } else {
                        textStatus = 'Sudah Terlaksana';
                        classBadge = 'badge-terlaksana';
                    }

                    const tglFormat = new Date(item.tanggal);
                    html += `
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:15px;">
                                <img src="${item.foto || 'https://via.placeholder.com/150'}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                                <div>
                                    <strong style="display:block; font-size: 14px; color:#2c3e50;">${item.judul}</strong>
                                    <small style="color:#7f8c8d; font-weight: 500;">Tema: ${item.tema}</small><br>
                                    <small style="color:#2980b9; font-weight: bold;">👤 ${item.narasumber}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="display:block; font-weight:600; color:#333;">${tglFormat.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
                            <small style="color:#7f8c8d; font-weight:600;">🕒 ${item.waktu}</small>
                        </td>
                        <td>
                            <span class="badge ${classBadge}">${textStatus}</span>
                        </td>
                        <td style="text-align: center; min-width: 140px;">
                            <button onclick="siapkanEditKajian(${item.id})" class="btn-action btn-edit">Edit</button>
                            <button onclick="hapusKajian(${item.id})" class="btn-action btn-delete">Hapus</button>
                        </td>
                    </tr>`;
                });
                
                containerTabel.innerHTML = html || '<tr><td colspan="4" style="text-align:center; padding:20px;">Belum ada jadwal kajian tersimpan.</td></tr>';
            } catch (err) {
                console.error("Error Load Kajian:", err);
                containerTabel.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#c0392b; font-weight:bold; padding:20px;">⚠️ Gagal Memuat Data.<br><small style="color:#7f8c8d; font-weight:normal;">Pesan Error: ${err.message}</small></td></tr>`;
            }
        }

        document.getElementById('form-kajian').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-simpan-kajian');
            btn.innerText = "⏳ Memproses...";
            btn.disabled = true;
            
            const id = document.getElementById('kajian-id').value;
            const isUpdate = id !== "";

            const jMulai = document.getElementById('jam_mulai').value;
            const jSelesai = document.getElementById('jam_selesai').value;
            const gabungWaktu = `${jMulai} - ${jSelesai} WIB`;

            const payload = {
                action: isUpdate ? "update" : "insert",
                type: "kajian",
                id: parseInt(id) || 0,
                judul: document.getElementById('judul').value,
                tema: document.getElementById('tema').value,
                narasumber: document.getElementById('narasumber').value,
                tanggal: document.getElementById('tgl_kajian').value,
                waktu: gabungWaktu,
                foto: document.getElementById('foto').value,
                status: "Aktif"
            };

            try {
                const formBody = [];
                for (const property in payload) {
                    formBody.push(encodeURIComponent(property) + "=" + encodeURIComponent(payload[property]));
                }

                await fetch(urlScriptKajian, { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    mode: 'no-cors',
                    body: formBody.join("&")
                });
                
                Swal.fire({
                    title: 'Alhamdulillah!',
                    text: isUpdate ? 'Jadwal kajian berhasil diperbarui.' : 'Jadwal kajian berhasil dipublikasikan.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } catch (err) {
                Swal.fire('Error!', 'Gagal menyimpan perubahan jadwal.', 'error');
                btn.innerText = "Simpan Jadwal"; btn.disabled = false;
            }
        }

        function siapkanEditKajian(id) {
            const item = rawDataKajian.find(x => x.id === id);
            if(!item) return;

            document.getElementById('kajian-id').value = item.id;
            document.getElementById('judul').value = item.judul;
            document.getElementById('tema').value = item.tema;
            document.getElementById('narasumber').value = item.narasumber;
            document.getElementById('tgl_kajian').value = new Date(item.tanggal).toISOString().split('T')[0];
            document.getElementById('foto').value = item.foto && item.foto !== 'undefined' ? item.foto : '';
            
            // PERBAIKAN: Regex diperbaiki dari /wib/ii menjadi /wib/i
            const bersihkanWaktu = (item.waktu || "").replace(/wib/i, '').trim();
            const pisahJam = bersihkanWaktu.split('-');
            if (pisahJam.length >= 1 && pisahJam[0].trim().match(/^\d{2}:\d{2}$/)) {
                document.getElementById('jam_mulai').value = pisahJam[0].trim();
            }
            if (pisahJam.length >= 2 && pisahJam[1].trim().match(/^\d{2}:\d{2}$/)) {
                document.getElementById('jam_selesai').value = pisahJam[1].trim();
            }

            document.getElementById('btn-simpan-kajian').innerText = "Update Jadwal";
            document.getElementById('btn-batal-kajian').style.display = "block";
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function batalEditKajian() {
            document.getElementById('form-kajian').reset();
            document.getElementById('kajian-id').value = "";
            document.getElementById('btn-simpan-kajian').innerText = "Simpan Jadwal";
            document.getElementById('btn-batal-kajian').style.display = "none";
        }

        function hapusKajian(id) {
            Swal.fire({
                title: 'Hapus Jadwal Kajian?',
                text: "Jadwal ini akan dihapus dari riwayat Musholla.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#7f8c8d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({title: 'Memproses...', allowOutsideClick: false, showConfirmButton: false});
                    Swal.showLoading();
                    
                    try {
                        await fetch(urlScriptKajian, { 
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                            mode: 'no-cors', 
                            body: `action=delete&type=kajian&id=${id}` 
                        });
                        
                        Swal.fire({
                            title: 'Terhapus!',
                            text: 'Jadwal kajian berhasil dihapus.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } catch(e) {
                        Swal.fire('Gagal!', 'Koneksi gagal saat menghapus.', 'error');
                    }
                }
            });
        }

        loadKajian();
        </script>
    </div>
    <?php
}