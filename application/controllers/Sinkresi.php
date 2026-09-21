<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengisi nomor resi Shopee.
 *
 * Sinkronisasi pesanan tidak pernah mengambil nomor resi, sehingga kolom
 * awb_number kosong untuk semua pesanan. Akibatnya tombol Lacak Resi
 * tidak menemukan apa pun dan tidak ada daftar resi yang bisa dipakai
 * anak packing.
 *
 * Resi baru terbit setelah pengiriman diatur, jadi yang diproses di sini
 * hanya pesanan berstatus PROCESSED ke atas yang resinya masih kosong.
 *
 * Dijalankan lewat cron:
 *   cd /var/www/skinlyfe-erp && php index.php sinkresi jalan
 *
 * Atau dari browser oleh pengguna yang sudah masuk:
 *   erp.skinlyfe.id/sinkresi/jalan
 */
class Sinkresi extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('Shopee_api');
    }

    public function index()
    {
        $this->jalan();
    }

    /**
     * @param int $batas jumlah pesanan yang diproses sekali jalan.
     *                   Dibatasi supaya satu putaran tidak berjalan
     *                   terlalu lama; sisanya diambil putaran berikutnya.
     */
    public function jalan($batas = 200)
    {
        $cli = is_cli();
        if (!$cli && !$this->session->userdata('id')) {
            show_404();
            return;
        }

        set_time_limit(900);
        ignore_user_abort(true);
        if (!$cli) header('Content-Type: text/plain; charset=utf-8');

        $batas = max(1, min((int)$batas, 500));

        // Hanya toko Shopee yang aktif. Toko lain ditangani jalur lain.
        $toko = $this->db->select('shop_id, shop_name')
            ->from('marketplace_config')
            ->where('opt', 'shopee')->where('status', 'Aktif')
            ->get()->result_array();

        $totalIsi = 0;
        $totalGagal = 0;

        foreach ($toko as $t) {
            $rows = $this->db->select('id, order_id')
                ->from('transaction')
                ->where('shop_id', $t['shop_id'])
                ->where('is_manual', 0)
                ->where_in('order_status', ['PROCESSED', 'SHIPPED', 'TO_CONFIRM_RECEIVE'])
                ->group_start()
                    ->where('awb_number IS NULL')
                    ->or_where('awb_number', '')
                ->group_end()
                ->order_by('id', 'DESC')
                ->limit($batas)
                ->get()->result_array();

            $this->tulis("Toko {$t['shop_name']}: " . count($rows) . " pesanan tanpa resi");

            foreach ($rows as $r) {
                $resi = $this->shopee_api->nomor_resi($t['shop_id'], $r['order_id']);

                if (!$resi) {
                    $totalGagal++;
                    continue;
                }

                $this->db->where('id', $r['id'])->update('transaction', [
                    'awb_number' => $resi,
                    'no_resi'    => $resi,
                ]);
                $totalIsi++;

                // Jeda pendek supaya tidak menabrak batas laju Shopee.
                usleep(120000);
            }
        }

        $this->tulis('');
        $this->tulis("Selesai. Terisi: {$totalIsi}, tidak dapat resi: {$totalGagal}");
    }

    /** Berapa pesanan yang masih kosong resinya. */
    public function sisa()
    {
        if (!is_cli() && !$this->session->userdata('id')) { show_404(); return; }
        if (!is_cli()) header('Content-Type: text/plain; charset=utf-8');

        $q = $this->db->query("
            SELECT order_status,
                   COUNT(*) AS jumlah,
                   SUM(awb_number IS NULL OR awb_number = '') AS tanpa_resi
            FROM transaction
            WHERE is_manual = 0 AND marketplace LIKE '%SHOPEE%'
              AND order_status IN ('PROCESSED','SHIPPED','TO_CONFIRM_RECEIVE')
            GROUP BY order_status
        ")->result_array();

        foreach ($q as $b) {
            $this->tulis(sprintf('%-22s %6d pesanan, %6d tanpa resi',
                $b['order_status'], $b['jumlah'], $b['tanpa_resi']));
        }
    }

    private function tulis($teks)
    {
        echo $teks . "\n";
        if (!is_cli()) flush();
    }
}
