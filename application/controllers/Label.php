<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengelompokan label pengiriman per produk.
 *
 * Anak packing sebelumnya memilih label satu per satu dari tumpukan
 * yang urutannya acak. Di sini order dikelompokkan menurut isi
 * pesanannya, sehingga sekali cetak menghasilkan satu jenis barang
 * saja dan penyiapannya bisa dikerjakan sekaligus.
 */
class Label extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mymodel');
    }

    private function _boleh()
    {
        $uid = intval($_SESSION['user']['id'] ?? 0);
        if (!$uid) return FALSE;
        // Dibaca dari tabel izin, bukan daftar peran yang dipatok di sini:
        // peran baru (mis. packing) cukup diberi akses modul 'label' lewat
        // pengaturan, tanpa perlu menyunting berkas ini lagi.
        $r = $this->mymodel->selectWithQuery(
            "SELECT rp.id FROM user_roles ur
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN modules m ON m.id = rp.module_id
             WHERE ur.user_id = $uid
               AND m.name = 'label'
               AND rp.can_view = 1
             LIMIT 1");
        return !empty($r);
    }

    /** Nama produk ringkas dari kolom pesanan, dipakai sebagai kunci kelompok. */
    private function _produk($pesanan)
    {
        $isi = json_decode((string)$pesanan, TRUE);
        if (!is_array($isi) || empty($isi)) return '';

        $nama = [];
        foreach ($isi as $it) {
            foreach (['model_name', 'item_name', 'model_sku'] as $k) {
                if (!empty($it[$k])) {
                    $n = str_replace('amp;', '', trim((string)$it[$k]));
                    if (mb_strlen($n) > 40) $n = rtrim(mb_substr($n, 0, 40)) . '...';
                    $nama[] = $n;
                    break;
                }
            }
        }

        if (count($nama) > 1) return '__CAMPURAN__';
        return $nama ? $nama[0] : '';
    }

    public function index()
    {
        if (!$this->_boleh()) { show_404(); }

        $tgl = '/^\d{4}-\d{2}-\d{2}$/';
        $dari   = $this->input->get('dari');
        $sampai = $this->input->get('sampai');
        // Dua hari cukup: label yang perlu dicetak selalu order baru.
        if (!preg_match($tgl, (string)$dari))   $dari   = date('Y-m-d', strtotime('-1 day'));
        if (!preg_match($tgl, (string)$sampai)) $sampai = date('Y-m-d');

        $mp     = $this->input->get('mp');
        $status = $this->input->get('status');   // belum | sudah | kosong = semua

        $syarat = "t.date BETWEEN " . $this->db->escape($dari . ' 00:00:00')
                . " AND " . $this->db->escape($sampai . ' 23:59:59')
                . " AND t.pesanan IS NOT NULL AND t.pesanan <> ''";

        if (in_array($mp, ['SHOPEE', 'TIKTOK'], TRUE)) {
            $syarat .= " AND t.marketplace = " . $this->db->escape($mp);
        }
        if ($status === 'belum')      $syarat .= " AND (t.print_at IS NULL OR t.print_at = '')";
        elseif ($status === 'sudah')  $syarat .= " AND t.print_at <> ''";

        // siap-kemas
        // Hanya order yang memang menunggu dikemas. Tanpa batas ini
        // yang sudah dikirim, batal, dan belum dibayar ikut terhitung,
        // sehingga jumlahnya ribuan dan tidak ada gunanya bagi packing.
        $os = $this->input->get('os');
        if ($os === 'semua') {
            // dibiarkan: sesekali perlu mencetak ulang order lama
        } else {
            // punya-resi
            // Shopee menolak mencetak label yang dokumennya belum siap.
            // Nomor resi baru terbit setelah "Atur pengiriman" dijalankan,
            // jadi baris tanpa resi tidak ditampilkan sama sekali:
            // memunculkannya hanya membuat tombol cetak selalu gagal.
            // READY_TO_SHIP sengaja dikeluarkan walau sebagian punya resi:
            // dibuktikan langsung ke Shopee, permintaannya tetap dijawab
            // "belum diatur pengirimannya". Menampilkannya hanya membuat
            // tombol cetak gagal padahal kelihatan siap. Hanya PROCESSED
            // yang benar-benar bisa dicetak; yang sudah SHIPPED berarti
            // labelnya sudah dicetak dan barangnya sudah diserahkan.
            $syarat .= " AND t.order_status = 'PROCESSED'";
            $syarat .= " AND t.awb_number IS NOT NULL AND t.awb_number <> ''";
            // Tanpa kurir tidak ditampilkan: Shopee tidak bisa membuat label
            // kalau jasa kirimnya belum terisi, jadi barisnya selalu gagal
            // dicetak. Memunculkannya hanya membuat orang menekan tombol
            // yang mustahil berhasil, lalu mengira sistemnya rusak.
            $syarat .= " AND t.shipping IS NOT NULL AND t.shipping <> ''";
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT t.id, t.order_id, t.marketplace, t.pesanan, t.print_at, t.awb_number, t.shipping,
                   (SELECT l.document_url FROM transaction_shipping_doc_logs l
                     WHERE l.order_id = t.order_id ORDER BY l.id DESC LIMIT 1) AS pdf
            FROM transaction t
            WHERE $syarat
            ORDER BY t.date DESC");

        // Dikelompokkan di sini, bukan lewat GROUP BY, karena nama
        // produknya harus dirakit dulu dari JSON kolom pesanan.
        $kelompok = [];
        foreach ($rows as $r) {
            $p = $this->_produk($r['pesanan']);
            if ($p === '') continue;

            // pisah-kurir
            // Shopee menolak menggabungkan label dari jasa kirim berbeda
            // dalam satu unduhan ("Packages can not download together").
            // Kelompoknya karena itu dipisah per produk dan per kurir,
            // supaya satu tombol selalu menghasilkan satu berkas yang sah.
            $kurir = $r['shipping'] ?: 'Tanpa kurir';
            $p = $p . ' :: ' . $kurir;

            if (!isset($kelompok[$p])) {
                $kelompok[$p] = [
                    'nama'  => $p,
                    'kurir' => $kurir,
                    'order' => [], 'belum' => 0, 'sudah' => 0,
                ];
            }
            $dicetak = !empty($r['print_at']);
            $kelompok[$p][$dicetak ? 'sudah' : 'belum']++;
            $kelompok[$p]['order'][] = [
                'id'         => $r['id'],
                'order_id'   => $r['order_id'],
                'mp'         => $r['marketplace'],
                'awb'        => $r['awb_number'],
                'print_at'   => $r['print_at'],
                'pdf'        => $r['pdf'],
            ];
        }

        // Yang paling banyak di atas: itu yang paling melelahkan
        // kalau harus dipilih satu per satu.
        uasort($kelompok, function ($a, $b) {
            return count($b['order']) <=> count($a['order']);
        });

        // Campuran selalu di bawah; order begini memang harus
        // disiapkan satu per satu, bukan diborong.
        // Campuran dipindah ke bawah: order begini memang harus
        // disiapkan satu per satu, bukan diborong.
        $bawah = [];
        foreach (array_keys($kelompok) as $kunci) {
            if (strpos($kunci, '__CAMPURAN__') === 0) {
                $bawah[$kunci] = $kelompok[$kunci];
                unset($kelompok[$kunci]);
            }
        }
        foreach ($bawah as $kunci => $c) {
            $c['nama'] = 'Campuran :: ' . $c['kurir'];
            $kelompok[$kunci] = $c;
        }

        $data = [
            'template' => $this->template,
            'user'     => $_SESSION['user'],
            'kelompok' => $kelompok,
            'dari'     => $dari,
            'sampai'   => $sampai,
            'mp'       => $mp,
            'status'   => $status,
            'os'       => $os,
        ];

        $data['title']   = 'Label Pengiriman - ' . $this->template->title();
        // Flashdata diambil di sini, bukan di view: view dirender jadi
        // string dulu lalu disisipkan ke template, dan pembacaan di dalam
        // string itu tidak selalu mengenai sesi yang sama.
        // Prioritaskan pesan dari query string (dipakai segarkan() supaya
        // tidak kena race sesi dengan poll_notifications), baru fallback
        // ke flashdata biasa untuk aksi lain di controller ini.
        $data['pesan_ok'] = $this->input->get('pesan_ok') ?: $this->session->flashdata('sukses');
        $data['pesan_no'] = $this->input->get('pesan_no') ?: $this->session->flashdata('gagal');

        $data['content'] = $this->load->view('label/all', $data, TRUE);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Satu berkas untuk semua label yang dipilih.
     *
     * Shopee menolak menggabungkan label dari jasa kirim berbeda dalam
     * satu permintaan, jadi berkasnya diambil per kurir lalu disatukan
     * di sini. Bagi yang mencetak, hasilnya tetap satu berkas: sekali
     * tekan cetak, seluruh tumpukan keluar.
     */
    public function gabung()
    {
        if (!$this->_boleh()) { show_404(); }

        set_time_limit(600);
        ignore_user_abort(TRUE);

        $ids = $this->input->post('ids');
        $daftar = array_values(array_filter(array_map('intval', explode(',', (string)$ids))));
        if (empty($daftar)) { show_error('Tidak ada label yang dipilih.', 400); return; }

        // Dikelompokkan per toko dan kurir: keduanya menentukan berkas
        // mana yang boleh disatukan di sisi Shopee.
        $rows = $this->mymodel->selectWithQuery(
            "SELECT id, order_id, shop_id, shipping FROM transaction
             WHERE id IN (" . implode(',', $daftar) . ")
               AND marketplace = 'SHOPEE' AND is_manual = 0
               AND awb_number IS NOT NULL AND awb_number <> ''");

        if (empty($rows)) {
            // Penyebab tersering: barisnya TikTok, bukan Shopee. Penggabung
            // ini hanya bicara ke API Shopee, jadi apa pun yang bukan Shopee
            // tidak akan pernah menghasilkan berkas -- dan pesan "belum siap"
            // menyesatkan karena orang mengira tinggal menunggu sebentar.
            $bukan = $this->mymodel->selectWithQuery(
                "SELECT DISTINCT marketplace FROM transaction
                 WHERE id IN (" . implode(',', $daftar) . ")
                   AND marketplace <> 'SHOPEE'");
            if ($bukan) {
                $nama = implode(', ', array_map(function ($r) {
                    return $r['marketplace'];
                }, $bukan));
                show_error('Label ' . $nama . ' belum bisa dicetak dari sini. '
                    . 'Cetak lewat Seller Center ' . $nama . ' dulu. '
                    . 'Supaya tidak tercampur, pilih Marketplace = SHOPEE '
                    . 'di penyaring atas.', 400);
                return;
            }
            show_error('Label belum siap dicetak.', 400);
            return;
        }

        $kelompok = [];
        foreach ($rows as $r) {
            $kunci = $r['shop_id'] . '|' . ($r['shipping'] ?: '-');
            $kelompok[$kunci][] = $r['order_id'];
        }

        $this->load->library('Shopee_api');
        $dir = FCPATH . 'uploads/shipping_labels/';
        if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

        $berkas  = [];
        $catatan = [];

        // Shopee menolak permintaan berisi lebih dari 50 label, jadi tiap
        // kelompok dipecah per 50 di sini. Bagi yang mencetak tidak ada
        // bedanya: seluruh potongan disatukan lagi menjadi satu berkas.
        foreach ($kelompok as $kunci => $order_ids) {
            list($shop_id, $kurir) = explode('|', $kunci, 2);

            foreach (array_chunk($order_ids, 50) as $ke => $sepotong) {
                $ket = [];
                $pdf = $this->shopee_api->ambil_label((int)$shop_id, $sepotong, $ket);

                if (!$pdf) {
                    $catatan[] = $kurir . ' (bagian ' . ($ke + 1) . '): '
                               . $this->shopee_api->galat();
                    log_message('error', 'GABUNG LABEL gagal | kurir=' . $kurir
                        . ' | bagian=' . ($ke + 1)
                        . ' | jml=' . count($sepotong)
                        . ' | galat=' . $this->shopee_api->galat());
                    continue;
                }

                $tmp = $dir . 'bagian-' . md5($kunci . $ke . microtime(TRUE)) . '.pdf';
                file_put_contents($tmp, $pdf);
                $berkas[] = $tmp;

                // Jeda antar permintaan supaya Shopee tidak menganggap
                // ini lonjakan panggilan yang tidak wajar.
                if (count($order_ids) > 50) { sleep(1); }
            }
        }

        if (empty($berkas)) {
            show_error('Tidak ada label yang berhasil diambil.<br>'
                     . implode('<br>', $catatan), 400);
            return;
        }

        // Sebagian gagal tetapi sebagian berhasil: berkasnya dibuka, dan
        // yang gagal disebutkan. Diam-diam memberi berkas yang kurang
        // adalah kegagalan yang paling mahal di sini -- orang mengira
        // seluruh tumpukan sudah tercetak, lalu paketnya tertinggal
        // tanpa ada yang tahu.
        if ($catatan) {
            log_message('error', 'GABUNG LABEL sebagian gagal: '
                . implode(' | ', $catatan));
        }

        $nama  = 'gabung-' . date('Ymd-His') . '-' . substr(md5(implode(',', $daftar)), 0, 6) . '.pdf';
        $hasil = $dir . $nama;

        if (count($berkas) === 1) {
            rename($berkas[0], $hasil);
        } else {
            $perintah = 'pdfunite ' . implode(' ', array_map('escapeshellarg', $berkas))
                      . ' ' . escapeshellarg($hasil) . ' 2>&1';
            exec($perintah, $keluaran, $kode);

            if ($kode !== 0 || !is_file($hasil)) {
                log_message('error', 'pdfunite gagal: ' . implode(' ', $keluaran));
                show_error('Gagal menggabungkan berkas.', 500);
                return;
            }
            foreach ($berkas as $b) { @unlink($b); }
        }

        // Penanda cetak dan riwayat, sama seperti jalur cetak lainnya.
        $waktu = date('Y-m-d H:i:s');
        $oleh  = (int)($this->session->userdata('id') ?? 0);
        $url   = base_url('uploads/shipping_labels/' . $nama);

        // Hanya order yang resinya benar-benar ada di dalam berkas yang
        // ditandai tercetak. Shopee bisa menolak sebagian pesanan dalam
        // satu permintaan; menandai semuanya membuat order yang gagal
        // hilang dari daftar padahal labelnya tidak pernah keluar, dan
        // paketnya berangkat tanpa label tanpa ada yang tahu.
        // Variabel sendiri, bukan $keluaran yang sudah dipakai pdfunite
        // di atas: exec() MENAMBAHKAN ke array yang diberikan, tidak
        // menimpanya, jadi memakai ulang variabel yang sama mencampur
        // pesan galat pdfunite ke dalam teks yang dicocokkan.
        $baris_pdf = [];
        exec('/usr/bin/pdftotext ' . escapeshellarg($hasil) . ' - 2>/dev/null', $baris_pdf);
        $teks_pdf = implode(' ', $baris_pdf);

        log_message('error', 'GABUNG LABEL verifikasi: panjang teks='
            . strlen($teks_pdf) . ' | berkas=' . $nama);

        // Dicocokkan lewat nomor pesanan, bukan nomor resi: pada label
        // JNE dan Anteraja resinya dicetak sebagai barcode saja dan tidak
        // ikut terbaca sebagai teks, sementara nomor pesanan selalu ada
        // di setiap label apa pun kurirnya.
        $terbukti = [];
        foreach ($rows as $r) {
            $oid = trim($r['order_id'] ?? '');
            $awb = trim($r['awb_number'] ?? '');
            $ada = ($oid !== '' && strpos($teks_pdf, $oid) !== FALSE)
                || ($awb !== '' && strpos($teks_pdf, $awb) !== FALSE);
            if ($ada) {
                $terbukti[] = $r;
            }
        }

        if (count($terbukti) < count($rows)) {
            log_message('error', 'GABUNG LABEL sebagian tidak masuk berkas: '
                . count($terbukti) . ' dari ' . count($rows)
                . ' | berkas=' . $nama);
        }

        $rows  = $terbukti;
        $daftar = array_map(function ($r) { return (int)$r['id']; }, $terbukti);

        $baris = [];
        foreach ($rows as $r) {
            $baris[] = [
                'order_id'     => $r['order_id'],
                'document_url' => $url,
                'marketplace'  => 'SHOPEE',
                'created_at'   => $waktu,
                'created_by'   => $oleh,
            ];
        }
        if ($baris) { $this->db->insert_batch('transaction_shipping_doc_logs', $baris); }

        // Hanya ditandai tercetak kalau seluruh potongan berhasil.
        // Kalau sebagian ditolak Shopee, barisnya harus tetap merah
        // supaya muncul lagi dan bisa dicetak ulang.
        if (!empty($daftar)) {
            $this->db->query(
                "UPDATE transaction SET print_at = " . $this->db->escape($waktu) .
                " WHERE id IN (" . implode(',', $daftar) . ")"
            );
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nama . '"');
        header('Content-Length: ' . filesize($hasil));
        readfile($hasil);
    }

    /**
     * Tarik status order terbaru dari Shopee, lalu kembali ke daftar.
     *
     * Daftar di halaman ini berasal dari database, yang disegarkan cron
     * (job "sinkresi") tiap 30 menit, hanya jam 06:00-22:00. Di jam sibuk
     * itu terasa: order yang baru saja diatur pengirimannya belum muncul,
     * dan yang baru dikirim masih terlihat siap cetak. Di luar jam itu
     * (malam-dini hari) cron tidak jalan sama sekali, jadi resi yang
     * terbit malam baru otomatis masuk pas cron jalan lagi jam 06:00.
     * Tombol ini untuk saat orang butuh yang terbaru sekarang, bukan
     * menunggu putaran cron berikutnya (atau menunggu sampai besok pagi
     * kalau sedang di luar jam kerja).
     */
    public function segarkan()
    {
        if (!$this->_boleh()) { show_404(); }

        set_time_limit(300);
        $hari_ini = date('Y-m-d');
        $kemarin  = date('Y-m-d', strtotime('-1 day'));

        // Dihitung sebelum dan sesudah supaya bisa dilaporkan berapa yang
        // benar-benar bertambah. Tanpa angka ini orang menekan tombolnya
        // berulang tanpa tahu apakah ada gunanya.
        // Disimpan id-nya, bukan cuma jumlah: dengan begitu bisa dicari
        // order mana saja yang benar-benar baru, lalu disebut nama
        // barangnya. "15 resi baru" tidak memberi tahu apa pun tentang
        // barang apa yang harus disiapkan; rinciannya yang berguna.
        $id_sebelum = [];
        $q = $this->db->query(
            "SELECT id FROM transaction
             WHERE marketplace = 'SHOPEE' AND order_status = 'PROCESSED'
               AND awb_number IS NOT NULL AND awb_number <> ''
               AND (print_at IS NULL OR print_at = '')
               AND date >= ?", [$kemarin . ' 00:00:00']);
        foreach ($q->result_array() as $r) { $id_sebelum[(int)$r['id']] = TRUE; }
        $sebelum = count($id_sebelum);

        foreach (['skinlyfe', 'prepare'] as $brand) {
            // Hanya hari ini, bukan dua hari: menarik dua hari berarti
            // ribuan order diperiksa ulang tiap klik dan orang menunggu
            // hampir semenit. Order kemarin sudah tertangani cron.
            $url = 'http://127.0.0.1:8090/api/shopee_get_order'
                 . '?start_date=' . $hari_ini
                 . '&until_date=' . $hari_ini
                 . '&brand=' . $brand;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_TIMEOUT        => 120,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }

        $baris_kini = $this->db->query(
            "SELECT id, pesanan FROM transaction
             WHERE marketplace = 'SHOPEE' AND order_status = 'PROCESSED'
               AND awb_number IS NOT NULL AND awb_number <> ''
               AND (print_at IS NULL OR print_at = '')
               AND date >= ?", [$kemarin . ' 00:00:00'])->result_array();

        $rincian = [];
        foreach ($baris_kini as $r) {
            if (isset($id_sebelum[(int)$r['id']])) continue;
            $nama = $this->_produk($r['pesanan']);
            if ($nama === '' || $nama === '__CAMPURAN__') $nama = 'Campuran';
            if (!isset($rincian[$nama])) $rincian[$nama] = 0;
            $rincian[$nama]++;
        }
        arsort($rincian);

        $sesudah = (int)$this->db->query(
            "SELECT COUNT(*) AS n FROM transaction
             WHERE marketplace = 'SHOPEE' AND order_status = 'PROCESSED'
               AND awb_number IS NOT NULL AND awb_number <> ''
               AND (print_at IS NULL OR print_at = '')
               AND date >= ?", [$kemarin . ' 00:00:00'])->row('n');

        $tambah = $sesudah - $sebelum;

        $pesan_ok = NULL;
        $pesan_no = NULL;

        if ($tambah > 0) {
            // Disebut barangnya, bukan cuma jumlah: yang dibutuhkan orang
            // packing adalah tahu barang apa yang harus disiapkan dari rak,
            // bukan angka total yang tidak bisa dikerjakan langsung.
            $daftar_barang = [];
            foreach ($rincian as $nama_barang => $jml) {
                $daftar_barang[] = $nama_barang . ' - ' . $jml;
            }
            $pesan_ok = $tambah . ' resi baru masuk:' . "\n"
                . implode("\n", $daftar_barang) . "\n\n"
                . 'Total ' . $sesudah . ' label siap dicetak.';
        } elseif ($sesudah > 0) {
            // Tidak diklaim "tidak ada resi baru": angka tambah di sini
            // hanya membandingkan sebelum dan sesudah klik ini, sementara
            // cron sinkresi juga mengisi resi tiap 30 menit. Resi bisa saja
            // baru masuk beberapa menit lalu lewat cron, dan mengatakan
            // tidak ada yang baru akan menyesatkan. Yang disebut jumlah
            // yang benar-benar siap dikerjakan sekarang.
            $pesan_no = 'Data sudah yang terbaru. Ada ' . $sesudah
                . ' label siap dicetak di daftar bawah. Kalau ada pesanan '
                . 'yang resinya belum muncul juga, minta Kak Putri '
                . 'menjalankan "Atur pengiriman" di Shopee Seller Center '
                . 'dulu supaya nomor resinya terbit.';
        } else {
            // Tidak ada yang bisa dikerjakan sama sekali: resinya belum
            // terbit, dan itu hanya muncul setelah "Atur pengiriman"
            // dijalankan di Shopee Seller Center. Disebutkan langsung
            // supaya tidak dikira sistemnya rusak dan orang tahu harus
            // minta ke siapa dan di mana.
            $pesan_no = 'Semua label sudah dicetak, tidak ada resi baru. '
                . 'Minta Kak Putri menjalankan "Atur pengiriman" di Shopee '
                . 'Seller Center dulu supaya nomor resi terbarunya muncul '
                . 'di sistem.';
        }

        // Pesan dikirim lewat query string, bukan session flashdata.
        // Alasan: request poll_notifications yang jalan tiap beberapa
        // detik di background ikut membuka sesi (CI pakai sess_driver
        // 'files' yang mengunci filenya per-request), dan kalau salah
        // satu dari request itu kebetulan membuka sesi lebih dulu
        // daripada request redirect ke /label, flashdata-nya sudah
        // "terpakai" oleh request lain sebelum sempat ditampilkan.
        // Lewat URL, tidak ada sesi yang disentuh sama sekali sehingga
        // tidak ada balapan seperti itu.
        $ref    = $this->input->server('HTTP_REFERER') ?: base_url('label');
        $bagian = parse_url($ref);
        parse_str($bagian['query'] ?? '', $qs);
        unset($qs['pesan_ok'], $qs['pesan_no']);
        if ($pesan_ok !== NULL) $qs['pesan_ok'] = $pesan_ok;
        if ($pesan_no !== NULL) $qs['pesan_no'] = $pesan_no;
        $path = $bagian['path'] ?? '/label';
        redirect($path . '?' . http_build_query($qs));
    }
}
