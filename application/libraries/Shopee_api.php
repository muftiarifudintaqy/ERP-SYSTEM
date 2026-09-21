<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pemanggil Shopee Open API v2.
 *
 * Sebelumnya ERP menitipkan semua panggilan ke endpoint.bhskin.co.id,
 * server milik pihak lain yang tidak mengenali toko Montera sehingga
 * cetak label tidak pernah berhasil. Library ini memanggil Shopee
 * langsung memakai kredensial yang sudah tersimpan di marketplace_config.
 *
 * Alur cetak label ada tiga langkah dan tidak bisa dipotong:
 *   1. get_shipping_document_parameter  — tanya jenis dokumen yang boleh
 *   2. create_shipping_document         — minta Shopee menyiapkannya
 *   3. download_shipping_document       — ambil PDF-nya
 *
 * Antara langkah 2 dan 3 Shopee butuh waktu menyiapkan berkas. Kalau
 * langsung diunduh, jawabannya "not yet ready". Karena itu ada
 * penantian bertahap di tunggu_siap().
 */
class Shopee_api
{
    private $CI;
    private $cfg = [];          // cache konfigurasi per shop_id
    private $galat = '';

    const HOST_BAKU = 'https://partner.shopeemobile.com';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function galat()
    {
        return $this->galat;
    }

    /* ================================================================
     * KONFIGURASI TOKO
     * ============================================================== */

    /**
     * Ambil kredensial satu toko dari marketplace_config.
     * Hasilnya disimpan sebentar di memori supaya satu permintaan yang
     * memanggil beberapa endpoint tidak membaca database berulang kali.
     */
    public function konfigurasi($shop_id)
    {
        $shop_id = (string)$shop_id;
        if (isset($this->cfg[$shop_id])) return $this->cfg[$shop_id];

        $baris = $this->CI->db
            ->select('id, val, shop_name, status')
            ->from('marketplace_config')
            ->where('opt', 'shopee')
            ->where('shop_id', $shop_id)
            ->get()->row_array();

        if (!$baris) {
            $this->galat = "Toko {$shop_id} tidak ada di marketplace_config.";
            return NULL;
        }

        $v = json_decode($baris['val'], TRUE);
        if (!is_array($v) || empty($v['access_token'])) {
            $this->galat = "Kredensial toko {$shop_id} tidak terbaca.";
            return NULL;
        }

        $v['_row_id']   = (int)$baris['id'];
        $v['_shop_id']  = $shop_id;
        $v['partner_host'] = str_replace('\\/', '/', $v['partner_host'] ?? self::HOST_BAKU);

        $this->cfg[$shop_id] = $v;
        return $v;
    }

    /* ================================================================
     * TANDA TANGAN & PEMANGGILAN
     * ============================================================== */

    private function tanda_tangan($path, $ts, $token, $shop_id, $partner_id, $partner_key)
    {
        // Urutannya ditentukan Shopee dan tidak boleh diubah:
        // partner_id + path + timestamp + access_token + shop_id
        $dasar = $partner_id . $path . $ts . $token . $shop_id;
        return hash_hmac('sha256', $dasar, $partner_key);
    }

    /**
     * Panggil satu endpoint Shopee.
     *
     * @param string $shop_id
     * @param string $path    misal /api/v2/logistics/create_shipping_document
     * @param array  $isi     badan permintaan; kosong berarti GET
     * @param array  $query   parameter tambahan di URL
     * @return array|NULL     isi 'response', atau NULL kalau gagal
     */
    public function panggil($shop_id, $path, $isi = [], $query = [], $mentah = FALSE)
    {
        $c = $this->konfigurasi($shop_id);
        if (!$c) return NULL;

        $ts    = time();
        $token = $c['access_token'];
        $sign  = $this->tanda_tangan($path, $ts, $token, $c['_shop_id'],
                                     $c['partner_id'], $c['partner_key']);

        $q = array_merge([
            'partner_id'   => $c['partner_id'],
            'timestamp'    => $ts,
            'access_token' => $token,
            'shop_id'      => $c['_shop_id'],
            'sign'         => $sign,
        ], $query);

        $url = rtrim($c['partner_host'], '/') . $path . '?' . http_build_query($q);

        $ch = curl_init($url);
        $opt = [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 120,
        ];
        if (!empty($isi)) {
            $opt[CURLOPT_POST]       = TRUE;
            $opt[CURLOPT_POSTFIELDS] = json_encode($isi);
            $opt[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
        }
        curl_setopt_array($ch, $opt);

        $jawab = curl_exec($ch);
        $err   = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->galat = 'Koneksi ke Shopee gagal: ' . $err;
            return NULL;
        }

        // Unduhan PDF tidak berbentuk JSON, jadi dikembalikan apa adanya.
        if ($mentah) return $jawab;

        $d = json_decode($jawab, TRUE);
        if (!is_array($d)) {
            $this->galat = 'Jawaban Shopee tidak terbaca.';
            return NULL;
        }

        // Token kedaluwarsa: segarkan lalu ulangi sekali. Access token
        // Shopee hanya berlaku 4 jam, jadi ini kejadian rutin, bukan
        // tanda ada yang salah.
        if (!empty($d['error']) && strpos($d['error'], 'error_auth') !== FALSE) {
            if ($this->segarkan_token($shop_id)) {
                unset($this->cfg[$shop_id]);
                return $this->panggil($shop_id, $path, $isi, $query, $mentah);
            }
        }

        if (!empty($d['error'])) {
            $this->galat = $d['error'] . ': ' . ($d['message'] ?? '');
            return $d;   // tetap dikembalikan; result_list sering berisi rinciannya
        }

        return $d;
    }

    /* ================================================================
     * PEMBARUAN TOKEN
     * ============================================================== */

    /**
     * Tukar refresh_token dengan access_token baru.
     * Dipanggil sendiri saat token kedaluwarsa, dan bisa dijadwalkan
     * lewat cron tiap 3 jam supaya tidak pernah sampai kedaluwarsa.
     */
    public function segarkan_token($shop_id)
    {
        $c = $this->konfigurasi($shop_id);
        if (!$c || empty($c['refresh_token'])) return FALSE;

        $path = '/api/v2/auth/access_token/get';
        $ts   = time();
        // Endpoint ini tidak memakai access_token dalam tanda tangannya.
        $sign = hash_hmac('sha256', $c['partner_id'] . $path . $ts, $c['partner_key']);

        $url = rtrim($c['partner_host'], '/') . $path . '?' . http_build_query([
            'partner_id' => $c['partner_id'],
            'timestamp'  => $ts,
            'sign'       => $sign,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode([
                'refresh_token' => $c['refresh_token'],
                'partner_id'    => (int)$c['partner_id'],
                'shop_id'       => (int)$c['_shop_id'],
            ]),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $jawab = curl_exec($ch);
        curl_close($ch);

        $d = json_decode($jawab, TRUE);
        if (empty($d['access_token'])) {
            $this->galat = 'Gagal menyegarkan token: ' . ($d['message'] ?? $jawab);
            return FALSE;
        }

        $baru = $c;
        unset($baru['_row_id'], $baru['_shop_id']);
        $baru['access_token']  = $d['access_token'];
        $baru['refresh_token'] = $d['refresh_token'] ?? $c['refresh_token'];
        $baru['expire_in']     = $d['expire_in'] ?? 14400;

        $this->CI->db->where('id', $c['_row_id'])->update('marketplace_config', [
            'val'              => json_encode($baru),
            'refresh_token_at' => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        unset($this->cfg[$shop_id]);
        return TRUE;
    }

    /** Segarkan token semua toko aktif. Dipakai oleh cron. */
    public function segarkan_semua()
    {
        $hasil = [];
        $daftar = $this->CI->db->select('shop_id, shop_name')
            ->from('marketplace_config')
            ->where('opt', 'shopee')->where('status', 'Aktif')
            ->get()->result_array();

        foreach ($daftar as $t) {
            $ok = $this->segarkan_token($t['shop_id']);
            $hasil[$t['shop_id']] = $ok ? 'OK' : $this->galat;
        }
        return $hasil;
    }

    /* ================================================================
     * DOKUMEN PENGIRIMAN
     * ============================================================== */

    /* ================================================================
     * ATUR PENGIRIMAN
     * ============================================================== */

    /**
     * Daftar alamat penjemputan beserta pilihan waktunya.
     * Dipakai untuk mengisi formulir sebelum pesanan diproses.
     */
    public function alamat_jemput($shop_id)
    {
        $d = $this->panggil($shop_id, '/api/v2/logistics/get_address_list');
        return $d['response']['address_list'] ?? [];
    }

    /** Parameter yang diminta Shopee untuk satu pesanan sebelum dikirim. */
    public function parameter_kirim($shop_id, $sn)
    {
        $d = $this->panggil($shop_id, '/api/v2/logistics/get_shipping_parameter',
                            [], ['order_sn' => $sn]);
        return $d['response'] ?? [];
    }

    /**
     * Proses satu pesanan: pesan penjemputan ke kurir.
     *
     * Ini tindakan nyata di Shopee, bukan sekadar mengambil data. Setelah
     * berhasil, resi terbit dan pesanan berpindah ke PROCESSED sehingga
     * labelnya bisa dicetak.
     *
     * Shopee v2 tidak punya versi massal, jadi dipanggil satu per satu.
     */
    public function atur_pengiriman($shop_id, $sn, $address_id = NULL, $pickup_time_id = NULL)
    {
        $param = $this->parameter_kirim($shop_id, $sn);

        $isi = ['order_sn' => $sn];

        // Shopee menentukan sendiri metode mana yang berlaku untuk tiap
        // pesanan: dijemput kurir, diantar ke drop-off, atau tanpa
        // keduanya. Mengirim metode yang salah akan ditolak.
        if (!empty($param['info_needed']['pickup'])) {
            $jemput = [];
            if ($address_id) {
                $jemput['address_id'] = (int)$address_id;
            } elseif (!empty($param['pickup']['address_list'][0]['address_id'])) {
                $jemput['address_id'] = (int)$param['pickup']['address_list'][0]['address_id'];
            }
            if ($pickup_time_id) {
                $jemput['pickup_time_id'] = (string)$pickup_time_id;
            } elseif (!empty($param['pickup']['address_list'][0]['time_slot_list'][0]['pickup_time_id'])) {
                $jemput['pickup_time_id'] = (string)$param['pickup']['address_list'][0]['time_slot_list'][0]['pickup_time_id'];
            }
            $isi['pickup'] = $jemput;
        } elseif (!empty($param['info_needed']['dropoff'])) {
            $isi['dropoff'] = new stdClass();
        } else {
            $isi['non_integrated'] = new stdClass();
        }

        $d = $this->panggil($shop_id, '/api/v2/logistics/ship_order', $isi);

        if (!empty($d['error'])) {
            $this->galat = $d['error'] . ': ' . ($d['message'] ?? '');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Proses beberapa pesanan sekaligus.
     * @return array ['berhasil' => [...], 'gagal' => ['sn' => alasan]]
     */
    public function atur_pengiriman_banyak($shop_id, array $order_sn,
                                           $address_id = NULL, $pickup_time_id = NULL)
    {
        $berhasil = [];
        $gagal = [];
        foreach ($order_sn as $sn) {
            if ($this->atur_pengiriman($shop_id, $sn, $address_id, $pickup_time_id)) {
                $berhasil[] = $sn;
            } else {
                $gagal[$sn] = $this->galat;
            }
        }
        return ['berhasil' => $berhasil, 'gagal' => $gagal];
    }

    /** Langkah 1: tanya jenis dokumen yang boleh dipakai tiap pesanan. */
    public function parameter_dokumen($shop_id, array $order_sn)
    {
        $daftar = array_map(function ($sn) { return ['order_sn' => $sn]; }, $order_sn);
        $d = $this->panggil($shop_id,
            '/api/v2/logistics/get_shipping_document_parameter',
            ['order_list' => $daftar]);

        $keluar = [];
        foreach ($d['response']['result_list'] ?? [] as $r) {
            $keluar[$r['order_sn']] = $r['suggest_shipping_document_type']
                                   ?? 'NORMAL_AIR_WAYBILL';
        }
        return $keluar;
    }

    /**
     * Ambil nomor resi satu pesanan dari Shopee.
     *
     * Resi ini wajib disertakan saat membuat dokumen. Tanpa dia Shopee
     * menolak dengan "tracking number is invalid", meskipun resinya
     * sebenarnya sudah terbit di sisi mereka.
     */
    public function nomor_resi($shop_id, $sn)
    {
        $d = $this->panggil($shop_id, '/api/v2/logistics/get_tracking_number',
                            [], ['order_sn' => $sn]);
        return $d['response']['tracking_number'] ?? NULL;
    }

    /** Langkah 2: minta Shopee menyiapkan dokumennya. */
    public function buat_dokumen($shop_id, array $order_sn, $jenis = NULL)
    {
        $param = $jenis ? [] : $this->parameter_dokumen($shop_id, $order_sn);

        $daftar = [];
        foreach ($order_sn as $sn) {
            $baris = [
                'order_sn' => $sn,
                'shipping_document_type' => $jenis ?: ($param[$sn] ?? 'NORMAL_AIR_WAYBILL'),
            ];
            $resi = $this->nomor_resi($shop_id, $sn);
            if ($resi) $baris['tracking_number'] = $resi;
            $daftar[] = $baris;
        }

        $d = $this->panggil($shop_id,
            '/api/v2/logistics/create_shipping_document',
            ['order_list' => $daftar]);

        $berhasil = [];
        $gagal    = [];
        foreach ($d['response']['result_list'] ?? [] as $r) {
            if (!empty($r['fail_error'])) {
                $gagal[$r['order_sn']] = $r['fail_message'] ?? $r['fail_error'];
            } else {
                $berhasil[] = $r['order_sn'];
            }
        }
        // Hanya yang tercatat berhasil di result_list yang boleh diunduh.
        // Sebelumnya pesanan yang tidak disebut Shopee dianggap siap,
        // dan akibatnya 30 pesanan menghasilkan PDF berisi 2 label
        // sementara pesannya tetap "berhasil". Yang tidak disebut
        // sekarang dicatat sebagai dilewati, supaya jumlahnya jujur.
        foreach ($order_sn as $sn) {
            if (!isset($gagal[$sn]) && !in_array($sn, $berhasil, TRUE)) {
                $gagal[$sn] = 'Belum siap dicetak (belum diatur pengirimannya).';
            }
        }

        return ['siap' => $berhasil, 'gagal' => $gagal];
    }

    /**
     * Langkah 3: unduh PDF gabungan.
     * Shopee butuh waktu menyiapkan berkas setelah create, jadi
     * percobaannya diulang beberapa kali dengan jeda.
     */
    public function unduh_dokumen($shop_id, array $order_sn, $jenis = NULL, $percobaan = 6)
    {
        $param = $jenis ? [] : $this->parameter_dokumen($shop_id, $order_sn);

        $daftar = [];
        foreach ($order_sn as $sn) {
            $daftar[] = [
                'order_sn' => $sn,
                'shipping_document_type' => $jenis ?: ($param[$sn] ?? 'NORMAL_AIR_WAYBILL'),
            ];
        }

        for ($i = 1; $i <= $percobaan; $i++) {
            $pdf = $this->panggil($shop_id,
                '/api/v2/logistics/download_shipping_document',
                ['order_list' => $daftar], [], TRUE);

            // PDF sungguhan selalu diawali %PDF
            if (is_string($pdf) && substr($pdf, 0, 4) === '%PDF') {
                return $pdf;
            }

            $d = json_decode((string)$pdf, TRUE);
            $pesan = $d['message'] ?? '';
            $this->galat = $pesan ?: 'Dokumen belum siap.';

            // Belum siap: tunggu lalu coba lagi. Jedanya bertambah
            // supaya tidak membanjiri Shopee saat antreannya panjang.
            // Shopee menyiapkan PDF-nya di latar belakang. Selama
            // jawabannya masih salah satu dari ini, berkasnya sedang
            // dibuat dan cukup ditunggu.
            if (stripos($pesan, 'not ready') !== FALSE
                || stripos($pesan, 'not yet') !== FALSE
                || stripos($pesan, 'processing') !== FALSE
                || stripos($pesan, 'download later') !== FALSE
                || stripos($this->galat, 'belum siap') !== FALSE) {
                sleep(min(3 * $i, 10));
                continue;
            }

            return NULL;   // galat lain, tidak ada gunanya diulang
        }

        return NULL;
    }

    /**
     * Alur lengkap: siapkan lalu unduh.
     * @return string|NULL isi PDF
     */
    public function ambil_label($shop_id, array $order_sn, &$catatan = [])
    {
        if (empty($order_sn)) {
            $this->galat = 'Tidak ada pesanan yang dipilih.';
            return NULL;
        }

        $buat = $this->buat_dokumen($shop_id, $order_sn);
        foreach ($buat['gagal'] as $sn => $sebab) {
            $catatan[] = "{$sn}: {$sebab}";
        }

        if (empty($buat['siap'])) {
            $this->galat = 'Semua pesanan belum bisa dicetak.';
            return NULL;
        }

        return $this->unduh_dokumen($shop_id, $buat['siap']);
    }
}
