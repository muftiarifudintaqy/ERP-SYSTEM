<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';
// require_once APPPATH.'core/BaseController.php';

use Google\Client as Google_Client;
use Google\Service\Drive as Google_Service_Drive;
use Google\Service\Docs as Google_Service_Docs;
use Google\Service\Oauth2 as Google_Service_Oauth2;
use Google\Service\Sheets as Google_Service_Sheets;

class Googlemou extends CI_Controller
{
    private $TEMPLATE_FILE_ID = '1rt0VY8YUdf2bfdsywdzeO3L-NZ_rOCuj-5HwOOof7e8';

    /** @var Google_Client */
    private $client;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('app_mailer');
        $this->load->library('session');
        $this->load->library('template');

        $this->client = new Google_Client();
        $this->client->setAuthConfig(google_client_auth_config());

        // $this->set_public_methods([
        //     'oauth2callback',
        //     'action_generate_mou_pdf',
        //     'action_send_mou_email',
        //     'logout_google',
        // ]);

        $this->client->setScopes([
            Google_Service_Drive::DRIVE,
            Google_Service_Docs::DOCUMENTS,
            Google_Service_Sheets::SPREADSHEETS,
            Google_Service_Oauth2::USERINFO_EMAIL,
            Google_Service_Calendar::CALENDAR,
        ]);

        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent select_account');
        $this->client->setRedirectUri(base_url('googlemou/oauth2callback'));
    }

    public function index() {
        $token = $this->_get_valid_token_or_redirect();
        $this->load->view('redirect/success');
    }

    /** Callback OAuth dari Google */
    public function oauth2callback() {
        $code = $this->input->get('code');
        if (!$code) { echo "Gagal login (code tidak ada)."; return; }

        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            $desc = $token['error_description'] ?? $token['error'];
            echo "OAuth error: " . htmlspecialchars($desc);
            return;
        }

        // Simpan token (ARRAY) → session sebagai JSON
        $this->client->setAccessToken($token);
        $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));

        redirect('googlemou');
    }
    
    private function _findTextRangeInDoc(Google_Service_Docs $docsService, $docId, $needle)
    {
        $doc  = $docsService->documents->get($docId);
        $body = $doc->getBody();
        if (!$body) return null;

        $content = $body->getContent();
        if (!$content) return null;

        $needleLen = mb_strlen($needle);
        foreach ($content as $struct) {
            $para = $struct->getParagraph();
            if (!$para) continue;

            $elements = $para->getElements();
            if (!$elements) continue;

            foreach ($elements as $el) {
                $tr = $el->getTextRun();
                if (!$tr) continue;

                $text = $tr->getContent();
                $si   = $el->getStartIndex();
                $ei   = $el->getEndIndex();
                if ($text === null || $si === null || $ei === null) continue;

                $pos = mb_strpos($text, $needle);
                if ($pos !== false) {
                    $start = $si + $pos;
                    $end   = $start + $needleLen;
                    return ['start' => $start, 'end' => $end];
                }
            }
        }
        return null;
    }


    public function action_generate_mou_pdf()
    {
        // --- Ambil & validasi token dari session ---
        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
        }

        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
        }

        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
            }
        }

        // 1) Ambil input
        $id_campaign   = (int)$this->input->post('id_campaign');
        $nama_creator  = $this->input->post('nama_creator', true);
        $ids_raw       = $this->input->post('mou_item_ids', true);
        $override_raw  = (int)($this->input->post('total_cost_override_raw') ?? 0);
        $sow_text_in   = trim((string)$this->input->post('sow'));
        $produk_kerjasama   = trim((string)$this->input->post('produk_kerjasama'));
        $deadline_postingan = trim((string)$this->input->post('deadline_postingan'));
        $pay_awal_in   = trim((string)$this->input->post('pembayaran_awal') ?: 'DP');
        $persen_dp_in  = (int)($this->input->post('persentase_pembayaran_awal') ?? 50);

        if (!$nama_creator || !$id_campaign || !$ids_raw) {
            return $this->_json(['success'=>false,'message'=>'Param tidak lengkap']);
        }

        // 2) Query DB
        $ids = array_values(array_filter(array_map('intval', explode(',', $ids_raw))));
        if (!$ids) return $this->_json(['success'=>false,'message'=>'Item kosong']);

        $rows = $this->db->where_in('id', $ids)->get('endorse')->result_array();
        if (!$rows) return $this->_json(['success'=>false,'message'=>'Data item tidak ditemukan']);

        $auto_total = array_sum(array_map(fn($r)=>(int)($r['total_cost']??0), $rows));
        $total      = $override_raw ?: $auto_total;

        $inf      = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
        $campaign = $this->db->get_where('endorse_campaign', ['id'=>$id_campaign])->row_array();
        if (!$inf) return $this->_json(['success'=>false,'message'=>'Data influencer tidak ditemukan']);

        // 3) Build data pengganti template
        $picName = 'System';
        foreach ($rows as $r) {
            if (!empty($r['pic']))      { $picName = $r['pic']; break; }
            if (!empty($r['pic_name'])) { $picName = $r['pic_name']; break; }
        }

        $brand_raw = $campaign['brand'];
        $brand = ($brand_raw === 'MG') ? 'Miscella-G' : (($brand_raw === 'POME') ? 'POME' : 'BHSKIN');

        // === BUILD SOW sebagai plain lines (bullets ditangani Docs) ===
        $sow_lines = [];

        // Ambil data dari SOW builder (JSON)
        $sow_json = $this->input->post('sow_json');
        $is_ads   = $this->input->post('is_ads') ? true : false;

        if ($sow_json) {
            $sow_data = json_decode($sow_json, true);
            if (is_array($sow_data)) {
                foreach ($sow_data as $row) {
                    $qty = (int)($row['total'] ?? 0);
                    if ($qty <= 0) continue;

                    $produk = trim((string)($row['produk'] ?? '-'));
                    $kerkun = (($row['jenis'] ?? '') === 'kerkun') ? 'dengan Keranjang Kuning' : 'tanpa Keranjang Kuning';
                    $cboost = (($row['cboost'] ?? '') === 'cboost')   ? 'dan Codeboost'         : 'dan tanpa Codeboost';

                    $sow_lines[] = "{$qty} (".$this->_terbilang($qty).") Content Review Video Tiktok produk {$produk} {$kerkun} {$cboost}.";
                }
            }
        }
        // Tambahkan jika Ads dicentang
        if ($is_ads) {
            $sow_lines[] = "Scanbarcode untuk ads.";
        }

        // String final untuk disisipkan (tanpa numbering)
        $sow_plain = implode("\n", $sow_lines);

        $pemb_awal  = in_array(strtoupper($pay_awal_in),['DP','FP']) ? strtoupper($pay_awal_in) : 'DP';
        $persen_dp  = ($pemb_awal==='DP') ? ($persen_dp_in ?: ($campaign['dp_percentage'] ?? 50)) : 100;
        $nominal_dp = (int)(($total * $persen_dp) / 100);
        $tglIndo    = $this->_format_tanggal_id(date('Y-m-d'));

        // repl: tetap isi 'sow' (akan diganti marker dulu)
        $repl = [
            'brand'                       => $brand,
            'pic'                         => $picName,
            'full_name'                   => $inf['full_name'],
            'alamat'                      => $inf['alamat'] ?? '-',
            'phone'                       => $inf['phone'] ?? '-',
            'username'                    => $nama_creator,
            'sow'                         => '', 
            'produk_kerjasama'            => $produk_kerjasama ?: '-',
            'deadline_postingan'          => $deadline_postingan ?: '-',
            'total_cost'                  => number_format($total,0,',','.'),
            'total_cost_bilangan'         => $this->_terbilang($total).' Rupiah',
            'pembayaran_awal'             => $pemb_awal,
            'persentase_pembayaran_awal'  => $persen_dp,
            'nominal_dp'                  => number_format($nominal_dp,0,',','.'),
            'bilangan_pembayaran_awal'    => $this->_terbilang($nominal_dp).' Rupiah',
            'bank'                        => $inf['bank'] ?? '-',
            'no_rekening'                 => $inf['no_rekening'] ?? '-',
            'pemilik_rekening'            => $inf['pemilik_rekening'] ?? '-',
            'tanggal'                     => $tglIndo,
        ];

        try {
            $drive = new Google_Service_Drive($this->client);
            $docs  = new Google_Service_Docs($this->client);

            // --- Preflight: cek template & handle shortcut/docx ---
            $probe = $drive->files->get(
                $this->TEMPLATE_FILE_ID,
                ['fields'=>'id,name,mimeType,shortcutDetails', 'supportsAllDrives'=>true]
            );

            if ($probe->getShortcutDetails() && $probe->getShortcutDetails()->getTargetId()) {
                $this->TEMPLATE_FILE_ID = $probe->getShortcutDetails()->getTargetId();
                $probe = $drive->files->get(
                    $this->TEMPLATE_FILE_ID,
                    ['fields'=>'id,name,mimeType', 'supportsAllDrives'=>true]
                );
            }

            $tplMime  = $probe->getMimeType();
            $newTitle = 'MOU '.$nama_creator.' - '.$picName.' - '.$tglIndo;

            $copyReqArr = ['name'=>$newTitle];
            if (in_array($tplMime, [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword'
            ])) {
                $copyReqArr['mimeType'] = 'application/vnd.google-apps.document';
            }
            $copyReq  = new Google_Service_Drive_DriveFile($copyReqArr);

            $copied   = $drive->files->copy(
                $this->TEMPLATE_FILE_ID,
                $copyReq,
                ['fields'=>'id,name,mimeType', 'supportsAllDrives'=>true]
            );
            $docId = $copied->getId();

            if ($copied->getMimeType() !== 'application/vnd.google-apps.document') {
                return $this->_json([
                    'success'=>false,
                    'message'=>'Template bukan Google Docs dan gagal dikonversi.'
                ]);
            }

            // --- Replace placeholder: semua kecuali {{sow}} → marker khusus ---
            $SOW_MARKER = '[[SOW_BLOCK]]';
            $reqs = [];
            foreach($repl as $k=>$v){
                $replaceText = ($k === 'sow') ? $SOW_MARKER : (string)$v;
                $reqs[] = new Google_Service_Docs_Request([
                    'replaceAllText'=>[
                        'containsText'=>['text'=>'{{'.$k.'}}','matchCase'=>true],
                        'replaceText'=>$replaceText
                    ]
                ]);
            }
            $docs->documents->batchUpdate(
                $docId,
                new Google_Service_Docs_BatchUpdateDocumentRequest(['requests'=>$reqs])
            );

            // --- Ganti marker [[SOW_BLOCK]] dengan teks + jadikan bullets "a." ---
            $range = $this->_findTextRangeInDoc($docs, $docId, $SOW_MARKER);
            if ($range) {
                $start = $range['start'];
                $end   = $range['end'];

                $reqs2 = [
                    // 1) Hapus marker
                    new Google_Service_Docs_Request([
                        'deleteContentRange' => [ 'range' => ['startIndex'=>$start, 'endIndex'=>$end] ]
                    ]),
                    // 2) Sisipkan teks SOW (tiap item baris baru)
                    new Google_Service_Docs_Request([
                        'insertText' => [
                            'location' => ['index' => $start],
                            'text' => $sow_plain
                        ]
                    ]),
                ];

                // 3) Jadikan bullets ALPHA (a., b., c.)
                $insertLen = mb_strlen($sow_plain);
                // 1. Buat bullets 
                $reqs2[] = new Google_Service_Docs_Request([
                    'createParagraphBullets' => [
                        'range' => [
                            'startIndex' => $start,
                            'endIndex'   => $start + $insertLen
                        ],
                        'bulletPreset' => 'NUMBERED_DECIMAL_ALPHA_ROMAN'
                    ]
                ]);

                $docs->documents->batchUpdate(
                    $docId,
                    new Google_Service_Docs_BatchUpdateDocumentRequest(['requests'=>$reqs2])
                );
            }

            // --- Ambil link view + siapkan URL export PDF ---
            $meta   = $drive->files->get($docId, ['fields'=>'id,name,webViewLink', 'supportsAllDrives'=>true]);
            $docUrl = $meta->getWebViewLink();
            $downloadUrl = base_url('googlemou/export_pdf/'.$docId);

            // --- Logging DB (tanpa PDF) ---
            $this->db->trans_start();
            $now   = date('Y-m-d H:i:s');
            $user  = $_SESSION['user'] ?? null;
            $uid   = $user['id']   ?? null;
            $ucode = $user['code'] ?? null;

            foreach($ids as $endorseId){
                $insertData = [
                    'id_endorse'  => $endorseId,
                    'id_campaign' => $id_campaign,
                    'nama_creator'=> $nama_creator,
                    'pic'         => $picName,
                    'filename'    => $meta->getName().'.gdoc',
                    'pdf_url'     => $docUrl, // simpan link dokumen (compat)
                    'created_at'  => $now,
                    'generated_by'=> $ucode,
                ];
                if ($this->db->field_exists('extra_json', 'mou_logs')) {
                    $insertData['extra_json'] = json_encode([
                        'gdoc_id' => $docId,
                        'produk_kerjasama' => $produk_kerjasama,
                        'deadline_postingan' => $deadline_postingan
                    ], JSON_UNESCAPED_UNICODE);
                }
                $this->db->insert('mou_logs', $insertData);

                $rowEndorse = $this->db->get_where('endorse',['id'=>$endorseId])->row_array();
                $logs = [];
                if (!empty($rowEndorse['logs'])) {
                    $dec = json_decode($rowEndorse['logs'], true);
                    if (is_array($dec)) $logs = $dec;
                }
                $logs[] = [
                    'status_mou'   => 'MOU Generated',
                    'created_by'   => (string)$uid,
                    'created_text' => $ucode,
                    'created_at'   => $now,
                ];
                $this->db->update('endorse',[
                    'is_generated_mou'   => 1,
                    'link_generated_mou' => $docUrl,
                    'logs'               => json_encode($logs, JSON_UNESCAPED_UNICODE),
                    'updated_at'         => $now,
                    'updated_by'         => $uid,
                ], ['id'=>$endorseId]);
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return $this->_json(['success'=>false,'message'=>'Gagal menyimpan log ke database']);
            }

            // --- Response
            return $this->_json([
                'success'      => true,
                'doc_id'       => $docId,
                'doc_url'      => $docUrl,
                'download_url' => $downloadUrl,
                'pdf_url'      => $downloadUrl, // backward-compat
                'filename'     => $meta->getName().'.gdoc',
                'message'      => 'MOU berhasil dibuat.'
            ]);

        } catch (\Throwable $e) {
            return $this->_json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }


    // Stream PDF hasil export langsung ke browser (tanpa menyimpan file)
    public function export_pdf($docId)
    {
        // cek OAuth
        $raw = $this->session->userdata('access_token');
        if (!$raw) { show_error('Unauthorized', 401); return; }
        $token = is_string($raw) ? json_decode($raw,true) : $raw;
        if (!$token || empty($token['access_token'])) { show_error('Unauthorized', 401); return; }
        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) { show_error('Token expired', 401); return; }

        $drive = new Google_Service_Drive($this->client);

        try {
            // ambil nama file untuk nama unduhan
            $meta    = $drive->files->get($docId, ['fields'=>'id,name', 'supportsAllDrives'=>true]);
            $name    = $meta->getName();
            if (strtolower(substr($name, -4)) !== '.pdf') {
                // pastikan nama rapi untuk unduhan
                $name .= '.pdf';
            }

            // export bytes
            $resp    = $drive->files->export($docId, 'application/pdf', ['alt'=>'media']);
            $pdfData = $resp->getBody()->getContents();

            // kirim ke browser
            $this->output
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: attachment; filename="'.$name.'"')
                ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
                ->set_output($pdfData);
        } catch (\Throwable $e) {
            log_message('error', '[GDrive] export_pdf error: '.$e->getMessage());
            show_error('Gagal mengekspor PDF', 500);
        }
    }

    // public function action_send_mou_email()
    // {
    //     $nama_creator = $this->input->post('nama_creator', true);
    //     $doc_id       = $this->input->post('doc_id', true);   // baru
    //     $doc_url      = $this->input->post('doc_url', true);  // baru (link GDocs untuk body)
    //     $pdf_url      = $this->input->post('pdf_url', true);  // legacy (boleh ada, kita coba parse doc_id)

    //     if (!$nama_creator) {
    //         return $this->_json(['success'=>false, 'message'=>'Nama creator kosong']);
    //     }

    //     // ambil email influencer
    //     $inf   = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
    //     $email = $inf['email'] ?? '';
    //     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //         return $this->_json(['success'=>false, 'message'=>'Email influencer tidak valid']);
    //     }

    //     // jika doc_id belum ada, coba ambil dari pdf_url kita (pattern: .../googlemou/export_pdf/{docId})
    //     if (!$doc_id && $pdf_url && preg_match('~/export_pdf/([a-zA-Z0-9_-]+)~', $pdf_url, $m)) {
    //         $doc_id = $m[1];
    //     }
    //     if (!$doc_id) {
    //         return $this->_json(['success'=>false, 'message'=>'doc_id tidak ditemukan']);
    //     }

    //     // ---- OAuth token untuk akses Drive
    //     $raw = $this->session->userdata('access_token');
    //     $token = is_string($raw) ? json_decode($raw, true) : $raw;
    //     if (!$token || empty($token['access_token'])) {
    //         return $this->_json(['success'=>false, 'message'=>'Unauthorized (token kosong)']);
    //     }
    //     $this->client->setAccessToken($token);
    //     if ($this->client->isAccessTokenExpired()) {
    //         $refresh = $this->client->getRefreshToken();
    //         if ($refresh) {
    //             $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
    //             if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
    //             $this->client->setAccessToken($new);
    //             $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
    //         } else {
    //             return $this->_json(['success'=>false, 'message'=>'Token expired & tidak bisa refresh']);
    //         }
    //     }

    //     try {
    //         $drive   = new Google_Service_Drive($this->client);
    //         $meta    = $drive->files->get($doc_id, ['fields'=>'id,name', 'supportsAllDrives'=>true]);
    //         $pdfName = preg_replace('/\.gdoc$/i', '', $meta->getName()) . '.pdf';

    //         $resp    = $drive->files->export($doc_id, 'application/pdf', ['alt'=>'media']);
    //         $pdfData = $resp->getBody()->getContents();
    //     } catch (\Throwable $e) {
    //         return $this->_json(['success'=>false, 'message'=>'Gagal mengambil PDF dari Drive: '.$e->getMessage()]);
    //     }

    //     $this->load->library('email');

    //     $configs = [
    //         [
    //             'label'        => '465/ssl',
    //             'protocol'     => 'smtp',
    //             'smtp_host'    => app_env('GOOGLEMOU_SMTP_HOST'),
    //             'smtp_user'    => app_env('GOOGLEMOU_SMTP_USER'),
    //             'smtp_pass'    => app_env('GOOGLEMOU_SMTP_PASS'),
    //             'smtp_port'    => 465,
    //             'smtp_crypto'  => 'ssl',
    //             'smtp_timeout' => 30,
    //             'mailtype'     => 'text',             
    //             'charset'      => 'utf-8',
    //             'newline'      => "\r\n",
    //             'crlf'         => "\r\n",
    //             'wordwrap'     => true,
    //             'validate'     => true,
    //         ],
    //         [
    //             'label'        => '587/tls',
    //             'protocol'     => 'smtp',
    //             'smtp_host'    => app_env('GOOGLEMOU_SMTP_HOST'),
    //             'smtp_user'    => app_env('GOOGLEMOU_SMTP_USER'),
    //             'smtp_pass'    => app_env('GOOGLEMOU_SMTP_PASS'),
    //             'smtp_port'    => 587,
    //             'smtp_crypto'  => 'tls',
    //             'smtp_timeout' => 30,
    //             'mailtype'     => 'text',
    //             'charset'      => 'utf-8',
    //             'newline'      => "\r\n",
    //             'crlf'         => "\r\n",
    //             'wordwrap'     => true,
    //             'validate'     => true,
    //         ],
    //     ];

    //     $last_error = null;
    //     foreach ($configs as $cfg) {
    //         $this->email->initialize($cfg);
    //         $this->email->set_newline("\r\n");
    //         $this->email->set_crlf("\r\n");

    //         $this->email->clear(true);
    //         $this->email->from('mou@bhskin.co.id', 'BHSKIN - MoU System'); 
    //         $this->email->to($email);
    //         $this->email->subject('MoU Kerja Sama - '.$inf['full_name']);

    //         $linkView = $doc_url ?: $pdf_url;
    //         $body = "Halo kak {$inf['full_name']},\n\n"
    //             . "Terlampir dokumen MoU kerja sama dengan BHSKIN untuk dapat ditinjau.\n"
    //             . "Silakan dibaca kembali, dan hubungi kami jika ada hal yang ingin ditanyakan.\n\n"
    //             . "Terima kasih atas kerja samanya.\n\n"
    //             . "Salam,\n"
    //             . "Tim BHSKIN";
    //         $this->email->message($body);


    //         $this->email->attach($pdfData, 'attachment', $pdfName, 'application/pdf');

    //         if ($this->email->send()) {
    //             return $this->_json(['success' => true, 'used' => $cfg['label']]);
    //         }

    //         $last_error = "Channel {$cfg['label']} gagal:\n" . $this->email->print_debugger(['headers','subject','body']);
    //     }

    //     return $this->_json([
    //         'success' => false,
    //         'message' => $last_error ?: 'Tidak diketahui (gagal tanpa log)',
    //     ]);

    // }

    public function action_send_mou_email()
    {
        $nama_creator = $this->input->post('nama_creator', true);
        $doc_id       = $this->input->post('doc_id', true);   // baru
        $doc_url      = $this->input->post('doc_url', true);  // baru (link GDocs untuk body)
        $pdf_url      = $this->input->post('pdf_url', true);  // legacy (boleh ada, kita coba parse doc_id)

        if (!$nama_creator) {
            return $this->_json(['success'=>false, 'message'=>'Nama creator kosong']);
        }

        // ambil email influencer
        $inf   = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
        $email = $inf['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->_json(['success'=>false, 'message'=>'Email influencer tidak valid']);
        }

        // jika doc_id belum ada, coba ambil dari pdf_url kita (pattern: .../googlemou/export_pdf/{docId})
        if (!$doc_id && $pdf_url && preg_match('~/export_pdf/([a-zA-Z0-9_-]+)~', $pdf_url, $m)) {
            $doc_id = $m[1];
        }
        if (!$doc_id) {
            return $this->_json(['success'=>false, 'message'=>'doc_id tidak ditemukan']);
        }

        // ---- OAuth token untuk akses Google APIs
        $raw = $this->session->userdata('access_token');
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            return $this->_json(['success'=>false, 'message'=>'Unauthorized (token kosong)']);
        }
        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                return $this->_json(['success'=>false, 'message'=>'Token expired & tidak bisa refresh']);
            }
        }

        // ---- Ambil PDF dari Google Drive
        try {
            $drive   = new Google_Service_Drive($this->client);
            $meta    = $drive->files->get($doc_id, ['fields'=>'id,name', 'supportsAllDrives'=>true]);
            $pdfName = preg_replace('/\.gdoc$/i', '', $meta->getName()) . '.pdf';

            $resp    = $drive->files->export($doc_id, 'application/pdf', ['alt'=>'media']);
            $pdfData = $resp->getBody()->getContents();
        } catch (\Throwable $e) {
            return $this->_json(['success'=>false, 'message'=>'Gagal mengambil PDF dari Drive: '.$e->getMessage()]);
        }

        // ---- Kirim via SMTP
        try {
            $subject = 'MoU Kerja Sama - ' . $inf['full_name'];
            $body_html = nl2br(htmlspecialchars(
                "Halo kak {$inf['full_name']},\n\n"
                . "Terlampir dokumen MoU kerja sama dengan BHSKIN untuk dapat ditinjau.\n"
                . "Silakan dibaca kembali, dan hubungi kami jika ada hal yang ingin ditanyakan.\n\n"
                . "Terima kasih atas kerja samanya.\n\n"
                . "Salam,\n"
                . "Tim BHSKIN",
                ENT_QUOTES,
                'UTF-8'
            ));

            $sent = $this->app_mailer->send_html($email, $subject, $body_html, [
                'from_name' => 'BHSKIN - MoU System',
                'reply_to_email' => 'mou@bhskin.co.id',
                'reply_to_name' => 'BHSKIN - MoU System',
                'attachments' => [
                    [
                        'name' => $pdfName,
                        'content' => $pdfData,
                        'mime' => 'application/pdf',
                    ],
                ],
            ]);

            if (!$sent) {
                return $this->_json([
                    'success' => false,
                    'message' => 'Gagal kirim email MoU via SMTP.',
                ]);
            }

            return $this->_json([
                'success' => true,
                'used'    => 'smtp',
            ]);

        } catch (\Throwable $e) {
            return $this->_json([
                'success' => false,
                'message' => 'Gagal kirim email MoU via SMTP.',
                'error' => $e->getMessage(),
            ]);
        }


    }


    // ---------------- Helper ----------------

    private function _get_valid_token_or_redirect() {
        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            redirect($this->client->createAuthUrl());
        }
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            $this->session->unset_userdata('access_token');
            redirect($this->client->createAuthUrl());
        }
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                $this->session->unset_userdata('access_token');
                redirect($this->client->createAuthUrl());
            }
        }
        return $this->client->getAccessToken();
    }

    public function test_drive() {
        $raw = $this->session->userdata('access_token');
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token) { echo 'no token'; return; }
        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) { echo 'expired'; return; }

        $drive = new Google_Service_Drive($this->client);
        try {
            $about = $drive->about->get(['fields' => 'user(displayName,emailAddress)']);
            echo '<pre>'; print_r($about); echo '</pre>';
        } catch (Exception $e) {
            echo $e->getMessage(); 
        }
    }


    private function _get_valid_token_or_redirect_json() {
        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
            return null;
        }
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
            return null;
        }
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
                return null;
            }
        }
        return $this->client->getAccessToken();
    }

    private function _format_tanggal_id($ymd)
    {
        $bulan = [
            1=>'Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember'
        ];
        $ts = strtotime($ymd);
        if (!$ts) $ts = time();
        $d = (int)date('j', $ts);
        $m = (int)date('n', $ts);
        $y = (int)date('Y', $ts);
        return $d.' '.$bulan[$m].' '.$y;
    }

    private function _terbilang($angka)
    {
        $angka = abs($angka);
        $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

        if ($angka < 12) return trim($baca[$angka]);
        if ($angka < 20) return trim($this->_terbilang($angka - 10) . " Belas");
        if ($angka < 100) return trim($this->_terbilang(intval($angka / 10)) . " Puluh " . $this->_terbilang($angka % 10));
        if ($angka < 200) return trim("Seratus " . $this->_terbilang($angka - 100));
        if ($angka < 1000) return trim($this->_terbilang(intval($angka / 100)) . " Ratus " . $this->_terbilang($angka % 100));
        if ($angka < 2000) return trim("Seribu " . $this->_terbilang($angka - 1000));
        if ($angka < 1000000) return trim($this->_terbilang(intval($angka / 1000)) . " Ribu " . $this->_terbilang($angka % 1000));
        if ($angka < 1000000000) return trim($this->_terbilang(intval($angka / 1000000)) . " Juta " . $this->_terbilang($angka % 1000000));
        if ($angka < 1000000000000) return trim($this->_terbilang(intval($angka / 1000000000)) . " Miliar " . $this->_terbilang($angka % 1000000000));
        return (string)$angka;
    }

    private function _sanitize_filename($name)
    {
        $name = preg_replace('/[\/\\\\:*?"<>|]+/', '_', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        if (strlen($name) > 200) {
            $ext = '.pdf';
            $base = substr($name, 0, 200 - strlen($ext));
            $name = $base.$ext;
        }
        return $name;
    }

    private function _json($arr){
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($arr, JSON_UNESCAPED_UNICODE));
    }

    public function logout_google() {
        $this->session->unset_userdata('access_token');
        redirect('googlemou'); 
    }
        
}
