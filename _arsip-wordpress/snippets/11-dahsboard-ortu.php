<?php
// SNIPPET WordPress #11: Dahsboard Ortu
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// ====================================================================
// 11. SHORTCODE HALAMAN LAPORAN ORANG TUA (VERSI PROTECTED & BEAUTIFIED)
// ====================================================================
function simta_laporan_orangtua_shortcode($atts) {
    // 1. CEK LOGIN: Jika belum login, tampilkan pesan peringatan
    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding: 25px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; color: #856404; font-family: sans-serif;">
                    <span style="font-size: 30px; display: block; margin-bottom: 10px;">🔒</span>
                    Harap <strong>Login</strong> terlebih dahulu menggunakan akun orang tua untuk melihat laporan perkembangan santri.
                </div>';
    }

    ob_start();
    global $wpdb;
    $table_name = $wpdb->prefix . 'simta_perkembangan';
    
    // 2. IDENTIFIKASI USER
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    $nama_ortu = $current_user->display_name; // Mengambil nama akun yang login

    // Cek apakah user adalah Ustadz/Admin (memiliki hak edit post)
    $is_ustadz = current_user_can('edit_posts'); 

    $selected_santri_id = isset($_GET['santri_id']) ? intval($_GET['santri_id']) : 0;

    // 3. FILTER DATA SANTRI
    $args = array(
        'post_type'   => 'santri',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC'
    );

    // Jika BUKAN Ustadz (berarti orang tua), filter hanya santri milik mereka
    if (!$is_ustadz) {
        $args['meta_query'] = array(
            array(
                'key'     => '_simta_ortu_id',
                'value'   => $current_user_id,
                'compare' => '='
            )
        );
    }

    $santri_list = get_posts($args);

    // Jika orang tua tersebut login tapi belum ada anak yang dihubungkan
    if (empty($santri_list) && !$is_ustadz) {
        echo '<div style="text-align:center; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px; font-family: sans-serif;">
                <strong>Akses Terbatas:</strong> Belum ada data santri yang dihubungkan dengan akun Anda. Silakan hubungi Ustadz/Admin Musholla.
              </div>';
        return ob_get_clean();
    }

    // PINTAR: Jika orang tua hanya punya 1 anak, otomatis pilih anak tersebut
    if (count($santri_list) == 1 && $selected_santri_id == 0) {
        $selected_santri_id = $santri_list[0]->ID;
    }

    ?>
    <style>
        .simta-front-wrap { font-family: sans-serif; max-width: 100%; margin: 0 auto; }
        
        /* GAYA LAINNYA */
        .simta-search-box { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 30px; text-align: center; }
        .simta-front-input { padding: 10px 15px; font-size: 16px; border: 1px solid #ced4da; border-radius: 5px; width: 100%; max-width: 300px; margin-bottom: 10px; }
        .simta-btn-cari { padding: 10px 20px; font-size: 16px; background-color: #0073aa; color: #fff; border: none; border-radius: 5px; cursor: pointer; transition: 0.3s; }
        .simta-btn-cari:hover { background-color: #005177; }
        .simta-laporan-header { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #0073aa; }
        .simta-table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .simta-front-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .simta-front-table th { background-color: #f1f3f5; color: #333; font-weight: 600; padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; }
        .simta-front-table td { padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: top; }
        .simta-front-table tr:hover { background-color: #f8f9fa; }
        .simta-f-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-top: 4px; }
        .f-badge-blue { background: #e5f5fa; color: #0073aa; }
        .f-badge-green { background: #e1faea; color: #00a32a; }
        .f-badge-orange { background: #fdf2e3; color: #d67f05; }
        .f-badge-purple { background: #f4f0fa; color: #8a2be2; }
        .simta-f-skor-wrap { display: flex; gap: 5px; flex-wrap: wrap; }
        .simta-f-skor { background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 12px; border: 1px solid #ced4da; }
        .simta-f-skor b { color: #212529; }
        .simta-f-predikat { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; color: #fff; text-align: center; white-space: nowrap; }
        .simta-f-catatan { background: #fffbeb; border-left: 3px solid #fcd34d; padding: 8px 12px; font-size: 13px; font-style: italic; color: #45350f; margin: 0; }
        @media (max-width: 768px) {
            .simta-front-input { max-width: 100%; }
            .simta-front-table th, .simta-front-table td { padding: 10px; font-size: 14px; }
        }
    </style>

    <div class="simta-front-wrap">
        
        <?php if (count($santri_list) > 1 || $is_ustadz): ?>
        <div class="simta-search-box">
            <form method="GET" action="<?php echo esc_url(get_permalink()); ?>">
                <?php 
                foreach ($_GET as $key => $val) {
                    if ($key !== 'santri_id') {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '">';
                    }
                }
                ?>
                <h4 style="margin-top:0; margin-bottom: 10px; color:#333;">Pilih Data Santri</h4>
                <select name="santri_id" class="simta-front-input" required>
                    <option value="">-- Pilih Nama Ananda --</option>
                    <?php foreach ($santri_list as $s): ?>
                        <option value="<?php echo $s->ID; ?>" <?php selected($selected_santri_id, $s->ID); ?>>
                            <?php echo esc_html($s->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="simta-btn-cari">Lihat Laporan</button>
            </form>
        </div>
        <?php endif; ?>

        <?php
        // Cek Validitas Akses
        $is_valid_access = false;
        foreach ($santri_list as $s) {
            if ($s->ID == $selected_santri_id) {
                $is_valid_access = true;
                break;
            }
        }

        if ($selected_santri_id > 0 && $is_valid_access):
            $nama_santri = get_the_title($selected_santri_id);
            $riwayat = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE santri_id = %d ORDER BY tanggal DESC", 
                $selected_santri_id
            ));
        ?>
            
            <div class="simta-laporan-header">
                <h2 style="margin:0;">Buku Penghubung: <strong><?php echo esc_html($nama_santri); ?></strong></h2>
            </div>

            <div class="simta-table-responsive">
                <table class="simta-front-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 20%;">Materi</th>
                            <th style="width: 25%;">Skor & Detail</th>
                            <th style="width: 10%;">Predikat</th>
                            <th style="width: 30%;">Catatan Ustadz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($riwayat) {
                            foreach ($riwayat as $row) {
                                // Tentukan warna badge
                                $badge_class = 'f-badge-blue';
                                if ($row->kategori == "Hafalan Al-Qur'an") $badge_class = 'f-badge-green';
                                if ($row->kategori == "Latihan Iqro/Tilawati") $badge_class = 'f-badge-orange';
                                if ($row->kategori == "Tilawah Al-Qur'an") $badge_class = 'f-badge-purple';

                                // Label Kriteria
                                $lbl1 = "Makhraj"; $lbl2 = "Tajwid"; $lbl3 = "Lancar";
                                if ($row->kategori == "Latihan Iqro/Tilawati") {
                                    $lbl1 = "Tepat"; $lbl2 = "Makhraj"; 
                                }

                                // Predikat
                                $rata_rata = ($row->nilai_1 + $row->nilai_2 + $row->nilai_3) / 3;
                                $predikat = ''; $bg_predikat = '';
                                if ($rata_rata == 5) { $predikat = 'Mumtaz'; $bg_predikat = '#28a745'; } 
                                elseif ($rata_rata >= 4) { $predikat = 'Jayyid Jiddan'; $bg_predikat = '#17a2b8'; } 
                                elseif ($rata_rata >= 3) { $predikat = 'Jayyid'; $bg_predikat = '#0073aa'; } 
                                elseif ($rata_rata >= 2) { $predikat = 'Maqbul'; $bg_predikat = '#fd7e14'; } 
                                else { $predikat = 'Mardud'; $bg_predikat = '#d63638'; }

                                echo "<tr>";
                                echo "<td>" . date('d M Y', strtotime($row->tanggal)) . "<br><small style='color:#6c757d;'>" . date('H:i', strtotime($row->tanggal)) . "</small></td>";
                                echo "<td>
                                        <strong>{$row->kategori}</strong><br>
                                        <span class='simta-f-badge {$badge_class}'>{$row->detail_materi}</span>
                                      </td>";
                                echo "<td>
                                        <div class='simta-f-skor-wrap'>
                                            <span class='simta-f-skor'>{$lbl1}: <b>{$row->nilai_1}</b></span>
                                            <span class='simta-f-skor'>{$lbl2}: <b>{$row->nilai_2}</b></span>
                                            <span class='simta-f-skor'>{$lbl3}: <b>{$row->nilai_3}</b></span>
                                        </div>
                                      </td>";
                                echo "<td><span class='simta-f-predikat' style='background-color: {$bg_predikat};'>{$predikat}</span></td>";
                                
                                echo "<td>";
                                if (!empty($row->catatan_ustadz)) {
                                    echo "<div class='simta-f-catatan'>" . nl2br(esc_html($row->catatan_ustadz)) . "</div>";
                                } else {
                                    echo "<span style='color:#adb5bd; font-style:italic;'>- Tidak ada catatan -</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#6c757d;'>Belum ada data evaluasi untuk ananda " . esc_html($nama_santri) . ".</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($selected_santri_id > 0 && !$is_valid_access): ?>
             <div style="text-align:center; padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px;">
                Anda tidak memiliki akses ke data santri ini.
             </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('simta_laporan_belajar', 'simta_laporan_orangtua_shortcode');