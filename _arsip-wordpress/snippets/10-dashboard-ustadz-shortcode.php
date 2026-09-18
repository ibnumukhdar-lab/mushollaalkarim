<?php
// SNIPPET WordPress #10: Dashboard Ustadz Shortcode
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// ====================================================================
// 11. SHORTCODE ELEMENTOR [simta_perkembangan_ustadz] (KARTU SANTRI + POPUP MODAL)
// ====================================================================
function simta_shortcode_ustadz_cards() {
    if (!current_user_can('edit_posts')) {
        return '<div style="padding:15px; background:#fff2f2; color:#d63638; border-radius:6px; font-weight:600;">Akses khusus Ustadz. Silakan login terlebih dahulu.</div>';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'simta_perkembangan';
    $base_url = strtok($_SERVER["REQUEST_URI"], '?');

    ob_start();

    // PROSES SIMPAN DATA VIA POP-UP FRONTEND
    if (isset($_POST['simta_frontend_submit']) && check_admin_referer('simta_front_save_action', 'simta_front_nonce')) {
        $current_user = wp_get_current_user();
        $santri_id = intval($_POST['modal_santri_id']);
        $kategori  = stripslashes(sanitize_text_field($_POST['kategori']));
        
        $detail_materi = '';
        if ($kategori == "Tilawah Al-Qur'an") {
            $detail_materi = 'Juz ' . sanitize_text_field($_POST['tilawah_juz']) . ' - Hal ' . sanitize_text_field($_POST['tilawah_halaman']);
        } elseif ($kategori == 'Latihan Iqro/Tilawati') {
            $detail_materi = 'Jilid ' . sanitize_text_field($_POST['iqro_jilid']) . ' - Hal ' . sanitize_text_field($_POST['iqro_halaman']);
        } elseif ($kategori == "Hafalan Al-Qur'an") {
            $detail_materi = 'Juz ' . sanitize_text_field($_POST['hafalan_juz']) . ' | QS. ' . sanitize_text_field($_POST['hafalan_surah']) . ' | Ayat: ' . sanitize_text_field($_POST['hafalan_ayat']);
        }

        $wpdb->insert($table_name, array(
            'santri_id'      => $santri_id,
            'kategori'       => $kategori,
            'detail_materi'  => $detail_materi,
            'nilai_1'        => intval($_POST['nilai_1']),
            'nilai_2'        => intval($_POST['nilai_2']),
            'nilai_3'        => intval($_POST['nilai_3']),
            'catatan_ustadz' => sanitize_textarea_field($_POST['catatan_ustadz']),
            'ustadz_nama'    => $current_user->display_name,
            'tanggal'        => current_time('mysql')
        ));

        echo '<div style="padding:15px; background:#e1faea; color:#00a32a; border-radius:6px; margin-bottom:20px; font-weight:600; border-left:4px solid #00a32a;">✓ Data perkembangan santri berhasil direkam ke database!</div>';
    }

    // AMBIL DATA SEMUA SANTRI UNTUK GRID KARTU
    $santri_list = get_posts(array('post_type' => 'santri', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));
    ?>

    <style>
        /* KOLOM PENCARIAN */
        .simta-search-wrapper { margin-bottom: 20px; }
        .simta-search-input { width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid #cbd5e0; font-size: 15px; box-sizing: border-box; transition: all 0.2s; background: #fff; }
        .simta-search-input:focus { border-color: #2271b1; outline: none; box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.2); }

        .ustadz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        .santri-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; justify-content: space-between; }
        .santri-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .santri-avatar { width: 60px; height: 60px; background: #edf2f7; color: #4a5568; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; margin: 0 auto 12px auto; border: 2px solid #cbd5e0; }
        .santri-name { font-size: 15px; font-weight: 700; color: #1a202c; margin-bottom: 15px; min-height: 40px; display: flex; align-items: center; justify-content: center; line-height: 1.3;}
        .btn-rekam { background: #2271b1; color: #fff; border: none; padding: 10px 14px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: pointer; width: 100%; transition: background 0.2s; margin-bottom: 15px; }
        .btn-rekam:hover { background: #135e96; }

        /* STYLE RIWAYAT MINI DI DALAM KARTU */
        .santri-history-wrapper { border-top: 1px dashed #e2e8f0; padding-top: 12px; text-align: left; }
        .history-title { font-size: 11px; font-weight: 700; color: #718096; text-transform: uppercase; margin-bottom: 8px; text-align: center; letter-spacing: 0.5px; }
        .history-list { list-style: none; padding: 0; margin: 0; font-size: 11px; color: #4a5568; }
        .history-list li { margin-bottom: 8px; border-bottom: 1px solid #f7fafc; padding-bottom: 6px; line-height: 1.3; }
        .history-list li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .hist-date { color: #a0aec0; font-weight: 600; font-size: 10px; }
        .hist-tag { font-weight: 700; color: #2d3748; }
        .no-history { font-size: 11px; color: #a0aec0; font-style: italic; margin: 5px 0 0 0; text-align: center; }

        /* TAMPILAN POPUP MODAL */
        .simta-modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;}
        .simta-modal-content { background: #fff; border-radius: 12px; max-width: 480px; width: 100%; max-height: 85vh; overflow-y: auto; padding: 25px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); position: relative; animation: simtaFadeIn 0.3s; }
        .close-modal { position: absolute; right: 20px; top: 15px; font-size: 28px; color: #a0aec0; cursor: pointer; }
        .close-modal:hover { color: #4a5568; }
        .modal-title { margin-top: 0; font-size: 18px; font-weight: 700; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 18px; color: #2d3748; text-align: left; }
        
        .modal-field { margin-bottom: 15px; display: flex; flex-direction: column; gap: 6px; text-align: left;}
        .modal-field label { font-weight: 600; font-size: 13px; color: #4a5568; }
        .m-input { width: 100%; border: 1px solid #cbd5e0; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-sizing: border-box; background:#fff; min-height: 38px;}
        .m-input:focus { border-color: #2271b1; outline: none; box-shadow: 0 0 0 1px #2271b1; }
        @keyframes simtaFadeIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
    </style>

    <div class="simta-search-wrapper">
        <input type="text" id="simta_search_santri" class="simta-search-input" placeholder="🔍 Cari nama santri...">
    </div>

    <div class="ustadz-grid">
        <?php if (!empty($santri_list)): foreach ($santri_list as $s): 
            $initial = strtoupper(substr($s->post_title, 0, 1));
            
            // QUERY AMBIL 3 RIWAYAT TERAKHIR ANAK INI
            $riwayat_anak = $wpdb->get_results($wpdb->prepare(
                "SELECT kategori, detail_materi, tanggal FROM $table_name WHERE santri_id = %d ORDER BY tanggal DESC LIMIT 3",
                $s->ID
            ));
            ?>
            <div class="santri-card">
                <div>
                    <div class="santri-avatar"><?php echo $initial; ?></div>
                    <div class="santri-name"><?php echo esc_html($s->post_title); ?></div>
                    
                    <button type="button" class="btn-rekam" onclick="openRekamModal(<?php echo $s->ID; ?>, '<?php echo esc_js($s->post_title); ?>')">
                        ➔ Rekam Progres
                    </button>
                </div>

                <div class="santri-history-wrapper">
                    <div class="history-title">⏱ 3 Riwayat Terakhir</div>
                    <?php if (!empty($riwayat_anak)): ?>
                        <ul class="history-list">
                            <?php foreach ($riwayat_anak as $r): 
                                $format_tgl = date('d/m/y', strtotime($r->tanggal));
                                ?>
                                <li>
                                    <span class="hist-date">[<?php echo $format_tgl; ?>]</span> 
                                    <span class="hist-tag"><?php echo esc_html($r->kategori); ?></span><br>
                                    <span style="color:#718096;"><?php echo esc_html($r->detail_materi); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-history">Belum ada riwayat tercatat</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p>Data santri tidak ditemukan.</p>
        <?php endif; ?>
    </div>

    <div id="popup_rekam_nilai" class="simta-modal">
        <div class="simta-modal-content">
            <span class="close-modal" onclick="closeRekamModal()">&times;</span>
            <h3 class="modal-title" id="m_title_santri">Rekam Progres Santri</h3>
            
            <form method="post" action="<?php echo esc_url($base_url); ?>">
                <?php wp_nonce_field('simta_front_save_action', 'simta_front_nonce'); ?>
                <input type="hidden" name="modal_santri_id" id="modal_santri_id" value="">

                <div class="modal-field">
                    <label>Materi Pelajaran</label>
                    <select name="kategori" id="m_kategori" class="m-input" required>
                        <option value="">-- Pilih Materi --</option>
                        <option value="Tilawah Al-Qur'an">Tilawah Al-Qur'an</option>
                        <option value="Latihan Iqro/Tilawati">Latihan Iqro/Tilawati</option>
                        <option value="Hafalan Al-Qur'an">Hafalan Al-Qur'an</option>
                    </select>
                </div>

                <div id="m_wrap_tilawah" style="display:none; background:#f4f0fa; padding:12px; border-radius:6px; margin-bottom:15px; border-left: 3px solid #8a2be2;">
                    <label style="color:#8a2be2; font-weight:700; display:block; margin-bottom:6px;">Target Tilawah</label>
                    <div style="display:flex; gap:10px;">
                        <select name="tilawah_juz" class="m-input" style="width:40%;"><option value="">Juz</option><?php for($i=1;$i<=30;$i++) echo "<option value='$i'>Juz $i</option>"; ?></select>
                        <input type="text" name="tilawah_halaman" class="m-input" placeholder="Halaman (Cth: 12-13)">
                    </div>
                </div>

                <div id="m_wrap_iqro" style="display:none; background:#f0f6fc; padding:12px; border-radius:6px; margin-bottom:15px; border-left: 3px solid #2271b1;">
                    <label style="color:#2271b1; font-weight:700; display:block; margin-bottom:6px;">Target Jilid/Halaman</label>
                    <div style="display:flex; gap:10px;">
                        <select name="iqro_jilid" class="m-input" style="width:40%;"><option value="">Jilid</option><?php for($i=1;$i<=6;$i++) echo "<option value='$i'>Jilid $i</option>"; ?></select>
                        <input type="number" name="iqro_halaman" class="m-input" placeholder="Halaman">
                    </div>
                </div>

                <div id="m_wrap_hafalan" style="display:none; background:#f3f9f4; padding:12px; border-radius:6px; margin-bottom:15px; border-left: 3px solid #46b450;">
                    <label style="color:#46b450; font-weight:700; display:block; margin-bottom:6px;">Target Hafalan Baru</label>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <select name="hafalan_juz" class="m-input"><option value="">Pilih Juz</option><?php for($i=1;$i<=30;$i++) echo "<option value='$i'>Juz $i</option>"; ?></select>
                        <select name="hafalan_surah" class="m-input">
                            <option value="">Pilih Surah</option>
                            <?php 
                            $surahs = ['Al-Fatihah','Al-Baqarah','Ali \'Imran','An-Nisa\'','Al-Ma\'idah','Al-An\'am','Al-A\'raf','Al-Anfal','At-Taubah','Yunus','Hud','Yusuf','Ar-Ra\'d','Ibrahim','Al-Hijr','An-Nahl','Al-Isra\'','Al-Kahf','Maryam','Ta-Ha','Al-Anbiya\'','Al-Hajj','Al-Mu\'minun','An-Nur','Al-Furqan','Asy-Syu\'ara\'','An-Naml','Al-Qasas','Al-\'Ankabut','Ar-Rum','Luqman','As-Sajdah','Al-Ahzab','Saba\'','Fatir','Ya-Sin','As-Saffat','Sad','Az-Zumar','Gafir','Fussilat','Asy-Syura','Az-Zukhruf','Ad-Dukhan','Al-Jasiyah','Al-Ahqaf','Muhammad','Al-Fath','Al-Hujurat','Qaf','Az-Zariyat','At-Tur','An-Najm','Al-Qamar','Ar-Rahman','Al-Waqi\'ah','Al-Hadid','Al-Mujadilah','Al-Hasyr','Al-Mumtahanah','As-Saff','Al-Jumu\'ah','Al-Munafiqun','At-Tagabun','At-Talaq','At-Tahrim','Al-Mulk','Al-Qalam','Al-Haqqah','Al-Ma\'arij','Nuh','Al-Jinn','Al-Muzzammil','Al-Muddassir','Al-Qiyamah','Al-Insan','Al-Mursalat','An-Naba\'','An-Nazi\'at','\'Abasa','At-Takwir','Al-Infitar','Al-Mutaffifin','Al-Insyiqaq','Al-Buruj','At-Tariq','Al-A\'la','Al-Gasyiyah','Al-Fajr','Al-Balad','Asy-Syams','Al-Lail','Ad-Duha','Asy-Syarh','At-Tin','Al-\'Alaq','Al-Qadr','Al-Bayyinah','Az-Zalzalah','Al-\'Adiyat','Al-Qari\'ah','At-Takasur','Al-\'Asr','Al-Humazah','Al-Fil','Quraisy','Al-Ma\'un','Al-Kausar','Al-Kafirun','An-Nasr','Al-Lahab','Al-Ikhlas','Al-Falaq','An-Nas'];
                            foreach($surahs as $s) { echo "<option value='$s'>$s</option>"; }
                            ?>
                        </select>
                        <input type="text" name="hafalan_ayat" class="m-input" placeholder="Ayat (Cth: 1-5)">
                    </div>
                </div>

                <div id="m_wrap_nilai" style="display:none;">
                    <div class="modal-field">
                        <label id="m_lbl_1">Kriteria 1</label>
                        <select name="nilai_1" class="m-input" required>
                            <option value="5">5 - Sangat Baik</option><option value="4" selected>4 - Baik</option><option value="3">3 - Cukup</option><option value="2">2 - Kurang</option><option value="1">1 - Sangat Kurang</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label id="m_lbl_2">Kriteria 2</label>
                        <select name="nilai_2" class="m-input" required>
                            <option value="5">5 - Sangat Baik</option><option value="4" selected>4 - Baik</option><option value="3">3 - Cukup</option><option value="2">2 - Kurang</option><option value="1">1 - Sangat Kurang</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label>Kelancaran Umum</label>
                        <select name="nilai_3" class="m-input" required>
                            <option value="5">5 - Sangat Baik</option><option value="4" selected>4 - Baik</option><option value="3">3 - Cukup</option><option value="2">2 - Kurang</option><option value="1">1 - Sangat Kurang</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label>Catatan Ustadz</label>
                        <textarea name="catatan_ustadz" rows="3" class="m-input" placeholder="Tambahkan catatan jika ada..."></textarea>
                    </div>
                    <button type="submit" name="simta_frontend_submit" class="btn-rekam" style="padding:12px; font-size:14px; margin-top:10px;">💾 Simpan Progres Santri</button>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        // FITUR PENCARIAN LIVE
        document.getElementById('simta_search_santri').addEventListener('input', function() {
            var filter = this.value.toLowerCase();
            var cards = document.querySelectorAll('.santri-card');
            
            cards.forEach(function(card) {
                var name = card.querySelector('.santri-name').innerText.toLowerCase();
                if (name.indexOf(filter) > -1) {
                    card.style.display = 'flex'; // menggunakan flex karena struktur card menggunakan flex
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // SCRIPT MODAL
        var simtaModal = document.getElementById('popup_rekam_nilai');
        
        function openRekamModal(santriId, santriNama) {
            document.getElementById('modal_santri_id').value = santriId;
            document.getElementById('m_title_santri').innerText = "Rekam Progres: " + santriNama;
            simtaModal.style.display = "flex";
        }

        function closeRekamModal() {
            simtaModal.style.display = "none";
        }

        document.getElementById('m_kategori').addEventListener('change', function() {
            var val = this.value;
            document.getElementById('m_wrap_tilawah').style.display = (val === "Tilawah Al-Qur'an") ? 'block' : 'none';
            document.getElementById('m_wrap_iqro').style.display    = (val === "Latihan Iqro/Tilawati") ? 'block' : 'none';
            document.getElementById('m_wrap_hafalan').style.display = (val === "Hafalan Al-Qur'an") ? 'block' : 'none';
            document.getElementById('m_wrap_nilai').style.display   = (val !== "") ? 'block' : 'none';

            var lbl1 = document.getElementById('m_lbl_1');
            var lbl2 = document.getElementById('m_lbl_2');
            
            if (val === "Tilawah Al-Qur'an") { lbl1.innerText = "Makhraj (Kesesuaian Huruf)"; lbl2.innerText = "Tajwid (Hukum Bacaan)"; } 
            else if (val === "Latihan Iqro/Tilawati") { lbl1.innerText = "Ketepatan (Huruf yang dibaca)"; lbl2.innerText = "Makhraj (Pengucapan)"; } 
            else if (val === "Hafalan Al-Qur'an") { lbl1.innerText = "Makhraj (Pengucapan)"; lbl2.innerText = "Tajwid (Hukum Bacaan)"; }
        });

        window.onclick = function(event) { if (event.target == simtaModal) { closeRekamModal(); } }
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('simta_perkembangan_ustadz', 'simta_shortcode_ustadz_cards');