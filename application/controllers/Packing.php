<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Scan packing: anak packing menembak barcode resi tiap selesai satu paket.
 * Tiap scan tercatat atas nama akun yang sedang login, jadi ketahuan siapa
 * yang mengerjakan paket mana kalau nanti ada barang hilang atau tertukar.
 *
 * Anak packing hanya melihat datanya sendiri; rekap semua orang ada di
 * halaman terpisah untuk HR.
 */
class Packing extends CI_Controller
{
    /** Empat anak packing: Dhika, Ica, Shintya, Adam. */
    const AKUN_PACKING = [16, 23, 24, 25];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library(['session', 'template']);
        if (empty($_SESSION['user']['id'])) {
            if ($this->input->is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'pesan' => 'Sesi berakhir. Login ulang.']);
                exit;
            }
            redirect('auth/login');
        }
    }

    private function _uid() { return (int) $_SESSION['user']['id']; }

    /** Nama produk + qty dari isi pesanan, untuk ditampilkan setelah scan. */
    private function _isi($pesanan)
    {
        $isi = json_decode((string) $pesanan, true);
        if (!is_array($isi)) return ['produk' => '', 'qty' => 0];
        $nama = []; $qty = 0;
        foreach ($isi as $it) {
            foreach (['name', 'model_name', 'name_parent', 'item_name', 'sku', 'sku_parent'] as $k) {
                if (!empty($it[$k])) { $nama[] = trim((string) $it[$k]); break; }
            }
            $qty += (int) ($it['qty'] ?? 0);
        }
        $teks = implode(' + ', array_slice($nama, 0, 3));
        if (count($nama) > 3) $teks .= ' +' . (count($nama) - 3) . ' lagi';
        return ['produk' => mb_substr($teks, 0, 250), 'qty' => $qty];
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $me = $this->db->select('img')->where('id', $this->_uid())->get('user')->row_array();
        $data['foto'] = !empty($me['img']) ? $me['img'] : '';
        $data['title'] = 'Scan Packing - ' . $this->template->title();
        $data['content'] = $this->load->view('packing/scan', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /** Terima satu hasil scan. Dipanggil tiap barcode ditembak. */
    public function scan()
    {
        header('Content-Type: application/json');
        $resi = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input->post('resi')));
        if (strlen($resi) < 6) { echo json_encode(['ok' => false, 'jenis' => 'asing', 'pesan' => 'Kode terlalu pendek.']); return; }

        // Sudah pernah discan? Tolak, sebutkan siapa dan kapan.
        $ada = $this->db->query("
            SELECT p.scanned_at, p.produk, u.full_name
            FROM packing_scan p LEFT JOIN user u ON u.id = p.user_id
            WHERE p.resi = ? LIMIT 1", [$resi])->row_array();
        if ($ada) {
            echo json_encode(['ok' => false, 'jenis' => 'dobel',
                'pesan' => 'Sudah discan ' . ($ada['full_name'] ?: 'orang lain') .
                           ' jam ' . date('H:i', strtotime($ada['scanned_at'])),
                'produk' => $ada['produk']]);
            return;
        }

        $trx = $this->db->query("
            SELECT order_id, marketplace, pesanan, shipping, order_status
            FROM transaction WHERE awb_number = ? ORDER BY id DESC LIMIT 1", [$resi])->row_array();
        if (!$trx) {
            echo json_encode(['ok' => false, 'jenis' => 'asing', 'pesan' => 'Resi tidak ditemukan di sistem.']);
            return;
        }

        $isi = $this->_isi($trx['pesanan']);
        $this->db->insert('packing_scan', [
            'user_id' => $this->_uid(), 'order_id' => $trx['order_id'], 'resi' => $resi,
            'marketplace' => $trx['marketplace'], 'produk' => $isi['produk'],
            'qty' => $isi['qty'], 'scanned_at' => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['ok' => true, 'resi' => $resi, 'order_id' => $trx['order_id'],
            'produk' => $isi['produk'], 'qty' => $isi['qty'], 'kurir' => $trx['shipping'],
            'jam' => date('H:i:s'), 'hari_ini' => $this->_hitung_hari_ini()]);
    }

    private function _hitung_hari_ini()
    {
        $r = $this->db->query("SELECT COUNT(*) AS n FROM packing_scan
            WHERE user_id = ? AND DATE(scanned_at) = CURDATE()", [$this->_uid()])->row_array();
        return (int) ($r['n'] ?? 0);
    }

    /** Rekap semua orang. Hanya untuk yang punya izin lihat modul packing/HR. */
    private function _boleh_rekap()
    {
        $r = (int) ($_SESSION['user']['role'] ?? 0);
        return in_array($r, [1, 2, 4, 5, 6], true); // developer, super admin, owner, head admin, HRD
    }

    public function rekap()
    {
        if (!$this->_boleh_rekap()) { show_404(); return; }
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Rekap Packing - ' . $this->template->title();
        $data['content'] = $this->load->view('packing/rekap', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function rekap_data()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(['ok' => false]); return; }

        $tgl = '/^\d{4}-\d{2}-\d{2}$/';
        $dari = (string) $this->input->get('dari');
        $sampai = (string) $this->input->get('sampai');
        if (!preg_match($tgl, $dari))   $dari = date('Y-m-d');
        if (!preg_match($tgl, $sampai)) $sampai = date('Y-m-d');

        // Semua anak packing selalu tampil, walau belum scan sama sekali
        // (angka 0) -- supaya kelihatan siapa yang belum mulai, bukan hilang
        // dari daftar dan dikira tidak ada.
        $per_orang = $this->db->query("
            SELECT u.id, u.full_name AS nama, u.img AS foto,
                   COUNT(p.id) AS paket, COALESCE(SUM(p.qty),0) AS pcs,
                   MIN(TIME(p.scanned_at)) AS mulai, MAX(TIME(p.scanned_at)) AS selesai
            FROM user u
            LEFT JOIN packing_scan p ON p.user_id = u.id AND DATE(p.scanned_at) BETWEEN ? AND ?
            WHERE u.id IN (" . implode(',', self::AKUN_PACKING) . ")
            GROUP BY u.id, u.full_name, u.img
            ORDER BY paket DESC, u.full_name", [$dari, $sampai])->result_array();

        $per_hari = $this->db->query("
            SELECT DATE(p.scanned_at) AS tgl, u.full_name AS nama, COUNT(*) AS paket
            FROM packing_scan p LEFT JOIN user u ON u.id = p.user_id
            WHERE DATE(p.scanned_at) BETWEEN ? AND ?
              AND p.user_id IN (" . implode(',', self::AKUN_PACKING) . ")
            GROUP BY tgl, u.full_name ORDER BY tgl DESC, paket DESC", [$dari, $sampai])->result_array();

        $jam = $this->db->query("SELECT DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%s') AS j")->row_array();
        echo json_encode(['ok' => true, 'dari' => $dari, 'sampai' => $sampai, 'server' => $jam['j'],
                          'per_orang' => $per_orang, 'per_hari' => $per_hari]);
    }

    /** Rincian paket satu orang: resi dan produk apa saja yang dia packing. */
    public function rincian_orang()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(['ok' => false]); return; }
        $uid = (int) $this->input->get('uid');
        $tgl = '/^\d{4}-\d{2}-\d{2}$/';
        $dari = (string) $this->input->get('dari');
        $sampai = (string) $this->input->get('sampai');
        if (!preg_match($tgl, $dari))   $dari = date('Y-m-d');
        if (!preg_match($tgl, $sampai)) $sampai = date('Y-m-d');

        $u = $this->db->select('full_name, img')->where('id', $uid)->get('user')->row_array();
        $rows = $this->db->query("
            SELECT resi, order_id, produk, qty, marketplace,
                   DATE_FORMAT(scanned_at, '%Y-%m-%d %H:%i') AS waktu
            FROM packing_scan WHERE user_id = ? AND DATE(scanned_at) BETWEEN ? AND ?
            ORDER BY id DESC LIMIT 1000", [$uid, $dari, $sampai])->result_array();
        echo json_encode(['ok' => true, 'nama' => $u['full_name'] ?? '-',
                          'foto' => $u['img'] ?? '', 'rows' => $rows]);
    }

    /** Cari resi: siapa yang packing, kapan, produk apa. */
    public function cari_resi()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(['ok' => false]); return; }
        $resi = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input->get('resi')));
        if (strlen($resi) < 4) { echo json_encode(['ok' => false, 'pesan' => 'Kode terlalu pendek.']); return; }
        $r = $this->db->query("
            SELECT p.resi, p.order_id, p.produk, p.qty, p.scanned_at, u.full_name AS nama
            FROM packing_scan p LEFT JOIN user u ON u.id = p.user_id
            WHERE p.resi = ? OR p.order_id = ? LIMIT 1", [$resi, $resi])->row_array();
        if (!$r) { echo json_encode(['ok' => false, 'pesan' => 'Resi ini belum pernah discan.']); return; }
        echo json_encode(['ok' => true, 'data' => $r]);
    }

    /** Daftar scan milik SENDIRI hari ini. Tidak bisa melihat milik orang lain. */
    public function milik_saya()
    {
        header('Content-Type: application/json');
        $rows = $this->db->query("
            SELECT resi, order_id, produk, qty, DATE_FORMAT(scanned_at, '%H:%i') AS jam
            FROM packing_scan WHERE user_id = ? AND DATE(scanned_at) = CURDATE()
            ORDER BY id DESC LIMIT 200", [$this->_uid()])->result_array();
        echo json_encode(['ok' => true, 'hari_ini' => $this->_hitung_hari_ini(), 'rows' => $rows]);
    }
}
