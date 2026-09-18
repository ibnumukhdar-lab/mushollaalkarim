<?php
// SNIPPET WordPress #20: Penilaian Lomba Adzan
// scope: global | status: tidak aktif | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

/* * ==========================================================
 * 1. MEMBUAT MENU SUB-PAGE "PENILAIAN JURI"
 * ==========================================================
 */
add_action('admin_menu', 'ksk_add_juri_submenu');
function ksk_add_juri_submenu() {
    add_submenu_page(
        'edit.php?post_type=pendaftar_adzan', // Parent menu
        'Meja Penilaian Juri',                // Page title
        'Penilaian Juri',                     // Menu title
        'read',                               // Capability (akses minimal)
        'penilaian_juri_adzan',               // Menu slug
        'ksk_render_halaman_juri'             // Fungsi yang dirender
    );
}

/* * ==========================================================
 * 2. TAMPILAN HALAMAN PENILAIAN JURI (TABEL & MODAL)
 * ==========================================================
 */
function ksk_render_halaman_juri() {
    // Ambil ID User (Juri) yang sedang login
    $current_juri_id = get_current_user_id();

    // Ambil HANYA peserta yang sudah sah (Publish)
    $peserta = get_posts(array(
        'post_type'      => 'pendaftar_adzan',
        'post_status'    => 'publish', 
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC'
    ));

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline"><span class="dashicons dashicons-welcome-write-blog" style="font-size:28px; margin-top:5px; margin-right:10px;"></span> Meja Penilaian Juri Lomba Adzan</h1>';
    echo '<p style="font-size:15px; background:#fff; padding:15px; border-left:4px solid #10b981; box-shadow:0 1px 3px rgba(0,0,0,0.05);">Hanya menampilkan peserta yang sudah diverifikasi pembayarannya. <br><b>Rumus Sistem:</b> (Makhraj 50%) + (Vokal 40%) + (Adab 10%).</p>';

    echo '<table class="wp-list-table widefat fixed striped" style="margin-top:20px;">';
    echo '<thead><tr>';
    echo '<th style="width:5%;">No</th>';
    echo '<th style="width:15%;">Nama Anak</th>';
    echo '<th style="width:8%; text-align:center;">Kategori</th>';
    echo '<th style="width:10%; text-align:center;">Karya Video</th>';
    echo '<th style="width:15%; text-align:center;">Nilai & Catatan Anda</th>';
    echo '<th style="width:10%; text-align:center;">Aksi Juri</th>';
    echo '<th style="width:10%; text-align:center; background:#f0fdf4;">Skor Akhir<br><small>(Rata-rata Semua Juri)</small></th>';
    echo '</tr></thead><tbody>';

    if (empty($peserta)) {
        echo '<tr><td colspan="7" style="text-align:center; padding:20px;">Belum ada peserta yang diverifikasi panitia.</td></tr>';
    } else {
        $no = 1;
        foreach ($peserta as $p) {
            $kategori = get_post_meta($p->ID, 'kategori', true);
            $link_video = get_post_meta($p->ID, 'link_video', true);
            
            // Ambil data nilai DARI JURI INI SAJA
            $nilai_saya_json = get_post_meta($p->ID, 'skor_juri_' . $current_juri_id, true);
            $nilai_saya = $nilai_saya_json ? json_decode($nilai_saya_json, true) : null;
            
            // Hitung Rata-rata dari SEMUA juri
            $semua_meta = get_post_meta($p->ID);
            $total_akumulasi = 0;
            $jumlah_juri_menilai = 0;
            
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
            
            // Tombol Simak Video
            echo '<td style="text-align:center;">';
            if ($link_video) {
                echo '<a href="' . esc_url($link_video) . '" target="_blank" class="button button-primary"><span class="dashicons dashicons-video-alt3" style="margin-top:3px;"></span> Simak</a>';
            } else {
                echo '<span style="color:red;">Tidak ada link</span>';
            }
            echo '</td>';

            // Menampilkan Nilai & Catatan Juri yang sedang login
            echo '<td style="text-align:center;">';
            if ($nilai_saya) {
                echo '<strong style="color:#047857; font-size:16px;">' . $nilai_saya['total'] . '</strong><br>';
                if(!empty($nilai_saya['catatan'])) {
                    echo '<small style="color:#64748b; font-style:italic;" title="'.esc_attr($nilai_saya['catatan']).'">"'.wp_trim_words($nilai_saya['catatan'], 5, '...').'"</small>';
                }
            } else {
                echo '<span style="color:#ef4444; font-weight:600;">Belum Dinilai</span>';
            }
            echo '</td>';

            // Tombol Buka Modal Form Penilaian
            echo '<td style="text-align:center;">';
            $btn_text = $nilai_saya ? 'Edit Nilai' : 'Beri Penilaian';
            $makhraj_val = $nilai_saya ? $nilai_saya['makhraj'] : '';
            $vokal_val = $nilai_saya ? $nilai_saya['vokal'] : '';
            $adab_val = $nilai_saya ? $nilai_saya['adab'] : '';
            $catatan_val = $nilai_saya ? esc_attr($nilai_saya['catatan']) : '';
            
            echo '<button type="button" class="button" onclick="bukaModalNilai('.$p->ID.', \''.esc_js($p->post_title).'\', \''.$makhraj_val.'\', \''.$vokal_val.'\', \''.$adab_val.'\', \''.$catatan_val.'\')"><span class="dashicons dashicons-edit" style="margin-top:3px;"></span> '.$btn_text.'</button>';
            echo '</td>';

            // Rata-rata Semua Juri
            echo '<td style="text-align:center; background:#f0fdf4;">';
            if ($rata_rata_akhir > 0) {
                echo '<strong style="font-size:18px; color:#1e293b;">' . $rata_rata_akhir . '</strong><br>';
                echo '<small>('.$jumlah_juri_menilai.' Juri)</small>';
            } else {
                echo '-';
            }
            echo '</td>';

            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // MODAL FORM PENILAIAN (HTML & CSS)
    ?>
    <style>
        .juri-modal-overlay { display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); align-items:center; justify-content:center;}
        .juri-modal-box { background:#fff; width:90%; max-width:500px; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3);}
        .juri-modal-header { background:#065f46; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center;}
        .juri-modal-header h3 { margin:0; color:#fff;}
        .juri-modal-close { background:none; border:none; color:#fff; font-size:24px; cursor:pointer;}
        .juri-modal-body { padding:20px;}
        .juri-form-group { margin-bottom:15px; display:flex; align-items:center; justify-content:space-between;}
        .juri-form-group label { font-weight:600; width:60%;}
        .juri-form-group input { width:35%; text-align:center; font-size:16px;}
        .juri-form-catatan { margin-bottom:15px;}
        .juri-form-catatan label { font-weight:600; display:block; margin-bottom:5px;}
        .juri-form-catatan textarea { width:100%; height:80px;}
        .skor-preview { text-align:center; background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0;}
        .skor-preview span { font-size:30px; font-weight:900; color:#10b981; display:block;}
    </style>

    <div id="modalJuri" class="juri-modal-overlay">
        <div class="juri-modal-box">
            <div class="juri-modal-header">
                <h3>Form Penilaian: <span id="namaPesertaLabel"></span></h3>
                <button class="juri-modal-close" onclick="tutupModalNilai()">&times;</button>
            </div>
            <div class="juri-modal-body">
                <form id="formPenilaian" onsubmit="simpanPenilaian(event)">
                    <input type="hidden" id="post_id_peserta">
                    
                    <div class="juri-form-group">
                        <label>Makhraj & Tajwid (50%)<br><small style="font-weight:normal;">Rentang: 0-100</small></label>
                        <input type="number" id="skor_makhraj" min="0" max="100" required oninput="hitungPreview()">
                    </div>
                    <div class="juri-form-group">
                        <label>Vokal & Irama (40%)<br><small style="font-weight:normal;">Rentang: 0-100</small></label>
                        <input type="number" id="skor_vokal" min="0" max="100" required oninput="hitungPreview()">
                    </div>
                    <div class="juri-form-group">
                        <label>Adab & Penampilan (10%)<br><small style="font-weight:normal;">Rentang: 0-100</small></label>
                        <input type="number" id="skor_adab" min="0" max="100" required oninput="hitungPreview()">
                    </div>
                    
                    <div class="skor-preview">
                        <strong>Kalkulasi Total Sementara:</strong>
                        <span id="skor_total_preview">0.00</span>
                    </div>

                    <div class="juri-form-catatan">
                        <label>Catatan Juri (Opsional)</label>
                        <textarea id="catatan_juri" placeholder="Tulis masukan/evaluasi untuk peserta ini..."></textarea>
                    </div>

                    <button type="submit" id="btnSimpanNilai" class="button button-primary button-large" style="width:100%;">💾 Simpan Nilai</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function bukaModalNilai(post_id, nama, makhraj, vokal, adab, catatan) {
            document.getElementById('post_id_peserta').value = post_id;
            document.getElementById('namaPesertaLabel').innerText = nama;
            document.getElementById('skor_makhraj').value = makhraj;
            document.getElementById('skor_vokal').value = vokal;
            document.getElementById('skor_adab').value = adab;
            document.getElementById('catatan_juri').value = catatan;
            
            hitungPreview();
            document.getElementById('modalJuri').style.display = 'flex';
        }

        function tutupModalNilai() {
            document.getElementById('modalJuri').style.display = 'none';
        }

        function hitungPreview() {
            let m = parseFloat(document.getElementById('skor_makhraj').value) || 0;
            let v = parseFloat(document.getElementById('skor_vokal').value) || 0;
            let a = parseFloat(document.getElementById('skor_adab').value) || 0;
            
            // Rumus Bobot
            let total = (m * 0.5) + (v * 0.4) + (a * 0.1);
            document.getElementById('skor_total_preview').innerText = total.toFixed(2);
        }

        async function simpanPenilaian(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSimpanNilai');
            btn.innerHTML = "Menyimpan...";
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'ksk_simpan_nilai_juri');
            formData.append('post_id', document.getElementById('post_id_peserta').value);
            formData.append('makhraj', document.getElementById('skor_makhraj').value);
            formData.append('vokal', document.getElementById('skor_vokal').value);
            formData.append('adab', document.getElementById('skor_adab').value);
            formData.append('catatan', document.getElementById('catatan_juri').value);
            formData.append('total', document.getElementById('skor_total_preview').innerText);

            try {
                const response = await fetch(ajaxurl, { method: 'POST', body: formData });
                const result = await response.json();
                if(result.success) {
                    alert('Nilai berhasil disimpan!');
                    location.reload(); // Refresh halaman untuk melihat update tabel
                } else {
                    alert('Gagal menyimpan nilai.');
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            } finally {
                btn.innerHTML = "💾 Simpan Nilai";
                btn.disabled = false;
            }
        }
    </script>
    <?php
    echo '</div>'; // Tutup div.wrap
}

/* * ==========================================================
 * 3. FUNGSI AJAX UNTUK MENYIMPAN NILAI KE DATABASE
 * ==========================================================
 */
add_action('wp_ajax_ksk_simpan_nilai_juri', 'ksk_ajax_simpan_nilai_juri');
function ksk_ajax_simpan_nilai_juri() {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $juri_id = get_current_user_id(); // ID Juri yang login

    if (!$post_id || !$juri_id) {
        wp_send_json_error('Data tidak valid.');
    }

    // Susun data menjadi array
    $data_nilai = array(
        'makhraj' => floatval($_POST['makhraj']),
        'vokal'   => floatval($_POST['vokal']),
        'adab'    => floatval($_POST['adab']),
        'total'   => floatval($_POST['total']),
        'catatan' => sanitize_textarea_field($_POST['catatan'])
    );

    // Simpan JSON ke Custom Field spesifik milik Juri tersebut
    update_post_meta($post_id, 'skor_juri_' . $juri_id, wp_json_encode($data_nilai));

    wp_send_json_success('Nilai tersimpan');
}