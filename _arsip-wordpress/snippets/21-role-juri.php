<?php
// SNIPPET WordPress #21: ROLE JURI
// scope: global | status: nonaktif | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

/* * ==========================================================
 * 6. MODUL FRONT-END JURI (ROLE & AJAX FETCH)
 * ==========================================================
 */
// A. Membuat Role 'Ustadz' secara otomatis
add_action('init', 'ksk_tambah_role_ustadz');
function ksk_tambah_role_ustadz() {
    if (!get_role('ustadz')) {
        add_role('ustadz', 'Ustadz / Juri', array('read' => true));
    }
}

// B. Menyediakan Data Peserta Khusus Untuk Halaman Front-End Juri
add_action('wp_ajax_ksk_get_data_juri_frontend', 'ksk_get_data_juri_frontend');
add_action('wp_ajax_nopriv_ksk_get_data_juri_frontend', 'ksk_get_data_juri_frontend'); // Untuk deteksi belum login
function ksk_get_data_juri_frontend() {
    $user = wp_get_current_user();
    
    // Validasi Keamanan: Harus login dan punya role ustadz / administrator
    if (!is_user_logged_in() || (!in_array('ustadz', (array) $user->roles) && !in_array('administrator', (array) $user->roles))) {
        wp_send_json_error('Akses Ditolak. Silakan login menggunakan akun Juri (Role: Ustadz) untuk melihat data.');
    }

    $current_juri_id = $user->ID;

    // Ambil peserta yang sudah Lunas/Publish
    $peserta = get_posts(array(
        'post_type'      => 'pendaftar_adzan',
        'post_status'    => 'publish', 
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC'
    ));

    $data_peserta = array();

    foreach ($peserta as $p) {
        $kategori = get_post_meta($p->ID, 'kategori', true);
        $link_video = get_post_meta($p->ID, 'link_video', true);
        
        // Nilai dari Juri yang sedang login
        $nilai_saya_json = get_post_meta($p->ID, 'skor_juri_' . $current_juri_id, true);
        $nilai_saya = $nilai_saya_json ? json_decode($nilai_saya_json, true) : null;
        
        // Rata-rata dari Semua Juri
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

        $data_peserta[] = array(
            'id' => $p->ID,
            'nama' => $p->post_title,
            'kategori' => $kategori,
            'link_video' => $link_video,
            'nilai_saya' => $nilai_saya,
            'rata_rata' => $rata_rata_akhir,
            'jumlah_juri' => $jumlah_juri_menilai
        );
    }

    wp_send_json_success($data_peserta);
}