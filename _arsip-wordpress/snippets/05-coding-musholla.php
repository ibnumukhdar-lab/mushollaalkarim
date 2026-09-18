<?php
// SNIPPET WordPress #5: Coding Musholla
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------


// =====================================================================
// --- KODE KUSTOM MAS FAHRI DIMULAI DI SINI ---
// =====================================================================

// [BARU] Memuat Script untuk Pop-Up Animasi (SweetAlert2) di Halaman Admin
function load_custom_popup_admin() {
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
    echo '<style>
        .swal2-popup { font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important; border-radius: 15px !important; }
        .swal2-confirm { border-radius: 50px !important; padding: 10px 25px !important; font-weight: bold !important; }
        .swal2-cancel { border-radius: 50px !important; padding: 10px 25px !important; font-weight: bold !important; }
    </style>';
}
add_action('admin_enqueue_scripts', 'load_custom_popup_admin');


// 1. Tambahkan Role Pengurus Musholla (DIPERBAIKI SECARA PAKSA KE DATABASE)
function tambah_role_pengurus_musholla() {
    $pengurus_role = get_role('pengurus_musholla');
    
    // Jika belum ada, buat baru
    if ( ! $pengurus_role ) {
        add_role('pengurus_musholla', 'Pengurus Musholla', array('read' => true));
        $pengurus_role = get_role('pengurus_musholla');
    }
    
    // Paksa update kapabilitas ke dalam Database
    if ( $pengurus_role ) {
        $pengurus_role->add_cap('update_kas_cap');
        $pengurus_role->add_cap('edit_posts');              // Mengakses Menu Update Berita / Post biasa
        $pengurus_role->add_cap('publish_posts');           // Tombol Publish
        $pengurus_role->add_cap('upload_files');            // Akses Media Library / Thumbnail
        $pengurus_role->add_cap('edit_published_posts');    // Edit yang sudah tayang
        $pengurus_role->add_cap('delete_posts');            // Hapus draf
        $pengurus_role->add_cap('delete_published_posts');  // Hapus tayang
        
        // --- PERBAIKAN: Izinkan edit/hapus postingan (Wakaf/Berita) buatan Admin ---
        $pengurus_role->add_cap('edit_others_posts');
        $pengurus_role->add_cap('delete_others_posts');
    }

    $admin = get_role('administrator');
    if ($admin) { $admin->add_cap('update_kas_cap'); }
}
add_action('init', 'tambah_role_pengurus_musholla');


// =====================================================================
// MENU 1: UPDATE KAS MUSHOLLA
// =====================================================================
function register_menu_update_kas() {
    add_menu_page('Update Kas Musholla', 'Update Kas', 'update_kas_cap', 'update-kas-musholla', 'halaman_update_kas_render', 'dashicons-money-alt', 6);
}
add_action('admin_menu', 'register_menu_update_kas');

function halaman_update_kas_render() {
    ?>
    <div class="wrap">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="font-weight: 700; color: #23282d;">Manajemen Kas Musholla</h1>
            <button onclick="loadData()" class="button button-secondary" style="border-radius: 6px; padding: 5px 15px;">🔄 Refresh Data</button>
        </div>
        <hr>
        
        <style>
            #dashboard-pengurus { font-family: 'Segoe UI', Tahoma, sans-serif; color: #333; max-width: 1100px; margin: 20px 0; }
            .saldo-container { background: linear-gradient(135deg, #1d976c 0%, #93f9b9 100%); color: #fff; padding: 40px 20px; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px rgba(29, 151, 108, 0.3); margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.2); }
            .saldo-label { font-size: 14px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.9; font-weight: 600; margin-bottom: 10px; display: block; }
            .saldo-amount { font-size: 48px; font-weight: 800; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); }
            .card-input { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; border-left: 6px solid #1d976c; }
            .grid-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
            .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 13px; color: #555; }
            .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; transition: 0.3s; }
            .btn-save { width: 100%; padding: 14px; background: #1d976c; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s; }
            .btn-save:hover { background: #167d58; transform: translateY(-2px); }
            
            .filter-section { background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
            .filter-section label { font-weight: 600; font-size: 13px; }
            .filter-section select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; background: #f9f9f9; }

            .wp-list-table-custom { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            .wp-list-table-custom thead { background: #f8f9fa; }
            .wp-list-table-custom th, .wp-list-table-custom td { padding: 18px 15px; text-align: left; border-bottom: 1px solid #f1f1f1; }
            .btn-hapus { background: #ff4757; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
            .btn-edit { background: #ffa502; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; margin-right: 5px; }
        </style>

        <div id="dashboard-pengurus">
            <div class="saldo-container">
                <span class="saldo-label">Total Saldo Kas Musholla</span>
                <h2 id="total-saldo" class="saldo-amount">Rp 0</h2>
            </div>

            <div class="card-input">
                <h3 style="margin-top: 0; color: #2c3e50; font-size: 18px;">✍️ Input Transaksi Baru</h3>
                <form id="form-kas" class="grid-form">
                    <input type="hidden" id="edit-id">
                    <div class="form-group"><label>Tanggal</label><input type="date" id="tgl" required></div>
                    <div class="form-group"><label>Keterangan</label><input type="text" id="ket" placeholder="Misal: Infaq Ahad" required></div>
                    <div class="form-group"><label>Masuk (Rp)</label><input type="number" id="masuk" value="0"></div>
                    <div class="form-group"><label>Keluar (Rp)</label><input type="number" id="keluar" value="0"></div>
                    <div style="display: flex; align-items: flex-end; gap: 10px;">
                        <button type="submit" id="btn-submit" class="btn-save">Simpan Transaksi</button>
                        <button type="button" id="btn-batal" style="display:none; padding:14px; background:#747d8c; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">Batal</button>
                    </div>
                </form>
            </div>

            <div class="filter-section">
                <div>
                    <label>📅 Bulan:</label>
                    <select id="filter-bulan" onchange="applyFilters()">
                        <option value="all">Semua Bulan</option>
                        <option value="0">Januari</option><option value="1">Februari</option><option value="2">Maret</option>
                        <option value="3">April</option><option value="4">Mei</option><option value="5">Juni</option>
                        <option value="6">Juli</option><option value="7">Agustus</option><option value="8">September</option>
                        <option value="9">Oktober</option><option value="10">November</option><option value="11">Desember</option>
                    </select>
                </div>
                <div>
                    <label>💰 Tipe:</label>
                    <select id="filter-tipe" onchange="applyFilters()">
                        <option value="all">Semua Transaksi</option>
                        <option value="masuk">Uang Masuk</option>
                        <option value="keluar">Uang Keluar</option>
                    </select>
                </div>
                <div style="margin-left: auto; font-size: 12px; color: #777;" id="row-count">Menghitung data...</div>
            </div>

            <div style="overflow-x: auto;">
                <table class="wp-list-table-custom">
                    <thead>
                        <tr>
                            <th>Tgl</th><th>Keterangan</th>
                            <th style="text-align: right;">Masuk</th>
                            <th style="text-align: right;">Keluar</th>
                            <th style="text-align: right;">Saldo</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="data-body">
                        <tr><td colspan="6" style="text-align:center; padding: 40px;">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        const scriptUrl = 'https://script.google.com/macros/s/AKfycbzIDUg1PQd_Naiby7-uAzVKrTCiVZMOIqmXSz1aLyHHZZKWw_acsMk5zqrICg3yw2G1vw/exec';
        let rawData = [];

        async function loadData() {
            document.getElementById('data-body').innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 40px;">Memperbarui data...</td></tr>';
            try {
                const res = await fetch(scriptUrl);
                rawData = await res.json();
                applyFilters();
            } catch(e) { 
                document.getElementById('data-body').innerHTML = '<tr><td colspan="6" style="text-align:center; color:red; padding: 40px;">⚠️ Gagal memuat data.</td></tr>'; 
            }
        }

        function applyFilters() {
            const bulan = document.getElementById('filter-bulan').value;
            const tipe = document.getElementById('filter-tipe').value;
            
            let filtered = rawData.filter(item => {
                const itemDate = new Date(item.tanggal);
                const matchBulan = (bulan === 'all' || itemDate.getMonth() == bulan);
                let matchTipe = true;
                if (tipe === 'masuk') matchTipe = item.masuk > 0;
                if (tipe === 'keluar') matchTipe = item.keluar > 0;
                return matchBulan && matchTipe;
            });

            renderTable(filtered);
        }

        function renderTable(data) {
            let html = ''; let runningSaldo = 0; let totalSaldoAll = 0;
            
            rawData.forEach(item => totalSaldoAll += (item.masuk - item.keluar));
            
            data.forEach(item => {
                const tglIndo = new Date(item.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'2-digit', year:'numeric'});
                html += `<tr>
                    <td>${tglIndo}</td>
                    <td style="font-weight:500;">${item.keterangan}</td>
                    <td style="color:#1d976c; font-weight:600; text-align:right;">${item.masuk.toLocaleString('id-ID')}</td>
                    <td style="color:#ff4757; font-weight:600; text-align:right;">${item.keluar.toLocaleString('id-ID')}</td>
                    <td style="text-align:right;">${(item.masuk - item.keluar).toLocaleString('id-ID')}</td>
                    <td style="text-align:center; min-width: 140px;">
                        <button class="btn-edit" onclick="siapkanEdit(${item.id})">Edit</button>
                        <button class="btn-hapus" onclick="hapusData(${item.id})">Hapus</button>
                    </td>
                </tr>`;
            });

            document.getElementById('data-body').innerHTML = html || '<tr><td colspan="6" style="text-align:center; padding: 40px;">Data tidak ditemukan.</td></tr>';
            document.getElementById('total-saldo').innerText = 'Rp ' + totalSaldoAll.toLocaleString('id-ID');
            document.getElementById('row-count').innerText = `Menampilkan ${data.length} transaksi`;
        }

        document.getElementById('form-kas').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit');
            btn.innerText = "⏳ Memproses..."; btn.disabled = true;
            
            const isUpdate = document.getElementById('edit-id').value !== "";
            const payload = {
                action: isUpdate ? "update" : "insert",
                id: parseInt(document.getElementById('edit-id').value) || 0,
                tanggal: document.getElementById('tgl').value,
                keterangan: document.getElementById('ket').value,
                masuk: parseInt(document.getElementById('masuk').value) || 0,
                keluar: parseInt(document.getElementById('keluar').value) || 0
            };
            
            try {
                await fetch(scriptUrl, { method: 'POST', body: JSON.stringify(payload) });
                Swal.fire({
                    title: 'Berhasil!',
                    text: isUpdate ? 'Transaksi berhasil diperbarui.' : 'Transaksi berhasil ditambahkan.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } catch(e) {
                Swal.fire('Error!', 'Terjadi kesalahan saat menyimpan.', 'error');
                btn.innerText = "Simpan Transaksi"; btn.disabled = false;
            }
        };

        function hapusData(id) {
            Swal.fire({
                title: 'Hapus data ini, Mas Fahri?',
                text: "Data kas yang dihapus akan memengaruhi total saldo!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4757',
                cancelButtonColor: '#7f8c8d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({title: 'Memproses...', allowOutsideClick: false, showConfirmButton: false});
                    Swal.showLoading();
                    
                    await fetch(scriptUrl, { method: 'POST', body: JSON.stringify({ action: 'delete', id: id }) });
                    loadData();
                    
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Data kas berhasil dihapus.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }

        function siapkanEdit(id) {
            const item = rawData.find(x => x.id === id);
            if(!item) return;
            document.getElementById('edit-id').value = item.id;
            document.getElementById('tgl').value = new Date(item.tanggal).toISOString().split('T')[0];
            document.getElementById('ket').value = item.keterangan;
            document.getElementById('masuk').value = item.masuk;
            document.getElementById('keluar').value = item.keluar;
            document.getElementById('btn-submit').innerText = "Update Transaksi";
            document.getElementById('btn-batal').style.display = "block";
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.getElementById('btn-batal').onclick = () => { 
            document.getElementById('form-kas').reset();
            document.getElementById('edit-id').value = "";
            document.getElementById('btn-submit').innerText = "Simpan Transaksi";
            document.getElementById('btn-batal').style.display = "none";
        };
        loadData();
        </script>
    </div>
    <?php
}


// =====================================================================
// MENU 2: SHORTCODE TAB MENU NAVIGASI MUSHOLLA (VERSI PINTAR)
// =====================================================================
function shortcode_menu_tab_musholla($atts) {
    $args = shortcode_atts( array(
        'nama' => '', 
    ), $atts );

    $menu_args = array(
        'container'      => 'nav',
        'container_class'=> 'nav-tab-musholla',
        'menu_class'     => 'tab-menu-list',
        'fallback_cb'    => '__return_false',
        'echo'           => false
    );

    if ( !empty($args['nama']) ) {
        $menu_args['menu'] = $args['nama'];
    } else {
        $menu_args['theme_location'] = 'menu-1';
    }

    $menu_html = wp_nav_menu( $menu_args );
    
    if ( ! $menu_html ) {
        $menus = wp_get_nav_menus();
        if ( !empty($menus) ) {
            $menu_args['menu'] = $menus[0]->term_id;
            unset($menu_args['theme_location']);
            $menu_html = wp_nav_menu( $menu_args );
        }
    }

    if ( ! $menu_html ) {
        return '<p style="text-align:center; color:#e11d48; font-size:14px; background:#ffe4e6; padding:10px; border-radius:8px;">⚠️ Gagal memuat. Pastikan Mas Fahri sudah membuat setidaknya satu menu di <b>Tampilan > Menu</b>.</p>';
    }

    return $menu_html;
}
add_shortcode('menu_tab_musholla', 'shortcode_menu_tab_musholla');

// =====================================================================
// MENU 3: PENGATURAN TAMPILAN DASHBOARD KHUSUS PENGURUS
// =====================================================================

function sembunyikan_menu_untuk_pengurus() {
    if ( ! current_user_can( 'manage_options' ) ) {
        remove_menu_page( 'index.php' );                  
        remove_menu_page( 'edit.php' );                    
        remove_menu_page( 'upload.php' );                 
        remove_menu_page( 'edit.php?post_type=page' );    
        remove_menu_page( 'edit-comments.php' );          
        remove_menu_page( 'themes.php' );                 
        remove_menu_page( 'plugins.php' );                
        remove_menu_page( 'users.php' );                  
        remove_menu_page( 'tools.php' );                  
        remove_menu_page( 'options-general.php' );        

        remove_menu_page( 'ultimatemember' );
        remove_menu_page( 'elementor' );
        remove_menu_page( 'edit.php?post_type=elementor_library' );
    }
}
add_action( 'admin_menu', 'sembunyikan_menu_untuk_pengurus', 999 );

function redirect_pengurus_setelah_login( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'pengurus_musholla', $user->roles ) ) {
            return admin_url( 'admin.php?page=update-kas-musholla' );
        }
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'redirect_pengurus_setelah_login', 10, 3 );

function sembunyikan_admin_bar_pengurus() {
    if ( ! current_user_can( 'manage_options' ) ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'sembunyikan_admin_bar_pengurus' );

// =====================================================================
// MENU 4: TAMBAH MENU DASHBOARD OTOMATIS SAAT LOGIN
// =====================================================================
function menu_dinamis_pengurus( $items, $args ) {
    if ( is_user_logged_in() ) {
        $url_dashboard = admin_url( 'admin.php?page=update-kas-musholla' );
        $items .= '<li class="menu-item"><a href="' . esc_url( $url_dashboard ) . '">Dashboard</a></li>';
    } 
    return $items;
}
add_filter( 'wp_nav_menu_items', 'menu_dinamis_pengurus', 10, 2 );

// =====================================================================
// KUSTOMISASI TAMPILAN WP-ADMIN (TEMA MUSHOLLA AL KARIM)
// =====================================================================
function custom_admin_theme_musholla() {
    echo '<style>
        #wpcontent, #wpbody-content { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important; }
        #adminmenuback, #adminmenuwrap, #adminmenu { background-color: #065f46 !important; }
        #adminmenu a { color: #d1fae5 !important; }
        #adminmenu li.menu-top:hover, #adminmenu li.opensub > a.menu-top, #adminmenu li > a.menu-top:focus, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu { background-color: #10b981 !important; color: #ffffff !important; }
        #adminmenu .wp-submenu, #adminmenu .wp-has-current-submenu .wp-submenu { background-color: #047857 !important; }
        #adminmenu div.wp-menu-image::before { color: #a7f3d0 !important; }
        #wpadminbar { background-color: #065f46 !important; }
        #wpadminbar #wp-admin-bar-wp-logo { display: none !important; }
        .wp-core-ui .button-primary { background: #10b981 !important; border-color: #059669 !important; border-radius: 50px !important; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2) !important; font-weight: 600 !important; transition: all 0.3s ease !important; }
        .wp-core-ui .button-primary:hover { background: #059669 !important; border-color: #047857 !important; transform: translateY(-1px); }
        .postbox { border-radius: 16px !important; border: 1px solid rgba(16, 185, 129, 0.15) !important; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.05) !important; overflow: hidden; }
        .postbox-header { border-bottom: 1px solid #d1fae5 !important; }
    </style>';
}
add_action('admin_head', 'custom_admin_theme_musholla');

// =====================================================================
// PENGATURAN REDIRECT KE HOMEPAGE SETELAH LOGOUT
// =====================================================================
function alkarim_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
    return 'https://mushollaalkarim.web.id';
}
add_filter( 'logout_redirect', 'alkarim_logout_redirect', 999, 3 );

// =====================================================================
// MENU 5: TAMBAH TOMBOL LOGOUT DI SIDEBAR ADMIN
// =====================================================================
function tambah_menu_logout_sidebar() {
    add_menu_page('Keluar', 'Keluar', 'read', 'logout-musholla', '__return_false', 'dashicons-exit', 99);
}
add_action('admin_menu', 'tambah_menu_logout_sidebar');

function proses_logout_sidebar_fixed() {
    if ( isset($_GET['page']) && $_GET['page'] === 'logout-musholla' ) {
        wp_redirect( wp_logout_url( home_url() ) );
        exit;
    }
}
add_action('admin_init', 'proses_logout_sidebar_fixed');

function css_custom_logout_sidebar() {
    echo '<style>
        #adminmenu li.toplevel_page_logout-musholla { margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); }
        #adminmenu li.toplevel_page_logout-musholla a { background-color: rgba(244, 63, 94, 0.1) !important; color: #fecdd3 !important; }
        #adminmenu li.toplevel_page_logout-musholla a:hover { background-color: #e11d48 !important; color: #ffffff !important; }
        #adminmenu li.toplevel_page_logout-musholla div.wp-menu-image:before { color: inherit !important; }
    </style>';
}
add_action('admin_head', 'css_custom_logout_sidebar');

// =====================================================================
// MENU 6: PROGRAM SEPEKAN
// =====================================================================
function register_menu_program_sepekan() {
    add_menu_page(
        'Program Sepekan', 
        'Program Sepekan', 
        'update_kas_cap',
        'program-sepekan', 
        'render_halaman_program_sepekan', 
        'dashicons-calendar-alt', 
        8
    );
}
add_action('admin_menu', 'register_menu_program_sepekan');

function render_halaman_program_sepekan() {
    if ( !current_user_can('manage_options') && !current_user_can('update_kas_cap') ) {
        wp_die(__('Mas Fahri, maaf ya, halaman ini khusus untuk Pengurus Musholla saja.'));
    }
    ?>
    <div class="wrap">
        <h1 style="font-weight: 800; color: #065f46;">🗓️ Manajemen Program Sepekan</h1>
        <p>Atur jadwal rutin ibadah Ahad - Sabtu untuk ditampilkan di beranda website.</p>
        <hr>

        <div style="background:#fff; padding:25px; border-radius:15px; border:1px solid #d1fae5; max-width:700px; box-shadow:0 8px 20px rgba(0,0,0,0.05);">
            <form id="form-program">
                <input type="hidden" id="edit_id_prog">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label><b>Pilih Hari</b></label><br>
                        <select id="hari_prog" style="width:100%; padding:10px; border-radius:8px; margin-top:5px; border:1px solid #ddd;">
                            <option value="Ahad">Ahad</option><option value="Senin">Senin</option><option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                        </select>
                    </div>
                    <div>
                        <label><b>Waktu (Jam)</b></label><br>
                        <input type="text" id="waktu_prog" placeholder="Contoh: 18:30 - Selesai" style="width:100%; padding:10px; border-radius:8px; margin-top:5px; border:1px solid #ddd;" required>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <label><b>Nama Kegiatan</b></label><br>
                    <input type="text" id="kegiatan_prog" placeholder="Contoh: Tahsin Dewasa" style="width:100%; padding:10px; border-radius:8px; margin-top:5px; border:1px solid #ddd;" required>
                </div>
                
                <div style="margin-top:25px; display: flex; gap: 10px;">
                    <button type="submit" id="btn-save-prog" class="button button-primary" style="border-radius:50px; padding:5px 30px; background:#10b981; border:none; font-weight:600;">Simpan ke Jadwal</button>
                    <button type="button" id="btn-cancel-prog" onclick="resetFormProg()" style="border-radius:50px; padding:5px 20px; background:#64748b; color:white; border:none; cursor:pointer;">Batal Edit</button>
                </div>
            </form>
        </div>

        <div style="margin-top:40px; max-width:800px;">
            <h3 style="color:#065f46;">Daftar Program Terdaftar</h3>
            <table class="wp-list-table widefat fixed striped" style="border-radius:12px; overflow:hidden; border:1px solid #d1fae5;">
                <thead>
                    <tr style="background:#f0fdf4;">
                        <th style="padding:15px;">Hari</th><th style="padding:15px;">Waktu</th><th style="padding:15px;">Kegiatan</th><th style="width:120px; padding:15px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="body-program-admin">
                    <tr><td colspan="4" style="padding:20px; text-align:center;">Memuat jadwal...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    const scriptUrlProg = 'https://script.google.com/macros/s/AKfycbyziSyzt0Pq513h7TbDRQRyTeZj6BR0FjBwAE0sf3vP3KHrGLzigsPE6c2ZowBEU_Ij/exec';
    let dataLocalProg = []; 

    async function loadProgram() {
        const tableBody = document.getElementById('body-program-admin');
        try {
            const res = await fetch(scriptUrlProg + '?t=' + new Date().getTime());
            const data = await res.json();
            dataLocalProg = data; 
            
            const urutanHari = ["Ahad", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            data.sort((a, b) => urutanHari.indexOf(a.hari) - urutanHari.indexOf(b.hari));

            let html = '';
            if (data && data.length > 0) {
                data.forEach(item => {
                    html += `<tr>
                        <td style="padding:12px;"><strong>${item.hari}</strong></td>
                        <td style="padding:12px;">${item.waktu}</td>
                        <td style="padding:12px;">${item.kegiatan}</td>
                        <td style="padding:12px;">
                            <button onclick="editProg('${item.id}')" class="button" style="background:#f39c12; color:white; border:none; border-radius:5px; font-size:11px; cursor:pointer; margin-right:4px;">Edit</button>
                            <button onclick="hapusProg('${item.id}')" class="button" style="background:#ef4444; color:white; border:none; border-radius:5px; font-size:11px; cursor:pointer;">Hapus</button>
                        </td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="4" style="padding:20px; text-align:center;">Belum ada jadwal rutin tersimpan.</td></tr>';
            }
            tableBody.innerHTML = html;
        } catch(e) {
            tableBody.innerHTML = '<tr><td colspan="4" style="color:red; text-align:center; padding:20px;">⚠️ Gagal memuat data.</td></tr>';
        }
    }

    function editProg(id) {
        const item = dataLocalProg.find(x => x.id == id);
        if(!item) return;

        document.getElementById('edit_id_prog').value = item.id;
        document.getElementById('hari_prog').value = item.hari;
        document.getElementById('waktu_prog').value = item.waktu;
        document.getElementById('kegiatan_prog').value = item.kegiatan;

        document.getElementById('btn-save-prog').innerText = "Update Jadwal";
        document.getElementById('btn-save-prog').style.background = "#f39c12";
        document.getElementById('btn-cancel-prog').style.display = "block";
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetFormProg() {
        document.getElementById('form-program').reset();
        document.getElementById('edit_id_prog').value = "";
        document.getElementById('btn-save-prog').innerText = "Simpan ke Jadwal";
        document.getElementById('btn-save-prog').style.background = "#10b981";
        document.getElementById('btn-cancel-prog').style.display = "none";
    }

    document.getElementById('form-program').onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-save-prog');
        const editId = document.getElementById('edit_id_prog').value;
        const isUpdate = editId !== "";
        
        btn.innerText = "⏳ Memproses..."; btn.disabled = true;
        
        const payload = {
            action: isUpdate ? "update" : "insert",
            id: editId || null,
            hari: document.getElementById('hari_prog').value,
            waktu: document.getElementById('waktu_prog').value,
            kegiatan: document.getElementById('kegiatan_prog').value
        };

        try {
            await fetch(scriptUrlProg, { method: 'POST', mode: 'no-cors', body: JSON.stringify(payload) });
            
            Swal.fire({
                title: 'Alhamdulillah!',
                text: isUpdate ? 'Jadwal rutin berhasil diperbarui.' : 'Jadwal rutin berhasil ditambahkan.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                resetFormProg();
                location.reload();
            });
        } catch (err) {
            Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
            btn.innerText = "Simpan ke Jadwal"; btn.disabled = false;
        }
    };

    function hapusProg(id) {
        Swal.fire({
            title: 'Hapus Jadwal Ini?',
            text: "Jadwal rutin ini tidak akan tampil lagi di website.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#7f8c8d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                Swal.fire({title: 'Memproses...', allowOutsideClick: false, showConfirmButton: false});
                Swal.showLoading();
                
                try {
                    await fetch(scriptUrlProg, { method: 'POST', mode: 'no-cors', body: JSON.stringify({action: "delete", id: id}) });
                    
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Jadwal berhasil dihapus.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        loadProgram();
                    });
                } catch(e) {
                    Swal.fire('Gagal!', 'Tidak dapat menghapus data.', 'error');
                }
            }
        });
    }
    
    loadProgram();
    </script>
    <?php
}

// =====================================================================
// MENU 7: CUSTOM POST TYPE - UPDATE BERITA 
// =====================================================================
function register_cpt_berita_musholla() {
    $labels = array(
        'name'               => 'Update Berita',
        'singular_name'      => 'Berita',
        'menu_name'          => 'Update Berita',
        'add_new'            => 'Tambah Berita Baru',
        'add_new_item'       => 'Tambah Berita Baru',
        'edit_item'          => 'Edit Berita',
        'new_item'           => 'Berita Baru',
        'view_item'          => 'Lihat Berita',
        'search_items'       => 'Cari Berita',
        'not_found'          => 'Berita belum ada',
        'not_found_in_trash' => 'Tidak ada berita di tempat sampah',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'capability_type'     => 'post',
        'map_meta_cap'        => true, // --- PERBAIKAN META CAP ---
        'hierarchical'        => false,
        'menu_position'       => 9,
        'menu_icon'           => 'dashicons-megaphone',
        'supports'            => array( 'title', 'editor', 'thumbnail' ), 
        'rewrite'             => array('slug' => 'berita-musholla'),
    );

    register_post_type( 'berita_musholla', $args );
}
add_action( 'init', 'register_cpt_berita_musholla' );

// =====================================================================
// SHORTCODE SLIDER DOKUMENTASI BERITA (MAKS 3 BERITA TERBARU)
// =====================================================================
function slider_berita_musholla_shortcode() {
    $query = new WP_Query(array(
        'post_type'      => 'berita_musholla',
        'posts_per_page' => 3, 
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC'
    ));

    if ( ! $query->have_posts() ) return '<p style="text-align:center; color:#64748b;">Belum ada dokumentasi program saat ini.</p>';

    ob_start(); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
    <div class="swiper beritaSwiper">
        <div class="swiper-wrapper">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <div class="swiper-slide">
                    <div class="berita-slide-card">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="berita-image"><?php the_post_thumbnail('medium_large'); ?></div>
                        <?php endif; ?>
                        <div class="berita-content">
                            <h3><?php the_title(); ?></h3>
                            <div class="berita-text"><?php echo wp_trim_words( get_the_content(), 15 ); ?></div>
                        </div>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next d-none-mobile"></div>
        <div class="swiper-button-prev d-none-mobile"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('slider_berita', 'slider_berita_musholla_shortcode');

/**
 * 1. REGISTER CUSTOM POST TYPE (PROGRAM WAKAF)
 */
function register_program_wakaf_cpt() {
    $user = wp_get_current_user();
    $allowed_roles = array('administrator', 'pengurus_musholla');
    $is_allowed = array_intersect($allowed_roles, (array) $user->roles);

    $labels = array(
        'name'               => 'Program Wakaf',
        'singular_name'      => 'Program Wakaf',
        'menu_name'          => 'Program Wakaf',
        'add_new'            => 'Tambah Program',
        'add_new_item'       => 'Tambah Program Wakaf Baru',
        'edit_item'          => 'Edit Program',
        'all_items'          => 'Semua Program',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-heart', 
        'supports'           => array('title'), 
        'capability_type'    => 'post',
        'map_meta_cap'       => true, // --- PERBAIKAN META CAP SANGAT PENTING DI SINI ---
        'show_in_menu'       => !empty($is_allowed),
        'show_in_rest'       => true, 
    );

    register_post_type('program_wakaf', $args);
}
add_action('init', 'register_program_wakaf_cpt');

/**
 * 2. TAMBAHKAN KOLOM INPUT (META BOX) TERMASUK LINK CHECKOUT
 */
function wakaf_add_custom_meta_boxes() {
    add_meta_box('wakaf_details_box', 'Konfigurasi Program Wakaf', 'wakaf_render_meta_box', 'program_wakaf', 'normal', 'high');
}
add_action('add_meta_boxes', 'wakaf_add_custom_meta_boxes');

function wakaf_render_meta_box($post) {
    $webapp_url = get_post_meta($post->ID, '_wakaf_webapp_url', true);
    $target_nominal = get_post_meta($post->ID, '_wakaf_target_nominal', true);
    $checkout_url = get_post_meta($post->ID, '_wakaf_checkout_url', true);
    $deskripsi = get_post_meta($post->ID, '_wakaf_deskripsi', true);

    wp_nonce_field('wakaf_save_meta_box_data', 'wakaf_meta_box_nonce');

    echo '<div style="margin-bottom: 15px;"><label style="display:block; font-weight:bold; margin-bottom:5px;">Web App URL (Apps Script):</label>';
    echo '<input type="text" name="wakaf_webapp_url" value="' . esc_attr($webapp_url) . '" style="width:100%;" placeholder="https://script.google.com/..." /></div>';

    echo '<div style="margin-bottom: 15px;"><label style="display:block; font-weight:bold; margin-bottom:5px;">Link Checkout (Lynk.id/Xendit):</label>';
    echo '<input type="text" name="wakaf_checkout_url" value="' . esc_attr($checkout_url) . '" style="width:100%;" placeholder="https://lynk.id/musholla-alkarim/s/..." /></div>';

    echo '<div style="margin-bottom: 15px;"><label style="display:block; font-weight:bold; margin-bottom:5px;">Target Nominal (Rp):</label>';
    echo '<input type="number" name="wakaf_target_nominal" value="' . esc_attr($target_nominal) . '" style="width:100%;" /></div>';

    echo '<div style="margin-bottom: 15px;"><label style="display:block; font-weight:bold; margin-bottom:5px;">Deskripsi Singkat:</label>';
    echo '<textarea name="wakaf_deskripsi" style="width:100%; height:80px;">' . esc_textarea($deskripsi) . '</textarea></div>';
}

/**
 * 3. SIMPAN DATA INPUTAN
 */
function wakaf_save_meta_box_data($post_id) {
    if (!isset($_POST['wakaf_meta_box_nonce']) || !wp_verify_nonce($_POST['wakaf_meta_box_nonce'], 'wakaf_save_meta_box_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = [
        '_wakaf_webapp_url' => 'wakaf_webapp_url', 
        '_wakaf_target_nominal' => 'wakaf_target_nominal', 
        '_wakaf_checkout_url' => 'wakaf_checkout_url', 
        '_wakaf_deskripsi' => 'wakaf_deskripsi'
    ];
    foreach ($fields as $meta_key => $post_key) {
        if (isset($_POST[$post_key])) update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
    }
}
add_action('save_post', 'wakaf_save_meta_box_data');

/**
 * 4. DASHBOARD MONITORING DI ATAS DAFTAR PROGRAM ADMIN
 */
function display_wakaf_dashboard_summary($views) {
    $programs = get_posts(array('post_type' => 'program_wakaf', 'post_status' => 'publish', 'numberposts' => -1));
    if (empty($programs)) return $views;

    echo '<div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin: 20px 0 30px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-family: sans-serif;">';
    echo '<h2 style="margin-top: 0; color: #2c5e1a; border-bottom: 2px solid #eee; padding-bottom: 10px; font-size: 1.3em;">📊 Monitoring Program Wakaf</h2>';
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">';

    foreach ($programs as $program) {
        $webapp_url = get_post_meta($program->ID, '_wakaf_webapp_url', true);
        $target = (float) get_post_meta($program->ID, '_wakaf_target_nominal', true);
        $terkumpul = 0; $error_msg = '';

        if (!empty($webapp_url)) {
            $response = wp_remote_get($webapp_url, array('timeout' => 15, 'redirection' => 5));
            if (is_wp_error($response)) { $error_msg = 'Koneksi Gagal'; } else {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($data['total'])) { $terkumpul = (float) $data['total']; } else { $error_msg = 'Data 0 / Gagal'; }
            }
        }

        $persen = ($target > 0) ? ($terkumpul / $target) * 100 : 0;
        if ($persen > 100) $persen = 100;
        $bar_color = ($persen >= 100) ? '#10b981' : '#fbc02d';

        echo '<div style="background: #f9f9f9; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; position: relative;">';
        echo '<strong style="display:block; font-size:15px; margin-bottom:12px; color:#1e293b;">' . esc_html($program->post_title) . '</strong>';
        if ($error_msg && $terkumpul == 0) echo '<span style="color: #dc2626; font-size: 11px; position: absolute; top: 18px; right: 18px;">⚠️ ' . $error_msg . '</span>';
        echo '<div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:8px; color:#475569;">';
        echo '<span>Terkumpul: <b>Rp ' . number_format($terkumpul, 0, ',', '.') . '</b></span><span>Target: Rp ' . number_format($target, 0, ',', '.') . '</span></div>';
        echo '<div style="background: #e2e8f0; border-radius: 20px; height: 20px; position: relative; overflow: hidden;">';
        echo '<div style="background: ' . $bar_color . '; width: ' . $persen . '%; height: 100%; transition: width 0.8s ease;"></div>';
        echo '<span style="position:absolute; width:100%; text-align:center; top:0; left:0; font-size:10px; line-height:20px; font-weight:800; color:#1e293b;">' . round($persen, 1) . '%</span></div></div>';
    }
    echo '</div></div>';
    return $views;
}
add_filter('views_edit-program_wakaf', 'display_wakaf_dashboard_summary');

/**
 * 5. DAFTARKAN DATA KE REST API AGAR BISA DIBACA POP-UP (JAVASCRIPT)
 */
add_action('rest_api_init', function() {
    register_rest_field('program_wakaf', 'wakaf_data', [
        'get_callback' => function($post_array) {
            $post_id = $post_array['id'];
            $webapp_url = get_post_meta($post_id, '_wakaf_webapp_url', true);
            $target = (float) get_post_meta($post_id, '_wakaf_target_nominal', true);
            
            $terkumpul = 0;
            if ($webapp_url) {
                $response = wp_remote_get($webapp_url, ['timeout' => 10, 'redirection' => 5]);
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    $terkumpul = isset($data['total']) ? (float)$data['total'] : 0;
                }
            }

            return [
                'target' => $target,
                'terkumpul' => $terkumpul,
                'persen' => ($target > 0) ? round(($terkumpul / $target) * 100, 1) : 0,
                'checkout_url' => get_post_meta($post_id, '_wakaf_checkout_url', true)
            ];
        }
    ]);
});

/**
 * 6. SHORTCODE UNTUK MENAMPILKAN KOTAK INFAQ & POP-UP WAKAF
 * Gunakan [daftar_wakaf_musholla] di halaman website
 */
function shortcode_wakaf_musholla() {
    ob_start(); ?>

    <style>
        .infaq-container { font-family: 'Segoe UI', Roboto, sans-serif; padding: 40px 10px; }
        .infaq-header { text-align: center; margin-bottom: 50px; max-width: 800px; margin: 0 auto 50px; }
        .infaq-header h1 { color: #065f46; font-size: 36px; font-weight: 800; margin-bottom: 20px; }
        .hadis-box { background: #f0fdf4; border-left: 5px solid #10b981; padding: 25px; border-radius: 0 20px 20px 0; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.05); }
        .hadis-text { font-style: italic; color: #334155; font-size: 16px; line-height: 1.8; margin-bottom: 10px; display: block; }
        .hadis-rawi { font-weight: 700; color: #059669; font-size: 14px; display: block; }
        .infaq-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .infaq-card { background: #ffffff; border-radius: 20px; padding: 30px; text-align: center; transition: 0.4s; border: 1px solid #d1fae5; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.05); display: flex; flex-direction: column; justify-content: space-between; }
        .infaq-card:hover { transform: translateY(-12px); box-shadow: 0 20px 40px rgba(16, 185, 129, 0.12); border-color: #10b981; }
        .infaq-icon { font-size: 40px; margin-bottom: 20px; background: #ecfdf5; width: 80px; height: 80px; line-height: 80px; border-radius: 50%; display: inline-block; color: #10b981; }
        .infaq-card h3 { color: #065f46; font-size: 20px; margin-bottom: 15px; font-weight: 700; }
        .infaq-card p { color: #64748b; font-size: 14px; line-height: 1.6; margin-bottom: 25px; }
        .btn-infaq { display: inline-block; padding: 12px 25px; background: #10b981; color: #fff !important; text-decoration: none !important; border-radius: 50px; font-weight: 600; font-size: 14px; transition: 0.3s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); cursor: pointer; border: none; }
        .btn-infaq:hover { background: #059669; transform: scale(1.05); }
        .donatur-tetap-banner { background: linear-gradient(135deg, #065f46 0%, #10b981 100%); border-radius: 24px; padding: 40px; color: white; text-align: center; }
        .btn-donatur { display: inline-block; padding: 15px 40px; background: #ffffff; color: #065f46 !important; text-decoration: none !important; border-radius: 50px; font-weight: 800; transition: 0.3s; }

        /* MODAL STYLE */
        .wakaf-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 99999; backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 15px; }
        .wakaf-modal-content { background: #fff; width: 100%; max-width: 550px; max-height: 85vh; border-radius: 24px; padding: 30px; position: relative; overflow-y: auto; }
        .close-modal { position: absolute; top: 20px; right: 20px; font-size: 30px; cursor: pointer; color: #64748b; }
        .modal-program-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; margin-bottom: 15px; }
        .modal-progress-bar-bg { background: #e2e8f0; height: 16px; border-radius: 10px; overflow: hidden; margin-bottom: 15px; position: relative; }
        .modal-progress-bar-fill { background: #10b981; height: 100%; transition: width 1s ease; }
        .modal-percent-text { position: absolute; width: 100%; text-align: center; top: 0; font-size: 10px; line-height: 16px; font-weight: 900; color: #1e293b; }
        .btn-wakaf-direct { display: block; text-align: center; background: #065f46; color: #fff !important; padding: 12px; border-radius: 12px; text-decoration: none !important; font-weight: 700; }
    </style>

    <div class="infaq-container">
        <div class="infaq-header">
            <h1>Mari Berinfaq</h1>
            <div class="hadis-box">
                <span class="hadis-text">"Jika seseorang meninggal dunia, maka terputuslah amalannya kecuali tiga perkara: sedekah jariyah, ilmu yang bermanfaat, atau doa anak yang shalih."</span>
                <span class="hadis-rawi">(HR. Muslim no. 1631)</span>
            </div>
        </div>
        
        <div class="infaq-grid">
            <div class="infaq-card">
                <div><div class="infaq-icon">🍲</div><h3>Infaq Makan Gratis</h3><p>Membantu penyediaan makan siang gratis bagi jamaah sholat zuhur harian.</p></div>
                <a href="https://lynk.id/musholla-alkarim/s/llmlwwxgldv6" class="btn-infaq" target="_blank">Infaq Makan Gratis</a>
            </div>

            <div class="infaq-card">
                <div><div class="infaq-icon">⚡</div><h3>Infaq Operasional</h3><p>Mendukung biaya listrik, air, and pemeliharaan harian musholla.</p></div>
                <a href="https://lynk.id/musholla-alkarim/s/jx95wp8gg51j" class="btn-infaq" target="_blank">Dukung Operasional</a>
            </div>

            <div class="infaq-card">
                <div><div class="infaq-icon">🏗️</div><h3>Wakaf Fasilitas</h3><p>Pahala jariyah untuk pengadaan sarana ibadah (AC, karpet, rak buku, dll).</p></div>
                <button id="btnBukaWakaf" class="btn-infaq">Wakaf Sekarang</button>
            </div>
        </div>

        <div class="donatur-tetap-banner">
            <h2>Menjadi Donatur Tetap</h2>
            <p>Alirkan pahala tanpa putus dengan komitmen infaq rutin setiap bulan.</p>
            <a href="https://lynk.id/musholla-alkarim/s/jx95wp8gg51j" class="btn-donatur" target="_blank">DAFTAR DONATUR TETAP</a>
        </div>
    </div>

    <!-- MODAL -->
    <div id="wakafModal" class="wakaf-modal-overlay">
        <div class="wakaf-modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h2 style="text-align:center; color:#065f46; margin-bottom:20px;">Pilih Program Wakaf</h2>
            <div id="modalContentList">
                <p style="text-align:center;">⏳ Menghubungkan ke database...</p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('wakafModal');
        const openBtn = document.getElementById('btnBukaWakaf');
        const closeBtn = document.getElementById('closeModal');
        const contentList = document.getElementById('modalContentList');

        if(openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'flex';
                loadWakafPrograms();
            });
        }

        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; }

        function loadWakafPrograms() {
            fetch('/wp-json/wp/v2/program_wakaf')
            .then(res => res.json())
            .then(data => {
                if(!data || data.length === 0) {
                    contentList.innerHTML = '<p style="text-align:center;">Belum ada program aktif.</p>';
                    return;
                }
                let html = '';
                data.forEach(post => {
                    const info = post.wakaf_data;
                    const persen = info.persen > 100 ? 100 : info.persen;
                    html += `
                    <div class="modal-program-item">
                        <span style="font-weight:700; display:block; margin-bottom:10px;">${post.title.rendered}</span>
                        <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:5px;">
                            <span>Terkumpul: <b>Rp ${info.terkumpul.toLocaleString('id-ID')}</b></span>
                            <span>Target: Rp ${info.target.toLocaleString('id-ID')}</span>
                        </div>
                        <div class="modal-progress-bar-bg">
                            <div class="modal-progress-bar-fill" style="width: ${persen}%; background: ${persen >= 100 ? '#10b981' : '#fbc02d'};"></div>
                            <div class="modal-percent-text">${persen}% TERCAPAI</div>
                        </div>
                        <a href="${info.checkout_url}" class="btn-wakaf-direct" target="_blank">Berwakaf di Sini</a>
                    </div>`;
                });
                contentList.innerHTML = html;
            })
            .catch(() => contentList.innerHTML = '<p style="text-align:center; color:red;">Gagal memuat data.</p>');
        }
    })();
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('daftar_wakaf_musholla', 'shortcode_wakaf_musholla');