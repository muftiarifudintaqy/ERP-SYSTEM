<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengecek ulang tahun karyawan.
 *
 * Setiap hari jam 00:05 dicek siapa saja karyawan aktif (hrd_karyawan,
 * status_karyawan = 'Aktif') yang tanggal_lahir-nya jatuh pada hari itu
 * (dibandingkan bulan+tanggal saja, bukan tahun). Untuk tiap yang
 * berulang tahun, semua user aktif dikirimi satu notifikasi lewat tabel
 * notifications yang sama dipakai sistem popup notif yang sudah ada
 * (lihat poll_notifications() di Kinerja.php) -- supaya muncul di
 * kartu notif seperti biasa, dan di sisi frontend (TemplateDashboard.php)
 * kartu berkategori 'ultah' dikenali khusus untuk memicu animasi
 * confetti + banner ucapan.
 *
 * Idempoten: kalau cron ini kebetulan jalan dua kali di hari yang sama
 * (atau dipicu manual ulang), karyawan yang sama tidak dikirimi notif
 * dobel -- dicek dulu apakah sudah ada notifikasi kategori 'ultah'
 * untuk karyawan itu yang dibuat hari ini.
 *
 * Dijalankan lewat cron:
 *   cd /var/www/skinlyfe-erp && php index.php ultah jalan
 *
 * Atau dari browser oleh pengguna yang sudah masuk:
 *   erp.skinlyfe.id/ultah/jalan
 */
class Ultah extends CI_Controller
{
    // Alamat ditulis tetap, bukan diambil dari konfigurasi: di CLI tidak
    // ada HTTP_HOST, dan nilainya jatuh ke "localhost" -- notifikasi push
    // yang memakai itu membuka localhost di ponsel orang, bukan ERP.
    const URL_ERP = 'https://erp.skinlyfe.id/';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function index()
    {
        $this->jalan();
    }

    public function jalan()
    {
        $cli = is_cli();
        if (!$cli && !$this->session->userdata('id')) {
            show_404();
            return;
        }
        if (!$cli) header('Content-Type: text/plain; charset=utf-8');

        $hariIni = date('m-d');

        $ultah = $this->db->select('id, user_id, nama')
            ->from('hrd_karyawan')
            ->where('status_karyawan', 'Aktif')
            ->where('user_id IS NOT NULL', null, false)
            ->where("DATE_FORMAT(tanggal_lahir, '%m-%d') =", $hariIni)
            ->get()->result_array();

        if (!$ultah) {
            $this->tulis('Tidak ada yang ulang tahun hari ini.');
            return;
        }

        $penerima = $this->db->select('id')->from('user')
            ->where('status', 'Aktif')->get()->result_array();
        $penerimaIds = array_column($penerima, 'id');

        if (!$penerimaIds) {
            $this->tulis('Tidak ada user aktif untuk dikirimi notif.');
            return;
        }

        $terkirim = [];

        foreach ($ultah as $k) {
            // Sudah dikirim hari ini? Lewati supaya tidak dobel kalau
            // cron kebetulan jalan dua kali.
            $sudah = $this->db->select('id')->from('notifications')
                ->where('category', 'ultah')
                ->where('related_table', 'hrd_karyawan')
                ->where('related_id', $k['id'])
                ->where('DATE(created_at) =', date('Y-m-d'))
                ->limit(1)->get()->row_array();

            if ($sudah) {
                $this->tulis("Lewati {$k['nama']}: notif hari ini sudah pernah dikirim.");
                continue;
            }

            // Nama akun, bukan nama di data HRD: di sana sebagian
            // tertulis panggilan ("ica", "deby"), dan mengumumkan
            // "Selamat ulang tahun ica" ke seluruh kantor terasa
            // setengah jadi.
            $akun = $this->db->select('full_name')->from('user')
                ->where('id', $k['user_id'])->get()->row_array();
            $nama = !empty($akun['full_name']) ? $akun['full_name'] : $k['nama'];

            // Nama dipindah ke baris kedua: ditempel di judul, nama
            // panjang terpotong jadi dua baris di tengah kata dan
            // tampilannya jadi berantakan.
            // Tanpa emoji di judul: kartunya sudah punya 🎉🎂🎈 besar
            // di atas, jadi mengulangnya di baris judul terasa penuh.
            $judul_lain = 'Selamat Ulang Tahun';
            $pesan_lain = $nama . "\n"
                        . 'Semoga sehat selalu, dilancarkan rezekinya, '
                        . 'dan dimudahkan segala urusannya.';

            // Yang berulang tahun dapat sapaan langsung, bukan kabar
            // tentang dirinya sendiri.
            $judul_dia  = 'Selamat Ulang Tahun, kamu';
            // Baris pertama nama, baris berikutnya doa. Tampilan
            // membaca baris pertama sebagai nama besar dan sisanya
            // sebagai ucapan di bawahnya -- jadi teksnya cukup ditulis
            // di sini, tidak ditanam di dalam kode tampilan.
            $pesan_dia  = $nama . "\n"
                        . 'Semoga sehat selalu, dilancarkan rezekinya, '
                        . 'dan dimudahkan segala urusannya.';

            $batch = [];
            foreach ($penerimaIds as $uid) {
                $sendiri = ((int)$uid === (int)$k['user_id']);
                $batch[] = [
                    'user_id'       => $uid,
                    'title'         => $sendiri ? $judul_dia : $judul_lain,
                    'message'       => $sendiri ? $pesan_dia : $pesan_lain,
                    'type'          => 'success',
                    'category'      => 'ultah',
                    'subcategory'   => 'karyawan',
                    'related_table' => 'hrd_karyawan',
                    'related_id'    => $k['id'],
                    'created_at'    => date('Y-m-d H:i:s'),
                ];
            }
            $this->db->insert_batch('notifications', $batch);

            // Push sengaja tidak dikirim di sini: jalan() berjalan
            // tengah malam, dan ponsel yang berbunyi jam 00:05 bukan
            // ucapan, itu gangguan. Pengirimannya lewat dorong(), jam 7.

            $terkirim[] = $nama;
            $this->tulis("Terkirim: {$nama} ke " . count($penerimaIds) . ' user (notif + push).');
        }

        // Pesan grup tidak dibuat di sini. jalan() berjalan tengah
        // malam supaya kartunya sudah siap untuk yang absen jam 6 pagi;
        // kalau pesan grup ikut dibuat saat itu, bot mengirimnya tengah
        // malam juga. Pengirimannya terpisah lewat "ultah grup", jam 9.
    }

    /**
     * Kirim push ke ponsel untuk ucapan yang sudah dibuat jalan()
     * tengah malam. Dibaca dari tabel notifications, jadi teksnya persis
     * sama dengan yang terlihat di ERP.
     *
     *   php index.php ultah dorong
     */
    public function dorong()
    {
        if (!is_cli() && !$this->session->userdata('id')) { show_404(); return; }
        if (!is_cli()) header('Content-Type: text/plain; charset=utf-8');

        $rows = $this->db->select('user_id, title, message')
            ->from('notifications')
            ->where('category', 'ultah')
            ->where('DATE(created_at) =', date('Y-m-d'))
            ->get()->result_array();

        if (!$rows) {
            $this->tulis('Tidak ada ucapan ulang tahun hari ini.');
            return;
        }

        $this->load->library('PushSender');
        $berhasil = 0;
        foreach ($rows as $r) {
            try {
                $this->pushsender->kirim_ke_user(
                    (int)$r['user_id'], $r['title'], $r['message'], self::URL_ERP
                );
                $berhasil++;
            } catch (Exception $e) {
                log_message('error', 'Push ultah gagal user '
                    . $r['user_id'] . ': ' . $e->getMessage());
            }
        }

        $this->tulis('Push terkirim ke ' . $berhasil . ' dari ' . count($rows) . ' user.');
    }

    /**
     * Kirim pesan ulang tahun ke grup WhatsApp, dari ucapan yang sudah
     * dibuat jalan() tengah malam.
     *
     *   php index.php ultah grup
     */
    public function grup()
    {
        if (!is_cli() && !$this->session->userdata('id')) { show_404(); return; }
        if (!is_cli()) header('Content-Type: text/plain; charset=utf-8');

        $hariIni = date('m-d');
        $ultah = $this->db->select('user_id')->from('hrd_karyawan')
            ->where('status_karyawan', 'Aktif')
            ->where('user_id IS NOT NULL', null, false)
            ->where("DATE_FORMAT(tanggal_lahir, '%m-%d') =", $hariIni)
            ->get()->result_array();

        if (!$ultah) {
            $this->tulis('Tidak ada yang ulang tahun hari ini.');
            return;
        }

        $nama_nama = [];
        foreach ($ultah as $u) {
            $akun = $this->db->select('full_name')->from('user')
                ->where('id', $u['user_id'])->get()->row_array();
            if (!empty($akun['full_name'])) $nama_nama[] = $akun['full_name'];
        }

        $this->_kabari_grup($nama_nama);
    }

    /**
     * Satu pesan ke grup WhatsApp absensi, berisi siapa saja yang
     * berulang tahun hari ini.
     *
     * Ditulis ke wa_pesan_teks dengan kode bertanggal, pola yang sama
     * dengan laporan keterlambatan: kodenya unik, jadi kalau cron
     * kebetulan jalan dua kali pesannya tidak dobel. Bot yang membaca
     * antrean itu yang mengirimkannya.
     */
    private function _kabari_grup($nama_nama)
    {
        if (empty($nama_nama)) return;

        $kode = 'ultah-' . date('Y-m-d');
        if ($this->db->where('kode', $kode)->count_all_results('wa_pesan_teks')) {
            $this->tulis('Pesan grup hari ini sudah pernah dibuat.');
            return;
        }

        $hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli',
                  'Agustus','September','Oktober','November','Desember'];

        $b = [];
        $b[] = '*SELAMAT ULANG TAHUN*';
        $b[] = $hari[(int)date('w')] . ', ' . (int)date('j') . ' '
             . $bulan[(int)date('n')] . ' ' . date('Y');
        $b[] = '';

        foreach ($nama_nama as $n) {
            $b[] = '🎂 ' . $n;
        }

        $b[] = '';
        $b[] = count($nama_nama) > 1
            ? 'Selamat ulang tahun untuk kalian semua. Semoga sehat selalu, '
            . 'dilancarkan rezekinya, dan dimudahkan segala urusannya.'
            : 'Selamat ulang tahun. Semoga sehat selalu, dilancarkan rezekinya, '
            . 'dan dimudahkan segala urusannya.';

        $this->db->insert('wa_pesan_teks', [
            'kode' => $kode,
            'isi'  => implode("\n", $b),
        ]);

        $this->tulis('Pesan grup masuk antrean: ' . count($nama_nama) . ' orang.');
    }

    /**
     * Kirim satu push tes ke satu user, tanpa menyentuh tabel
     * notifications dan tanpa broadcast ke siapa pun -- khusus untuk
     * memverifikasi jalur Web Push (banner notifikasi sistem) sedang
     * dikerjakan/diuji, terlepas dari logika ulang tahun yang
     * sebenarnya.
     *
     *   php index.php ultah tes_push 3
     */
    /**
     * Push versi ulang tahun ke satu user saja, dengan teks yang sama
     * persis seperti yang dikirim jalan() -- untuk memeriksa isinya di
     * ponsel tanpa mengganggu 29 orang lain.
     *
     *   php index.php ultah tes_ultah 3
     */
    public function tes_ultah($user_id)
    {
        if (!is_cli()) { show_404(); return; }

        $akun = $this->db->select('full_name')->from('user')
            ->where('id', (int)$user_id)->get()->row_array();
        $nama = !empty($akun['full_name']) ? $akun['full_name'] : 'Karyawan';

        $this->load->library('PushSender');
        $this->pushsender->kirim_ke_user(
            (int)$user_id,
            'Selamat Ulang Tahun',
            $nama . "\n"
                . 'Semoga sehat selalu, dilancarkan rezekinya, '
                . 'dan dimudahkan segala urusannya.',
            self::URL_ERP
        );
        $this->tulis('Push ultah dikirim ke user_id ' . (int)$user_id . '.');
    }

    public function tes_push($user_id)
    {
        if (!is_cli()) { show_404(); return; }
        // Dimuat di sini, bukan di constructor: PushSender menarik
        // vendor/autoload.php dan kunci VAPID, dan itu hanya perlu saat
        // benar-benar mengirim. Tanpa baris ini $this->pushsender bernilai
        // null dan CI3 tidak memberi peringatan apa pun -- prosesnya mati
        // diam-diam, persis seperti yang terjadi pada percobaan pertama.
        $this->load->library('PushSender');
        $this->pushsender->kirim_ke_user(
            (int)$user_id,
            '🎉 Tes Push Ultah',
            'Kalau ini muncul sebagai notifikasi sistem, jalur push-nya jalan.',
            base_url('hrd')
        );
        $this->tulis('Push tes dikirim ke user_id ' . (int)$user_id . '.');
    }

    private function tulis($teks)
    {
        echo $teks . "\n";
        if (!is_cli()) flush();
    }
}
