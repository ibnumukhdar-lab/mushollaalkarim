<?php
// SNIPPET WordPress #23: Penilaian Lomba Azan V.2
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

/* * ==========================================================
 * 1. MEMBUAT MENU SUB-PAGE "PENILAIAN JURI" (BACKEND)
 * ==========================================================
 */
add_action('init', 'ksk_tambah_role_ustadz');
function ksk_tambah_role_ustadz() {
    if (!get_role('ustadz')) {
        add_role('ustadz', 'Ustadz / Juri', array('read' => true));
    }
}

add_action('admin_menu', 'ksk_add_juri_submenu');
function ksk_add_juri_submenu() {
    add_submenu_page(
        'edit.php?post_type=pendaftar_adzan', 
        'Meja Penilaian Juri',                
        'Penilaian Juri',                     
        'read',                               
        'penilaian_juri_adzan',               
        'ksk_render_halaman_juri_backend'             
    );
}

/* * ==========================================================
 * 2. TAMPILAN HALAMAN PENILAIAN JURI ADMIN (TABEL, MODAL & CETAK)
 * ==========================================================
 */
function ksk_render_halaman_juri_backend() {
    $current_juri_id = get_current_user_id();
    $peserta = get_posts(array(
        'post_type'      => 'pendaftar_adzan',
        'post_status'    => 'publish', 
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC'
    ));

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline"><span class="dashicons dashicons-welcome-write-blog" style="font-size:28px; margin-top:5px; margin-right:10px;"></span> Meja Penilaian Juri Lomba Adzan</h1>';
    
    // Panel Tombol Cetak Backend
    echo '<div style="background:#fff; padding:15px; border-left:4px solid #10b981; box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-top:15px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">';
    echo '<div><p style="margin:0; font-size:14px;">Hanya menampilkan peserta lunas. <b>Rumus:</b> (Makhraj 50%) + (Vokal 40%) + (Adab 10%).</p></div>';
    echo '<div style="display:flex; gap:10px;">';
    echo '<a href="' . esc_url(admin_url('admin-ajax.php?action=ksk_cetak_rekap_juri')) . '" target="_blank" class="button"><span class="dashicons dashicons-media-spreadsheet" style="margin-top:4px;"></span> Cetak Rekap per Juri</a>';
    echo '<a href="' . esc_url(admin_url('admin-ajax.php?action=ksk_cetak_berita_acara')) . '" target="_blank" class="button button-primary"><span class="dashicons dashicons-awards" style="margin-top:4px;"></span> Cetak Berita Acara (Juara 1-3)</a>';
    echo '</div>';
    echo '</div>';

    echo '<table class="wp-list-table widefat fixed striped" style="margin-top:20px;">';
    echo '<thead><tr>';
    echo '<th style="width:5%;">No</th>';
    echo '<th style="width:20%;">Nama Anak</th>';
    echo '<th style="text-align:center;">Kategori</th>';
    echo '<th style="text-align:center;">Karya Video</th>';
    echo '<th style="text-align:center;">Nilai & Catatan Anda</th>';
    echo '<th style="text-align:center; background:#f0fdf4;">Skor Akhir (Rata-rata)</th>';
    echo '</tr></thead><tbody>';

    if (empty($peserta)) {
        echo '<tr><td colspan="6" style="text-align:center; padding:20px;">Belum ada peserta yang diverifikasi panitia.</td></tr>';
    } else {
        $no = 1;
        foreach ($peserta as $p) {
            $kategori = get_post_meta($p->ID, 'kategori', true);
            $link_video = get_post_meta($p->ID, 'link_video', true);
            
            $nilai_saya_json = get_post_meta($p->ID, 'skor_juri_' . $current_juri_id, true);
            $nilai_saya = $nilai_saya_json ? json_decode($nilai_saya_json, true) : null;
            
            $semua_meta = get_post_meta($p->ID);
            $total_akumulasi = 0; $jumlah_juri_menilai = 0;
            
            foreach ($semua_meta as $key => $val) {
                if (strpos($key, 'skor_juri_') === 0) {
                    $data_juri = json_decode($val[0], true);
                    if (isset($data_juri['total'])) {
                        $total_akumulasi += floatval($data_juri['total']);
                        $jumlah_juri_menilai++;
                    }
                }
            }
            $rata_rata_akhir = ($jumlah_juri_menilai > 0) ? round($total_akumulasi / $jumlah_juri_menilai, 2) : 0;

            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td><strong>' . esc_html($p->post_title) . '</strong></td>';
            echo '<td style="text-align:center;">' . esc_html($kategori) . '</td>';
            echo '<td style="text-align:center;">' . ($link_video ? '<a href="' . esc_url($link_video) . '" target="_blank" class="button button-primary">Simak Video</a>' : '-') . '</td>';
            echo '<td style="text-align:center;">' . ($nilai_saya ? '<strong style="color:#047857; font-size:16px;">' . $nilai_saya['total'] . '</strong><br><small style="color:#64748b; font-style:italic;">"'.wp_trim_words($nilai_saya['catatan'], 5, '...').'"</small>' : '<span style="color:#ef4444; font-weight:600;">Belum Dinilai</span>') . '</td>';
            echo '<td style="text-align:center; background:#f0fdf4;">' . ($rata_rata_akhir > 0 ? '<strong style="font-size:16px; color:#1e293b;">' . $rata_rata_akhir . '</strong><br><small>('.$jumlah_juri_menilai.' Juri)</small>' : '-') . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></div>';
    echo '<div style="margin-top:20px; padding:15px; background:#e0f2fe; border-left:4px solid #0284c7;"><p style="margin:0; font-weight:bold; color:#0369a1;">💡 Info Panitia: Untuk melakukan pengisian dan modifikasi nilai, silakan gunakan <b>Halaman Portal Juri (Front-End)</b> yang sudah disediakan di website.</p></div>';
}

/* * ==========================================================
 * 3. FUNGSI AJAX SIMPAN NILAI & GET DATA (DIPAKAI FRONTEND)
 * ==========================================================
 */
add_action('wp_ajax_ksk_simpan_nilai_juri', 'ksk_ajax_simpan_nilai_juri');
function ksk_ajax_simpan_nilai_juri() {
    if (!is_user_logged_in()) { wp_send_json_error('Sesi terputus. Silakan login kembali.'); }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $juri_id = get_current_user_id(); 

    if (!$post_id || !$juri_id) { wp_send_json_error('Data tidak valid.'); }

    $data_nilai = array(
        'makhraj' => floatval($_POST['makhraj']),
        'vokal'   => floatval($_POST['vokal']),
        'adab'    => floatval($_POST['adab']),
        'total'   => floatval($_POST['total']),
        'catatan' => sanitize_textarea_field($_POST['catatan'])
    );

    update_post_meta($post_id, 'skor_juri_' . $juri_id, wp_json_encode($data_nilai));
    wp_send_json_success('Nilai tersimpan');
}

add_action('wp_ajax_ksk_get_data_juri_frontend', 'ksk_get_data_juri_frontend');
add_action('wp_ajax_nopriv_ksk_get_data_juri_frontend', 'ksk_get_data_juri_frontend'); 
function ksk_get_data_juri_frontend() {
    if (!is_user_logged_in()) { wp_send_json_error('Sistem mendeteksi Anda belum login. Silakan Log In terlebih dahulu.'); }

    $user = wp_get_current_user();
    $user_roles = array_map('strtolower', (array) $user->roles);
    
    $is_allowed = false;
    foreach ($user_roles as $role) {
        if (strpos($role, 'ustadz') !== false || $role === 'administrator') { $is_allowed = true; break; }
    }

    if (!$is_allowed) { wp_send_json_error('Akses ditolak. Halaman ini khusus untuk Juri Lomba.'); }

    $current_juri_id = $user->ID;
    $peserta = get_posts(array('post_type' => 'pendaftar_adzan', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'ASC'));
    $data_peserta = array();

    foreach ($peserta as $p) {
        $kategori = get_post_meta($p->ID, 'kategori', true); 
        $link_video = get_post_meta($p->ID, 'link_video', true);
        
        $nilai_saya_json = get_post_meta($p->ID, 'skor_juri_' . $current_juri_id, true);
        $nilai_saya = $nilai_saya_json ? json_decode($nilai_saya_json, true) : null;
        
        $total_akumulasi = 0; $jumlah_juri_menilai = 0;
        foreach (get_post_meta($p->ID) as $key => $val) {
            if (strpos($key, 'skor_juri_') === 0) { 
                $data_juri = json_decode($val[0], true); 
                if (isset($data_juri['total'])) { 
                    $total_akumulasi += floatval($data_juri['total']); 
                    $jumlah_juri_menilai++; 
                } 
            }
        }
        $rata_rata_akhir = ($jumlah_juri_menilai > 0) ? round($total_akumulasi / $jumlah_juri_menilai, 2) : 0;

        $data_peserta[] = array(
            'id' => $p->ID, 'nama' => $p->post_title, 'kategori' => $kategori, 'link_video' => $link_video, 
            'nilai_saya' => $nilai_saya, 'rata_rata' => $rata_rata_akhir, 'jumlah_juri' => $jumlah_juri_menilai
        );
    }
    wp_send_json_success($data_peserta);
}

/* * ==========================================================
 * 4. FUNGSI CETAK BERITA ACARA & REKAP (HTML RENDERER)
 * ==========================================================
 */
// A. Fungsi Penarik Data Master untuk Cetak
function ksk_get_master_data_cetak() {
    $peserta = get_posts(array('post_type' => 'pendaftar_adzan', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'ASC'));
    $data_peserta = array();
    $daftar_juri = array();

    foreach ($peserta as $p) {
        $meta = get_post_meta($p->ID);
        $skor_detail = array();
        $total_akumulasi = 0; $jumlah_juri_menilai = 0;

        foreach ($meta as $key => $val) {
            if (strpos($key, 'skor_juri_') === 0) {
                $juri_id = str_replace('skor_juri_', '', $key);
                // Simpan ID dan Nama Juri ke array global
                if(!isset($daftar_juri[$juri_id])) {
                    $user_info = get_userdata($juri_id);
                    $daftar_juri[$juri_id] = $user_info ? $user_info->display_name : 'Juri '.$juri_id;
                }
                
                $data_juri = json_decode($val[0], true);
                $skor_detail[$juri_id] = $data_juri;
                
                if (isset($data_juri['total'])) {
                    $total_akumulasi += floatval($data_juri['total']);
                    $jumlah_juri_menilai++;
                }
            }
        }
        
        $rata_rata_akhir = ($jumlah_juri_menilai > 0) ? round($total_akumulasi / $jumlah_juri_menilai, 2) : 0;
        $data_peserta[] = array(
            'nama' => $p->post_title,
            'lembaga' => get_post_meta($p->ID, 'asal_lembaga', true),
            'kategori' => get_post_meta($p->ID, 'kategori', true),
            'skor_detail' => $skor_detail,
            'rata_rata' => $rata_rata_akhir,
            'jumlah_juri' => $jumlah_juri_menilai
        );
    }
    return array('peserta' => $data_peserta, 'juri' => $daftar_juri);
}

// B. Endpoint Cetak Berita Acara
add_action('wp_ajax_ksk_cetak_berita_acara', 'ksk_cetak_berita_acara_action');
function ksk_cetak_berita_acara_action() {
    if (!current_user_can('edit_posts')) die('Akses ditolak.');
    $master = ksk_get_master_data_cetak();
    $peserta = $master['peserta'];

    // Kelompokkan dan Sortir Juara berdasarkan Kategori
    $kat_A = array(); $kat_B = array();
    foreach($peserta as $p) {
        if($p['rata_rata'] > 0) {
            if($p['kategori'] == 'A') $kat_A[] = $p;
            elseif($p['kategori'] == 'B') $kat_B[] = $p;
        }
    }
    
    usort($kat_A, function($a, $b) { return $b['rata_rata'] <=> $a['rata_rata']; });
    usort($kat_B, function($a, $b) { return $b['rata_rata'] <=> $a['rata_rata']; });

    $juara_A = array_slice($kat_A, 0, 3);
    $juara_B = array_slice($kat_B, 0, 3);

    ?>
    <!DOCTYPE html><html><head><title>Berita Acara Hasil Lomba</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; color: #000; padding: 30px; font-size:14pt; line-height: 1.5; }
        .kop { text-align: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop h2, .kop h3 { margin: 5px 0; text-transform: uppercase; }
        .title { text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 8px; text-align: left; }
        th { background: #f2f2f2; text-align: center; }
        .ttd-box { width: 100%; margin-top: 40px; display: table; }
        .ttd-col { display: table-cell; width: 50%; text-align: center; }
        @media print { body { padding: 0; } @page { margin: 1.5cm; } }
    </style></head><body onload="window.print()">
        <div class="kop">
            <h2>PANITIA PELAKSANA LOMBA VIDEO ADZAN</h2>
            <h3>MAJELIS IDAROH MUSHOLLA AL KARIM</h3>
            <p style="font-size:12pt; margin:0;">Jalan Cilik Riwut KM 5 Perumnas Mentaya Permai Blok D No 2</p>
        </div>
        <div class="title">BERITA ACARA HASIL PENJURIAN LOMBA</div>
        <p>Pada hari ini, <b><?php echo date_i18n('l, d F Y'); ?></b>, bertempat di Sekretariat Musholla Al Karim, Tim Dewan Juri telah melakukan rekapitulasi penilaian Lomba Video Adzan Online. Berdasarkan akumulasi rata-rata nilai, diputuskan nama-nama berikut sebagai pemenang:</p>
        
        <b>KATEGORI A (Kelas 1 - 3 SD)</b>
        <table>
            <tr><th width="10%">Peringkat</th><th width="35%">Nama Peserta</th><th width="35%">Asal Lembaga</th><th width="20%">Skor Akhir</th></tr>
            <?php 
            $rank = 1;
            foreach($juara_A as $j) { 
                echo "<tr><td style='text-align:center;'>Juara {$rank}</td><td>{$j['nama']}</td><td>{$j['lembaga']}</td><td style='text-align:center;'><b>{$j['rata_rata']}</b></td></tr>"; 
                $rank++;
            }
            if(empty($juara_A)) echo "<tr><td colspan='4' style='text-align:center;'>Belum ada data nilai.</td></tr>";
            ?>
        </table>

        <b>KATEGORI B (Kelas 4 - 6 SD)</b>
        <table>
            <tr><th width="10%">Peringkat</th><th width="35%">Nama Peserta</th><th width="35%">Asal Lembaga</th><th width="20%">Skor Akhir</th></tr>
            <?php 
            $rank = 1;
            foreach($juara_B as $j) { 
                echo "<tr><td style='text-align:center;'>Juara {$rank}</td><td>{$j['nama']}</td><td>{$j['lembaga']}</td><td style='text-align:center;'><b>{$j['rata_rata']}</b></td></tr>"; 
                $rank++;
            }
            if(empty($juara_B)) echo "<tr><td colspan='4' style='text-align:center;'>Belum ada data nilai.</td></tr>";
            ?>
        </table>

        <p>Demikian Berita Acara ini dibuat dengan sebenar-benarnya dan bersifat mutlak tidak dapat diganggu gugat.</p>

        <div class="ttd-box">
            <div class="ttd-col">
                Mengetahui,<br>Ketua Panitia Pelaksana<br><br><br><br>
                <b>( .................................... )</b>
            </div>
            <div class="ttd-col">
                <br>Ketua Dewan Juri<br><br><br><br>
                <b>( .................................... )</b>
            </div>
        </div>
    </body></html>
    <?php
    die();
}

// C. Endpoint Cetak Rekap per Juri
add_action('wp_ajax_ksk_cetak_rekap_juri', 'ksk_cetak_rekap_juri_action');
function ksk_cetak_rekap_juri_action() {
    if (!current_user_can('edit_posts')) die('Akses ditolak.');
    $master = ksk_get_master_data_cetak();
    $peserta = $master['peserta'];
    $daftar_juri = $master['juri'];

    ?>
    <!DOCTYPE html><html><head><title>Rekapitulasi Nilai per Juri</title>
    <style>
        body { font-family: Arial, sans-serif; color: #000; padding: 20px; font-size:11pt; line-height: 1.4; }
        .page-break { page-break-after: always; }
        .header { text-align: center; margin-bottom: 20px; border-bottom:2px solid #000; padding-bottom:10px; }
        h2, h3 { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size:10pt;}
        table, th, td { border: 1px solid #000; }
        th, td { padding: 6px; text-align: left; }
        th { background: #f2f2f2; text-align: center; }
        .center { text-align:center; }
        @media print { body { padding: 0; } @page { margin: 1cm; size: landscape;} }
    </style></head><body onload="window.print()">
        <?php 
        if(empty($daftar_juri)) {
            echo "<h3>Belum ada data penilaian dari satupun juri.</h3></body></html>";
            die();
        }

        $count = 0;
        foreach($daftar_juri as $jid => $nama_juri) {
            $count++;
            $break_class = ($count < count($daftar_juri)) ? 'page-break' : '';
            ?>
            <div class="<?php echo $break_class; ?>">
                <div class="header">
                    <h2>REKAPITULASI PENILAIAN LOMBA ADZAN AL KARIM</h2>
                    <h3>LEMBAR PENILAIAN JURI: <?php echo strtoupper($nama_juri); ?></h3>
                </div>
                <table>
                    <tr>
                        <th width="3%">No</th>
                        <th width="20%">Nama Peserta</th>
                        <th width="5%">Kat</th>
                        <th width="10%">Makhraj (50%)</th>
                        <th width="10%">Vokal (40%)</th>
                        <th width="10%">Adab (10%)</th>
                        <th width="10%">Total Skor</th>
                        <th width="32%">Catatan Evaluasi</th>
                    </tr>
                    <?php 
                    $no = 1;
                    foreach($peserta as $p) {
                        $skor = isset($p['skor_detail'][$jid]) ? $p['skor_detail'][$jid] : null;
                        $m = $skor ? $skor['makhraj'] : '-';
                        $v = $skor ? $skor['vokal'] : '-';
                        $a = $skor ? $skor['adab'] : '-';
                        $t = $skor ? '<b>'.$skor['total'].'</b>' : '-';
                        $c = $skor && !empty($skor['catatan']) ? $skor['catatan'] : '-';

                        echo "<tr>
                            <td class='center'>{$no}</td>
                            <td><b>{$p['nama']}</b></td>
                            <td class='center'>{$p['kategori']}</td>
                            <td class='center'>{$m}</td>
                            <td class='center'>{$v}</td>
                            <td class='center'>{$a}</td>
                            <td class='center'>{$t}</td>
                            <td><small>{$c}</small></td>
                        </tr>";
                        $no++;
                    }
                    ?>
                </table>
                <div style="float:right; text-align:center; width:250px; margin-top:30px;">
                    Sampit, <?php echo date_i18n('d F Y'); ?><br>Dewan Juri<br><br><br><br>
                    <b>( <?php echo $nama_juri; ?> )</b>
                </div>
                <div style="clear:both;"></div>
            </div>
            <?php
        }
        ?>
    </body></html>
    <?php
    die();
}


/* * ==========================================================
 * 5. SHORTCODE FRONTEND PORTAL JURI ELEMENTOR [portal_juri_adzan]
 * ==========================================================
 */
add_shortcode('portal_juri_adzan', 'ksk_render_portal_juri_shortcode');
function ksk_render_portal_juri_shortcode() {
    if (!is_user_logged_in()) {
        return '<div style="background:#fee2e2; border-left:5px solid #ef4444; padding:20px; border-radius:8px; text-align:center; max-width:800px; margin:20px auto;"><h4 style="color:#b91c1c; margin:0 0 10px;">Akses Terkunci 🔒</h4><p>Anda belum login. Silakan masuk menggunakan akun juri.</p><a href="'.esc_url(wp_login_url(get_permalink())).'" style="display:inline-block; background:#ef4444; color:#fff; padding:10px 20px; border-radius:5px; text-decoration:none; font-weight:bold; margin-top:10px;">Log In Portal Juri</a></div>';
    }

    $current_user = wp_get_current_user();
    $juri_name = esc_html($current_user->display_name);

    ob_start();
    ?>
    <style>
        .portal-juri-container { max-width: 900px; margin: 20px auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; border-radius: 20px; border: 1px solid #e2e8f0; padding: 30px; color: #1e293b; }
        .portal-header { text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 20px; margin-bottom: 25px; }
        .portal-header h2 { color: #065f46; font-weight: 900; margin: 0 0 10px 0; font-size: 28px; text-transform: uppercase; }
        .portal-header p { margin: 0; color: #64748b; }
        .portal-header .badge-juri { display: inline-block; background: #10b981; color: #fff; padding: 5px 15px; border-radius: 50px; font-weight: bold; font-size: 14px; margin-bottom: 10px; }
        
        .juri-welcome-box { background: #fff; border: 1px solid #10b981; border-left: 5px solid #065f46; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .juri-welcome-box h3 { margin: 0 0 15px 0; color: #065f46; font-size: 20px; font-weight: 800; display:flex; align-items:center; gap:8px;}
        .juri-welcome-box p { margin: 0 0 10px 0; color: #334155; font-size: 15px; line-height: 1.5; }
        .juri-welcome-box ul { margin: 0 0 15px 0; padding-left: 20px; color: #334155; font-size: 14px; }
        .juri-welcome-box li { margin-bottom: 8px; line-height: 1.4; }

        .btn-cetak-wrapper { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 25px; }
        .btn-cetak { display: inline-flex; align-items: center; background: #0f172a; color: #fff !important; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; transition: 0.3s; border: 2px solid #0f172a; }
        .btn-cetak:hover { background: transparent; color: #0f172a !important; }
        .btn-cetak.btn-outline { background: transparent; color: #0f172a !important; }
        .btn-cetak.btn-outline:hover { background: #f1f5f9; }

        .msg-box { background: #fee2e2; border-left: 5px solid #ef4444; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .msg-box h4 { color: #b91c1c; margin: 0 0 10px 0; }
        .msg-box a { display: inline-block; margin-top: 10px; background: #ef4444; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; }
        
        .grid-peserta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media(max-width: 768px) { .grid-peserta { grid-template-columns: 1fr; } }
        .card-peserta { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: 0.3s; position: relative; }
        .card-peserta:hover { box-shadow: 0 10px 20px rgba(6, 95, 70, 0.1); border-color: #10b981; transform: translateY(-3px); }
        .card-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .card-head h3 { margin: 0; font-size: 18px; color: #065f46; font-weight: 800; line-height: 1.3; }
        .kat-badge { background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .card-actions { margin-bottom: 15px; }
        .btn-video { display: inline-flex; align-items: center; justify-content: center; width: 100%; background: #f1f5f9; color: #334155; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; border: 1px solid #cbd5e1; transition: 0.2s; }
        .btn-video:hover { background: #e2e8f0; color: #0f172a; }
        .skor-box { display: flex; justify-content: space-between; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 15px; }
        .skor-item { text-align: center; width: 50%; }
        .skor-item:first-child { border-right: 1px solid #cbd5e1; }
        .skor-label { display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; }
        .skor-value { display: block; font-size: 22px; font-weight: 900; color: #1e293b; }
        .skor-value.has-nilai { color: #10b981; }
        .btn-nilai { display: block; width: 100%; background: #f59e0b; color: #fff; border: none; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 800; cursor: pointer; transition: 0.2s; text-align: center; }
        .btn-nilai:hover { background: #d97706; }
        .btn-nilai.edit-mode { background: #10b981; }
        .btn-nilai.edit-mode:hover { background: #059669; }

        .juri-modal-overlay { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .juri-modal-box { background: #fff; width: 90%; max-width: 450px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); animation: popUp 0.3s ease-out; }
        @keyframes popUp { from {transform: scale(0.9); opacity:0;} to {transform: scale(1); opacity:1;} }
        .juri-modal-header { background: linear-gradient(135deg, #065f46 0%, #10b981 100%); color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .juri-modal-header h3 { margin: 0; font-size: 18px; }
        .juri-modal-close { background: none; border: none; color: #fff; font-size: 28px; cursor: pointer; line-height: 1; }
        .juri-modal-body { padding: 25px; }
        .j-form-group { margin-bottom: 15px; background: #f8fafc; padding: 10px 15px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .j-form-label { width: 65%; font-size: 14px; font-weight: 700; color: #334155; }
        .j-form-label span { font-size: 11px; font-weight: normal; color: #64748b; display: block; }
        .j-form-input { width: 30%; }
        .j-form-input input { width: 100%; padding: 10px; text-align: center; font-size: 18px; font-weight: 800; border: 2px solid #cbd5e1; border-radius: 8px; }
        .j-form-input input:focus { border-color: #10b981; outline: none; }
        .j-form-catatan textarea { width: 100%; padding: 12px; border: 2px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; height: 80px; resize: none; margin-bottom: 15px;}
        .kalkulator-box { text-align: center; background: #ecfdf5; border: 2px dashed #10b981; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .kalkulator-box span { font-size: 12px; color: #047857; font-weight: 700; text-transform: uppercase; }
        .kalkulator-box div { font-size: 36px; font-weight: 900; color: #065f46; line-height: 1; margin-top: 5px; }
        .btn-submit-nilai { width: 100%; background: #065f46; color: white; padding: 16px; border: none; border-radius: 50px; font-size: 18px; font-weight: 800; cursor: pointer; transition: 0.3s; }
        .btn-submit-nilai:hover { background: #047857; transform: translateY(-2px); }
        .loader-ui { text-align: center; padding: 50px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top: 4px solid #10b981; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>

    <div class="portal-juri-container">
        <div class="portal-header">
            <span class="badge-juri">🗝️ Akses Khusus Ustadz/Juri</span>
            <h2>Portal Penilaian Adzan</h2>
            <p>Sistem Rekapitulasi Otomatis Multi-Juri.</p>
        </div>

        <div class="juri-welcome-box">
            <h3>Selamat bertugas, <?php echo $juri_name; ?> 👋</h3>
            <p>Terima kasih atas partisipasi Anda sebagai Dewan Juri Lomba Adzan Al Karim. Berikut adalah kriteria pembobotan penilaian:</p>
            <ul>
                <li><strong>🗣️ Makhraj & Tajwid (50%):</strong> Kefasihan pengucapan huruf (Makhraj) dan ketepatan kaidah (Tajwid).</li>
                <li><strong>🎵 Vokal & Irama (40%):</strong> Kemerduan suara, kestabilan nafas, serta keindahan irama.</li>
                <li><strong>✨ Adab & Penampilan (10%):</strong> Kekhusyukan, kerapian pakaian, dan kesopanan dalam video.</li>
            </ul>
            <p style="margin-bottom:0; font-size:12px; color:#ef4444; font-weight:bold;">* Silakan klik "Beri Penilaian". Sistem akan mengalkulasi total skor secara otomatis.</p>
        </div>

        <!-- Tombol Cetak Dokumen -->
        <div class="btn-cetak-wrapper">
            <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=ksk_cetak_berita_acara')); ?>" target="_blank" class="btn-cetak">🖨️ Cetak Berita Acara (Juara)</a>
            <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=ksk_cetak_rekap_juri')); ?>" target="_blank" class="btn-cetak btn-outline">📑 Cetak Rekap Nilai Juri</a>
        </div>

        <div id="container-peserta">
            <div class="loader-ui">
                <div class="spinner"></div>
                <p style="color:#64748b; font-weight:600;">Memuat Data Peserta Lomba...</p>
            </div>
        </div>
    </div>

    <div id="modalPenilaianJuriShortcode" class="juri-modal-overlay">
        <div class="juri-modal-box">
            <div class="juri-modal-header">
                <h3><span id="modal_nama_peserta_sc">Nama Peserta</span></h3>
                <button class="juri-modal-close" onclick="tutupModalJuriSC()">&times;</button>
            </div>
            <div class="juri-modal-body">
                <form id="formNilaiJuriSC" onsubmit="simpanNilaiFrontendSC(event)">
                    <input type="hidden" id="modal_post_id_sc">
                    
                    <div class="j-form-group">
                        <div class="j-form-label">Makhraj & Tajwid <span>(Bobot 50% | Maks 100)</span></div>
                        <div class="j-form-input"><input type="number" id="inp_makhraj_sc" min="0" max="100" required oninput="kalkulasiOtomatisSC()"></div>
                    </div>
                    
                    <div class="j-form-group">
                        <div class="j-form-label">Vokal & Irama <span>(Bobot 40% | Maks 100)</span></div>
                        <div class="j-form-input"><input type="number" id="inp_vokal_sc" min="0" max="100" required oninput="kalkulasiOtomatisSC()"></div>
                    </div>

                    <div class="j-form-group">
                        <div class="j-form-label">Adab & Penampilan <span>(Bobot 10% | Maks 100)</span></div>
                        <div class="j-form-input"><input type="number" id="inp_adab_sc" min="0" max="100" required oninput="kalkulasiOtomatisSC()"></div>
                    </div>

                    <div class="kalkulator-box">
                        <span>Skor Total Anda</span>
                        <div id="display_skor_total_sc">0.00</div>
                    </div>

                    <div class="j-form-catatan">
                        <textarea id="inp_catatan_sc" placeholder="Catatan/Saran untuk peserta (Opsional)..."></textarea>
                    </div>

                    <button type="submit" id="btnSubmitNilaiSC" class="btn-submit-nilai">💾 Kunci & Simpan Nilai</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const ajaxurl_sc = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

        document.addEventListener('DOMContentLoaded', muatDataJuriSC);

        async function muatDataJuriSC() {
            const container = document.getElementById('container-peserta');
            try {
                const formData = new FormData();
                formData.append('action', 'ksk_get_data_juri_frontend');
                
                const response = await fetch(ajaxurl_sc, { method: 'POST', body: formData, credentials: 'same-origin' });
                const responseText = await response.text();
                
                if (responseText === '0' || responseText === '-1') {
                    container.innerHTML = `<div class="msg-box"><h4>Sistem Belum Siap (Error 0) 🔒</h4><p>WordPress menolak permintaan data. Pastikan Snippet Anda diatur ke "Run Everywhere".</p></div>`;
                    return;
                }

                const result = JSON.parse(responseText);

                if (!result.success) {
                    container.innerHTML = `<div class="msg-box"><h4>Akses Terkunci 🔒</h4><p>${result.data}</p><a href="<?php echo esc_url(wp_login_url()); ?>?redirect_to=${encodeURIComponent(window.location.href)}">Log In Portal Juri</a></div>`;
                    return;
                }

                const peserta = result.data;
                if (peserta.length === 0) {
                    container.innerHTML = `<div style="text-align:center; padding:30px; color:#64748b; font-weight:600;">Belum ada peserta yang Lunas/Diverifikasi Panitia.</div>`;
                    return;
                }

                let htmlCards = '<div class="grid-peserta">';
                peserta.forEach(p => {
                    const nilaiSaya = p.nilai_saya ? parseFloat(p.nilai_saya.total).toFixed(2) : '-';
                    const hasNilai = p.nilai_saya ? 'has-nilai' : '';
                    const btnClass = p.nilai_saya ? 'btn-nilai edit-mode' : 'btn-nilai';
                    const btnText = p.nilai_saya ? '✎ Revisi Nilai' : '⭐ Beri Penilaian';
                    
                    const dataJson = encodeURIComponent(JSON.stringify({
                        id: p.id, nama: p.nama,
                        makhraj: p.nilai_saya ? p.nilai_saya.makhraj : '',
                        vokal: p.nilai_saya ? p.nilai_saya.vokal : '',
                        adab: p.nilai_saya ? p.nilai_saya.adab : '',
                        catatan: p.nilai_saya ? p.nilai_saya.catatan : ''
                    }));

                    const videoHtml = p.link_video 
                        ? `<a href="${p.link_video}" target="_blank" class="btn-video">▶ Simak Video Karya</a>`
                        : `<span class="btn-video" style="background:#fee2e2; color:#ef4444; border-color:#fca5a5; cursor:not-allowed;">Video Belum Ada</span>`;

                    htmlCards += `
                        <div class="card-peserta">
                            <div class="card-head">
                                <h3>${p.nama}</h3>
                                <span class="kat-badge">Kat ${p.kategori}</span>
                            </div>
                            <div class="card-actions">${videoHtml}</div>
                            <div class="skor-box">
                                <div class="skor-item"><span class="skor-label">Nilai Anda</span><span class="skor-value ${hasNilai}">${nilaiSaya}</span></div>
                                <div class="skor-item"><span class="skor-label">Rata-rata (${p.jumlah_juri} Juri)</span><span class="skor-value">${p.rata_rata > 0 ? p.rata_rata : '-'}</span></div>
                            </div>
                            <button onclick="bukaModalJuriSC('${dataJson}')" class="${btnClass}">${btnText}</button>
                        </div>
                    `;
                });
                htmlCards += '</div>';
                container.innerHTML = htmlCards;

            } catch (error) {
                container.innerHTML = `<div class="msg-box">Terjadi kesalahan pemrosesan data struktur di server web.</div>`;
            }
        }

        function bukaModalJuriSC(dataEncoded) {
            const data = JSON.parse(decodeURIComponent(dataEncoded));
            document.getElementById('modal_post_id_sc').value = data.id;
            document.getElementById('modal_nama_peserta_sc').innerText = data.nama;
            document.getElementById('inp_makhraj_sc').value = data.makhraj;
            document.getElementById('inp_vokal_sc').value = data.vokal;
            document.getElementById('inp_adab_sc').value = data.adab;
            document.getElementById('inp_catatan_sc').value = data.catatan || '';
            kalkulasiOtomatisSC();
            document.getElementById('modalPenilaianJuriShortcode').style.display = 'flex';
        }

        function tutupModalJuriSC() { document.getElementById('modalPenilaianJuriShortcode').style.display = 'none'; }

        function kalkulasiOtomatisSC() {
            let m = parseFloat(document.getElementById('inp_makhraj_sc').value) || 0;
            let v = parseFloat(document.getElementById('inp_vokal_sc').value) || 0;
            let a = parseFloat(document.getElementById('inp_adab_sc').value) || 0;
            if(m > 100) document.getElementById('inp_makhraj_sc').value = 100;
            if(v > 100) document.getElementById('inp_vokal_sc').value = 100;
            if(a > 100) document.getElementById('inp_adab_sc').value = 100;
            m = parseFloat(document.getElementById('inp_makhraj_sc').value) || 0;
            v = parseFloat(document.getElementById('inp_vokal_sc').value) || 0;
            a = parseFloat(document.getElementById('inp_adab_sc').value) || 0;
            let total = (m * 0.5) + (v * 0.4) + (a * 0.1);
            document.getElementById('display_skor_total_sc').innerText = total.toFixed(2);
        }

        async function simpanNilaiFrontendSC(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitNilaiSC');
            btn.innerHTML = "Menyimpan... ⌛"; btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'ksk_simpan_nilai_juri'); 
            formData.append('post_id', document.getElementById('modal_post_id_sc').value);
            formData.append('makhraj', document.getElementById('inp_makhraj_sc').value);
            formData.append('vokal', document.getElementById('inp_vokal_sc').value);
            formData.append('adab', document.getElementById('inp_adab_sc').value);
            formData.append('catatan', document.getElementById('inp_catatan_sc').value);
            formData.append('total', document.getElementById('display_skor_total_sc').innerText);

            try {
                const response = await fetch(ajaxurl_sc, { method: 'POST', body: formData, credentials: 'same-origin' });
                const result = await response.json();
                if(result.success) {
                    tutupModalJuriSC();
                    document.getElementById('container-peserta').innerHTML = `<div class="loader-ui"><div class="spinner"></div><p style="color:#10b981; font-weight:600;">Nilai Tersimpan! Menyegarkan data...</p></div>`;
                    setTimeout(muatDataJuriSC, 800);
                } else {
                    alert('Gagal menyimpan nilai: ' + result.data);
                }
            } catch (err) { alert('Terjadi kesalahan jaringan.');
            } finally { btn.innerHTML = "💾 Kunci & Simpan Nilai"; btn.disabled = false; }
        }
    </script>
    <?php
    return ob_get_clean();
}