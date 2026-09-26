<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengirim notifikasi push/FCM di latar belakang.
 *
 * Dulu notifikasi ke admin dikirim langsung saat karyawan absen, dan server
 * menunggu tiap kiriman ke Google selesai sebelum menjawab "berhasil" --
 * karyawan ikut menunggu notifikasi yang bukan untuk dirinya. Sekarang absen
 * hanya mencatat ke push_antrean; cron menjalankan ini tiap menit, dan di
 * dalamnya memeriksa antrean tiap 3 detik.
 *
 * Hanya dari terminal:  php index.php antrean_push jalan [detik]
 */
class Antrean_push extends CI_Controller
{
    public function jalan($detik = 55)
    {
        if (!is_cli()) { show_404(); return; }
        $this->load->database();
        $this->load->library('PushSender');
        $this->load->library('FcmSender');

        $batas = time() + max(1, (int) $detik);
        $n = 0;
        while (time() < $batas) {
            $rows = $this->db->query("SELECT * FROM push_antrean
                WHERE terkirim_at IS NULL ORDER BY id LIMIT 50")->result_array();
            foreach ($rows as $r) {
                // Ditandai dulu supaya tidak terkirim dua kali kalau ada galat di tengah.
                $this->db->where('id', $r['id'])->update('push_antrean', ['terkirim_at' => date('Y-m-d H:i:s')]);
                try { $this->pushsender->kirim_ke_user((int) $r['user_id'], $r['judul'], $r['isi'], $r['url']); }
                catch (\Throwable $e) { log_message('error', 'push antrean gagal: ' . $e->getMessage()); }
                try { $this->fcmsender->kirim((int) $r['user_id'], $r['judul'], $r['isi'], $r['url'], $r['kategori']); }
                catch (\Throwable $e) { log_message('error', 'fcm antrean gagal: ' . $e->getMessage()); }
                $n++;
            }
            if (!$rows) sleep(3);
        }
        echo '[' . date('Y-m-d H:i:s') . "] terkirim=$n\n";
    }
}
