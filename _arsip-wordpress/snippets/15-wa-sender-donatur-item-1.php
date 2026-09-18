<?php
// SNIPPET WordPress #15: WA Sender Donatur (Item 1)
// scope: global | status: nonaktif | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// Mencegah akses langsung ke file
if ( ! defined( 'ABSPATH' ) ) exit;

// =========================================================
// 1. REGISTRASI MENU DASHBOARD
// =========================================================
add_action( 'admin_menu', 'wa_donatur_register_menu' );

function wa_donatur_register_menu() {
    add_menu_page('Modul WhatsApp Donatur', 'WA Donatur', 'manage_options', 'wa-modul-main', 'wa_donatur_main_callback', 'dashicons-whatsapp', 25);
    add_submenu_page('wa-modul-main', 'Dashboard WA', 'Dashboard', 'manage_options', 'wa-modul-main', 'wa_donatur_main_callback');
    add_submenu_page('wa-modul-main', 'Data Donatur', 'Data Donatur', 'manage_options', 'wa-modul-donatur', 'wa_donatur_data_callback');
    add_submenu_page('wa-modul-main', 'Riwayat Broadcast', 'Riwayat & Antrean', 'manage_options', 'wa-modul-riwayat', 'wa_donatur_riwayat_callback');
    add_submenu_page('wa-modul-main', 'Template Pesan', 'Template Teks', 'manage_options', 'wa-modul-template', 'wa_donatur_template_callback');
    add_submenu_page('wa-modul-main', 'Setting Integrasi API', 'Setting Integrasi', 'manage_options', 'wa-modul-setting', 'wa_donatur_setting_callback');
}

// =========================================================
// 2. CALLBACK HALAMAN: DASHBOARD UTAMA
// =========================================================
function wa_donatur_main_callback() {
    echo '<div class="wrap"><h1>Dashboard Modul WhatsApp</h1>';
    echo '<p>Selamat datang di panel kontrol WhatsApp API untuk laporan Musholla Al-Karim.</p>';
    echo '<div class="card"><p>Nantinya, statistik pengiriman pesan dan status koneksi API akan ditampilkan di sini.</p></div></div>';
}

// =========================================================
// 3. FUNGSI HELPER FORMAT NOMOR HP
// =========================================================
function wa_format_nomor_hp($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor); 
    if (substr($nomor, 0, 1) == '0') {
        $nomor = '62' . substr($nomor, 1);
    }
    return $nomor;
}

// =========================================================
// 4. CALLBACK HALAMAN: DATA DONATUR (Manual & CSV)
// =========================================================
function wa_donatur_data_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wa_donatur';

    // --- PROSES SIMPAN MANUAL ---
    if (isset($_POST['submit_manual']) && check_admin_referer('simpan_donatur_manual')) {
        $nama = sanitize_text_field($_POST['nama_donatur']);
        $nomor = sanitize_text_field($_POST['nomor_wa']);
        $nomor_format = wa_format_nomor_hp($nomor);
        
        if (!empty($nama) && !empty($nomor_format)) {
            $wpdb->insert($table_name, array(
                'nama' => $nama,
                'nomor_wa' => $nomor_format,
                'is_subscribed' => 1
            ));
            echo '<div class="notice notice-success is-dismissible"><p>Data donatur berhasil ditambahkan!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Nama dan Nomor WA wajib diisi.</p></div>';
        }
    }

    // --- PROSES IMPORT CSV ---
    if (isset($_POST['submit_csv']) && check_admin_referer('simpan_donatur_csv')) {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $row = 0;
                $success_count = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($row > 0) { 
                        $nama = sanitize_text_field($data[0]);
                        $nomor = isset($data[1]) ? wa_format_nomor_hp($data[1]) : '';
                        
                        if (!empty($nama) && !empty($nomor)) {
                            $wpdb->insert($table_name, array(
                                'nama' => $nama,
                                'nomor_wa' => $nomor,
                                'is_subscribed' => 1
                            ));
                            $success_count++;
                        }
                    }
                    $row++;
                }
                fclose($handle);
                echo '<div class="notice notice-success is-dismissible"><p>'.$success_count.' data berhasil diimpor dari CSV!</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Silakan pilih file CSV terlebih dahulu.</p></div>';
        }
    }

    // --- TAMPILAN HALAMAN (UI) ---
    echo '<div class="wrap">';
    echo '<h1>Data Donatur & Kontak</h1>';
    echo '<div style="display:flex; gap: 20px; margin-top: 20px;">';
    
    // Form Input Manual
    echo '<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">';
    echo '<h3>Tambah Manual</h3>';
    echo '<form method="post" action="">';
    wp_nonce_field('simpan_donatur_manual');
    echo '<p><label>Nama Donatur</label><br><input type="text" name="nama_donatur" required style="width:100%;"></p>';
    echo '<p><label>Nomor WhatsApp (Cth: 08123456789)</label><br><input type="text" name="nomor_wa" required style="width:100%;"></p>';
    echo '<p><input type="submit" name="submit_manual" class="button button-primary" value="Simpan Data"></p>';
    echo '</form></div>';

    // Form Import CSV
    echo '<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">';
    echo '<h3>Import via CSV</h3>';
    echo '<p>Pastikan format file CSV memiliki 2 kolom (Kolom 1: <b>Nama</b>, Kolom 2: <b>Nomor WA</b>). Baris pertama akan dibaca sebagai judul kolom (Header) dan tidak akan diimpor.</p>';
    echo '<form method="post" action="" enctype="multipart/form-data">';
    wp_nonce_field('simpan_donatur_csv');
    echo '<p><input type="file" name="csv_file" accept=".csv" required></p>';
    echo '<p><input type="submit" name="submit_csv" class="button button-secondary" value="Import File CSV"></p>';
    echo '</form></div>';
    echo '</div>'; 

    // --- TABEL DATA DONATUR ---
    echo '<h2 style="margin-top:40px;">Daftar Kontak Tersimpan</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Nama Donatur</th><th>Nomor WA</th><th>Status Broadcast</th><th>Tanggal Ditambahkan</th></tr></thead>';
    echo '<tbody>';
    
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 50");
    if ($results) {
        foreach ($results as $row) {
            $status = $row->is_subscribed ? '<span style="color:green; font-weight:bold;">Aktif</span>' : '<span style="color:red;">Opt-Out</span>';
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->nama) . '</td>';
            echo '<td>' . esc_html($row->nomor_wa) . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($row->tanggal_dibuat) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">Belum ada data donatur.</td></tr>';
    }
    
    echo '</tbody></table></div>';
}

// =========================================================
// 5. CALLBACK HALAMAN: MESIN BROADCAST & ANTREAN
// =========================================================
function wa_donatur_riwayat_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wa_donatur';

    $template = get_option('wa_donatur_template', '');
    $donaturs = $wpdb->get_results("SELECT id, nama, nomor_wa FROM $table_name WHERE is_subscribed = 1");
    
    echo '<div class="wrap"><h1>Mesin Broadcast Laporan</h1>';
    
    if (empty($template)) {
        echo '<div class="notice notice-warning"><p>Template pesan masih kosong. Silakan isi di menu <b>Template Pesan</b> terlebih dahulu.</p></div></div>';
        return;
    }
    
    if (empty($donaturs)) {
        echo '<div class="notice notice-warning"><p>Belum ada donatur aktif untuk di-broadcast. Silakan tambahkan di menu <b>Data Donatur</b>.</p></div></div>';
        return;
    }

    echo '<div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:5px; max-width:800px; margin-top:20px;">';
    echo '<p>Total donatur aktif: <b>' . count($donaturs) . '</b> kontak.</p>';
    echo '<p style="color:#d63638;"><b>Perhatian:</b> Biarkan halaman ini tetap terbuka selama proses broadcast berlangsung. Sistem menggunakan jeda acak (5-10 detik) per pesan untuk menghindari blokir WhatsApp.</p>';
    echo '<button id="btn-start-broadcast" class="button button-primary button-large">Mulai Broadcast Sekarang</button>';
    
    echo '<div id="broadcast-progress-container" style="display:none; margin-top:30px;">';
    echo '<h4>Progres Pengiriman: <span id="progress-text">0 / ' . count($donaturs) . '</span></h4>';
    echo '<progress id="broadcast-progress" value="0" max="' . count($donaturs) . '" style="width:100%; height:25px;"></progress>';
    
    echo '<h4 style="margin-top:20px;">Terminal Log (Status Pengiriman):</h4>';
    echo '<div id="broadcast-log" style="background:#1e1e1e; color:#0f0; padding:15px; height:300px; overflow-y:auto; font-family:monospace; border-radius:4px; font-size:14px;"></div>';
    echo '</div></div>';

    $donatur_json = json_encode($donaturs);
    $nonce = wp_create_nonce("broadcast_wa_nonce");
    ?>
    
    <script>
    jQuery(document).ready(function($) {
        var donaturs = <?php echo $donatur_json; ?>;
        var total = donaturs.length;
        var current = 0;

        $('#btn-start-broadcast').on('click', function() {
            if(!confirm('Yakin ingin memulai pengiriman pesan massal ke ' + total + ' donatur sekarang?')) return;
            
            $(this).prop('disabled', true).text('Sedang Memproses...');
            $('#broadcast-progress-container').fadeIn();
            $('#broadcast-log').append('<p style="color:#fff;">[SISTEM] Memulai antrean broadcast...</p>');
            
            processQueue(); 
        });

        function processQueue() {
            if (current >= total) {
                $('#broadcast-log').append('<p style="color:#0f0;">[SELESAI] Alhamdulillah, semua pesan telah diproses!</p>');
                $('#btn-start-broadcast').text('Tugas Selesai').prop('disabled', true);
                scrollToBottom();
                return;
            }

            var donatur = donaturs[current];
            var delay = Math.floor(Math.random() * (10000 - 5000 + 1) + 5000); 

            $('#broadcast-log').append('<p style="color:#fff;">[MENGIRIM] Menyiapkan pesan ke: ' + donatur.nama + ' (' + donatur.nomor_wa + ')...</p>');
            scrollToBottom();

            $.ajax({
                url: ajaxurl, 
                type: 'POST',
                data: {
                    action: 'send_wa_broadcast_ajax',
                    nama: donatur.nama,
                    nomor_wa: donatur.nomor_wa,
                    security: '<?php echo $nonce; ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#broadcast-log').append('<p style="color:#0f0;">[BERHASIL] Pesan terkirim ke Bapak/Ibu ' + donatur.nama + '</p>');
                    } else {
                        $('#broadcast-log').append('<p style="color:#f00;">[GAGAL] ' + donatur.nama + ' - ' + response.data + '</p>');
                    }
                },
                error: function() {
                    $('#broadcast-log').append('<p style="color:#f00;">[ERROR] Koneksi terputus saat mencoba mengirim ke ' + donatur.nama + '</p>');
                },
                complete: function() {
                    current++;
                    $('#progress-text').text(current + ' / ' + total);
                    $('#broadcast-progress').val(current);
                    scrollToBottom();
                    
                    if (current < total) {
                        $('#broadcast-log').append('<p style="color:#aaa;">[JEDA] Mengaktifkan pendingin. Menunggu ' + (delay/1000).toFixed(1) + ' detik sebelum target berikutnya...</p><br>');
                        setTimeout(processQueue, delay);
                    } else {
                        processQueue();
                    }
                }
            });
        }

        function scrollToBottom() {
            var log = $('#broadcast-log');
            log.scrollTop(log[0].scrollHeight);
        }
    });
    </script>
    <?php
    echo '</div>'; 
}

// =========================================================
// 6. FUNGSI AJAX: EKSEKUTOR PENGIRIMAN & GENERATOR KAS
// =========================================================
add_action('wp_ajax_send_wa_broadcast_ajax', 'ajax_send_wa_broadcast_handler');

function ajax_send_wa_broadcast_handler() {
    check_ajax_referer('broadcast_wa_nonce', 'security');

    $nama = sanitize_text_field($_POST['nama']);
    $nomor = sanitize_text_field($_POST['nomor_wa']);
    
    $template    = get_option('wa_donatur_template', '');
    $webhook_url = get_option('wa_dripsender_webhook');
    $api_token   = get_option('wa_dripsender_token');

    if (empty($template) || empty($webhook_url) || empty($api_token)) {
        wp_send_json_error('Pengaturan integrasi atau template kosong.');
    }

    // Mengambil dan Menghitung Data dari Google Script (Cache 5 Menit)
    $laporan_kas = get_transient('wa_laporan_kas_data');
    if (false === $laporan_kas) {
        $api_kas_url = 'https://script.google.com/macros/s/AKfycbzIDUg1PQd_Naiby7-uAzVKrTCiVZMOIqmXSz1aLyHHZZKWw_acsMk5zqrICg3yw2G1vw/exec';
        $response_kas = wp_remote_get($api_kas_url, array('timeout' => 15));
        
        $saldo_terkini = 0;
        $masuk_bulan_ini = 0;
        $keluar_bulan_ini = 0;

        if (!is_wp_error($response_kas)) {
            $body = wp_remote_retrieve_body($response_kas);
            $data = json_decode($body, true);
            
            $current_month = date('n'); 
            $current_year = date('Y');  

            if (is_array($data)) {
                foreach ($data as $row) {
                    $masuk = isset($row['masuk']) ? floatval($row['masuk']) : 0;
                    $keluar = isset($row['keluar']) ? floatval($row['keluar']) : 0;
                    $tanggal = isset($row['tanggal']) ? strtotime($row['tanggal']) : 0;
                    
                    $saldo_terkini += ($masuk - $keluar);
                    
                    if ($tanggal && date('n', $tanggal) == $current_month && date('Y', $tanggal) == $current_year) {
                        $masuk_bulan_ini += $masuk;
                        $keluar_bulan_ini += $keluar;
                    }
                }
            }
        }

        $laporan_kas = array(
            'saldo'  => number_format($saldo_terkini, 0, ',', '.'),
            'masuk'  => number_format($masuk_bulan_ini, 0, ',', '.'),
            'keluar' => number_format($keluar_bulan_ini, 0, ',', '.')
        );
        
        set_transient('wa_laporan_kas_data', $laporan_kas, 300);
    }

    // MENGUBAH PLACEHOLDER JADI NAMA ASLI & ANGKA KAS
    $pesan_final = str_replace(
        array('{{nama_donatur}}', '{{saldo_terkini}}', '{{masuk_bulan_ini}}', '{{keluar_bulan_ini}}'),
        array($nama, $laporan_kas['saldo'], $laporan_kas['masuk'], $laporan_kas['keluar']),
        $template
    );

    // Siapkan Payload ke Dripsender
    $body = array(
        'api_key' => $api_token,
        'phone'   => $nomor,
        'text'    => $pesan_final
    );

    $args = array(
        'body'        => json_encode($body),
        'timeout'     => 15,
        'blocking'    => true,
        'headers'     => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_token
        ),
    );

    // Tembak API
    $response = wp_remote_post($webhook_url, $args);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code == 200 || $http_code == 201) {
            wp_send_json_success('Terkirim');
        } else {
            wp_send_json_error('Ditolak API (Code: ' . $http_code . ')');
        }
    }
}

// =========================================================
// 7. CALLBACK HALAMAN: TEMPLATE PESAN (Dengan Data Kas)
// =========================================================
function wa_donatur_template_callback() {
    if (isset($_POST['simpan_template']) && check_admin_referer('simpan_template_wa')) {
        $template = sanitize_textarea_field($_POST['template_pesan']);
        update_option('wa_donatur_template', $template);
        echo '<div class="notice notice-success is-dismissible"><p>Template pesan berhasil disimpan!</p></div>';
    }

    $default_text = "Assalamu'alaikum Yth. Bapak/Ibu {{nama_donatur}},\n\nBerikut kami sampaikan ringkasan Laporan Kas Musholla Al-Karim:\n\n🔹 Saldo Kas Terkini: Rp {{saldo_terkini}}\n🔹 Pemasukan Bulan Ini: Rp {{masuk_bulan_ini}}\n🔹 Pengeluaran Bulan Ini: Rp {{keluar_bulan_ini}}\n\nUntuk melihat rincian riwayat transaksi, silakan kunjungi: https://mushollaalkarim.web.id/laporan-kas/nnTerima kasih atas infaq dan sedekah yang diberikan. Jazakumullah Khairan Katsiran.";
    $saved_template = get_option('wa_donatur_template', $default_text);

    echo '<div class="wrap"><h1>Template Pesan Laporan</h1>';
    echo '<form method="post" action="">';
    wp_nonce_field('simpan_template_wa');
    
    echo '<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 800px; margin-top: 15px;">';
    echo '<p>Ketik draf pesan laporan di sini. Gunakan kode variabel berikut yang akan otomatis berubah menjadi angka asli dari database kas saat dikirim:</p>';
    echo '<ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px;">';
    echo '<li><code>{{nama_donatur}}</code> : Memanggil nama donatur.</li>';
    echo '<li><code>{{saldo_terkini}}</code> : Memanggil total saldo akhir secara keseluruhan.</li>';
    echo '<li><code>{{masuk_bulan_ini}}</code> : Memanggil total pemasukan di bulan berjalan.</li>';
    echo '<li><code>{{keluar_bulan_ini}}</code> : Memanggil total pengeluaran di bulan berjalan.</li>';
    echo '</ul>';
    echo '<p><textarea name="template_pesan" rows="12" style="width:100%;">' . esc_textarea($saved_template) . '</textarea></p>';
    echo '<p><input type="submit" name="simpan_template" class="button button-primary" value="Simpan Template"></p>';
    echo '</div></form></div>';
}

// =========================================================
// 8. CALLBACK HALAMAN: SETTING INTEGRASI & TEST PING
// =========================================================
function wa_donatur_setting_callback() {
    if (isset($_POST['simpan_setting']) && check_admin_referer('simpan_setting_wa')) {
        update_option('wa_dripsender_webhook', sanitize_url($_POST['dripsender_webhook']));
        update_option('wa_dripsender_token', sanitize_text_field($_POST['dripsender_token']));
        echo '<div class="notice notice-success is-dismissible"><p>Pengaturan integrasi API Dripsender berhasil disimpan!</p></div>';
    }

    if (isset($_POST['test_ping']) && check_admin_referer('test_ping_wa')) {
        $test_number = sanitize_text_field($_POST['test_nomor']);
        $webhook_url = get_option('wa_dripsender_webhook');
        $api_token   = get_option('wa_dripsender_token');

        if (empty($webhook_url) || empty($api_token)) {
            echo '<div class="notice notice-error is-dismissible"><p>Gagal: Webhook URL atau Token API belum disetel atau disimpan.</p></div>';
        } elseif (empty($test_number)) {
            echo '<div class="notice notice-warning is-dismissible"><p>Silakan masukkan nomor WhatsApp tujuan untuk test ping.</p></div>';
        } else {
            $test_nomor_format = wa_format_nomor_hp($test_number);
            $test_message = "Assalamu'alaikum.\n\nIni adalah pesan uji coba (Test Ping) otomatis dari sistem website Musholla Al-Karim.\n\nKoneksi API berhasil!";

            $body = array(
                'api_key' => $api_token,
                'phone'   => $test_nomor_format,
                'text'    => $test_message
            );

            $args = array(
                'body'        => json_encode($body),
                'timeout'     => 15,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_token
                ),
            );

            $response = wp_remote_post($webhook_url, $args);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo '<div class="notice notice-error is-dismissible"><p>Koneksi Error: ' . esc_html($error_message) . '</p></div>';
            } else {
                $http_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                
                if ($http_code == 200 || $http_code == 201) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Berhasil!</strong> Pesan test terkirim ke ' . $test_nomor_format . '.<br>Respons server: <code>' . esc_html($response_body) . '</code></p></div>';
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p><strong>Terkoneksi, tapi server Dripsender menolak.</strong><br>HTTP Code: ' . $http_code . '<br>Respons: <code>' . esc_html($response_body) . '</code></p></div>';
                }
            }
        }
    }

    // Default ke API Send agar tidak terjadi salah alamat webhook lagi
    $saved_webhook = get_option('wa_dripsender_webhook', 'https://api.dripsender.id/send');
    $saved_token = get_option('wa_dripsender_token', '');

    echo '<div class="wrap"><h1>Setting Integrasi API Dripsender</h1>';
    echo '<div style="display:flex; gap: 20px; margin-top: 20px;">';
    
    // Panel 1
    echo '<div style="flex: 2; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">';
    echo '<h3>Kredensial API</h3>';
    echo '<form method="post" action="">';
    wp_nonce_field('simpan_setting_wa');
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Webhook URL</th><td><input type="url" name="dripsender_webhook" value="' . esc_attr($saved_webhook) . '" class="regular-text" style="width: 100%;" /></td></tr>';
    echo '<tr><th scope="row">API Token</th><td><input type="text" name="dripsender_token" value="' . esc_attr($saved_token) . '" class="regular-text" placeholder="Masukkan Token Dripsender" style="width: 100%;" /></td></tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="simpan_setting" class="button button-primary" value="Simpan Pengaturan"></p>';
    echo '</form></div>';

    // Panel 2
    echo '<div style="flex: 1; background: #f0f6fc; padding: 20px; border: 1px solid #c8d7e1; border-radius: 4px;">';
    echo '<h3>Test Koneksi (Ping)</h3>';
    echo '<p>Pastikan token dan URL sudah <b>disimpan</b> sebelum melakukan test ping.</p>';
    echo '<form method="post" action="">';
    wp_nonce_field('test_ping_wa');
    echo '<p><label>Nomor WhatsApp Tujuan:</label><br><input type="text" name="test_nomor" placeholder="Cth: 08123456789" required style="width:100%; margin-top:5px;"></p>';
    echo '<p><input type="submit" name="test_ping" class="button button-secondary" value="Kirim Pesan Uji Coba"></p>';
    echo '</form></div>';
    
    echo '</div></div>';
}
