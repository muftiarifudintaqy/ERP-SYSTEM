<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Disc_ai — narasi hasil DISC lewat Gemini.
 *
 * Terisolasi: tidak menyentuh library, model, atau controller lain.
 * Kalau API mati / kena limit / key salah, otomatis jatuh ke teks
 * cadangan supaya halaman hasil tidak pernah kosong.
 *
 * Pakai:
 *   $this->load->library('disc_ai');
 *   $narasi = $this->disc_ai->narasi($baris_hrd_disc);
 */
class Disc_ai
{
    private $CI;
    private $key;
    private $model;
    private $cadangan_model;
    private $groq_key;
    private $groq_model;
    private $groq_utama;
    private $timeout = 60;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->key   = $this->env('GEMINI_API_KEY', '');
        $this->model = $this->env('GEMINI_MODEL', 'gemini-3.6-flash');
        $this->cadangan_model = $this->env('GEMINI_MODEL_CADANGAN', 'gemini-3.1-flash-lite');
        $this->groq_key   = $this->env('GROQ_API_KEY', '');
        $this->groq_model = $this->env('GROQ_MODEL', 'llama-3.3-70b-versatile');
        $this->groq_utama = ($this->env('AI_UTAMA', 'gemini') === 'groq');
    }

    private function env($nama, $bawaan = '')
    {
        if (function_exists('app_env')) {
            $v = app_env($nama);
            if ($v !== null && $v !== '') return $v;
        }
        $v = getenv($nama);
        return ($v === false || $v === '') ? $bawaan : $v;
    }

    /* ================================================================
     * PINTU UTAMA
     * ============================================================== */

    /**
     * @param array $d  satu baris tabel hrd_disc
     * @param bool  $paksa  true = abaikan cache, panggil ulang AI
     * @return array  struktur narasi siap tampil
     */
    public function narasi($d, $paksa = FALSE)
    {
        @set_time_limit(0);   // jangan dipotong PHP, biar AI sempat menjawab
        // 1. Sudah pernah dinilai? pakai yang tersimpan.
        if (!$paksa && !empty($d['ai_json'])) {
            $c = json_decode($d['ai_json'], TRUE);
            if ($this->sah($c)) {
                $c['_sumber'] = 'tersimpan';
                return $c;
            }
        }

        // 2. Tidak ada key -> langsung cadangan, jangan buang waktu.
        if ($this->key === '') {
            return $this->cadangan($d, 'Kunci API belum diisi di .env');
        }

        // 3. Panggil Gemini.
        $hasil = $this->coba_ulang($this->prompt($d));

        if ($hasil['ok'] && $this->sah($hasil['data'])) {
            $this->simpan($d['id'], $hasil['data']);
            $hasil['data']['_sumber'] = 'ai';
            return $hasil['data'];
        }

        return $this->cadangan($d, $hasil['pesan']);
    }


    /**
     * Panggil Gemini, ulangi kalau servernya sedang penuh.
     *
     * 503 dan 429 itu keadaan sementara: server sibuk atau kuota
     * sesaat. Menunggu sebentar lalu mencoba lagi biasanya berhasil.
     * Kesalahan lain tidak diulang, karena mengulang permintaan yang
     * salah tetap akan salah.
     */
    private function coba_ulang($prompt, $maks = 3)
    {
        // Groq lebih cepat, jadi dicoba lebih dulu kalau kuncinya ada.
        // Gemini tetap siaga di belakang; keduanya perusahaan berbeda
        // sehingga gangguan di satu pihak tidak menjatuhkan keduanya.
        if ($this->groq_utama && $this->groq_key !== '') {
            $g = $this->panggil_groq($prompt);
            if ($g['ok']) return $g;
        }

        $h = $this->coba_satu_model($prompt, $maks);
        if ($h['ok']) return $h;

        if ($this->cadangan_model && $this->cadangan_model !== $this->model
            && $this->sementara($h['pesan'])) {
            $semula = $this->model;
            $this->model = $this->cadangan_model;
            $h2 = $this->coba_satu_model($prompt, 2);
            if ($h2['ok']) return $h2;
            $this->model = $semula;
            $h['pesan'] .= ' (model cadangan juga gagal)';
        }

        // Penyedia kedua. Gemini dan Groq perusahaan berbeda, jadi
        // gangguan di satu pihak tidak menjatuhkan keduanya.
        if ($this->groq_key !== '') {
            $g = $this->panggil_groq($prompt);
            if ($g['ok']) return $g;
            $h['pesan'] .= ' | Groq: ' . $g['pesan'];
        }
        return $h;
    }

    /** Groq memakai bentuk permintaan ala OpenAI, bukan ala Gemini. */
    private function panggil_groq($prompt)
    {
        $body = [
            'model'    => $this->groq_model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->groq_key,
            ],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);
        $resp = curl_exec($ch);
        $kode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === FALSE) return ['ok'=>FALSE,'data'=>NULL,'pesan'=>'koneksi '.$err];
        if ($kode !== 200) {
            $j = json_decode($resp, TRUE);
            return ['ok'=>FALSE,'data'=>NULL,
                    'pesan'=>"HTTP {$kode} " . ($j['error']['message'] ?? substr($resp,0,120))];
        }

        $j = json_decode($resp, TRUE);
        $teks = $j['choices'][0]['message']['content'] ?? '';
        if (trim($teks) === '') return ['ok'=>FALSE,'data'=>NULL,'pesan'=>'balasan kosong'];

        $data = $this->urai($teks);
        if ($data === NULL) return ['ok'=>FALSE,'data'=>NULL,'pesan'=>'bukan JSON sah'];

        $this->model = $this->groq_model;   // dicatat di kolom ai_model
        return ['ok'=>TRUE,'data'=>$data,'pesan'=>''];
    }

    private function sementara($pesan)
    {
        return (strpos($pesan, '503') !== FALSE)
            || (strpos($pesan, '429') !== FALSE)
            || (stripos($pesan, 'overload') !== FALSE)
            || (stripos($pesan, 'high demand') !== FALSE)
            || (stripos($pesan, 'unavailable') !== FALSE);
    }

    private function coba_satu_model($prompt, $maks = 3)
    {
        $jeda = 2;
        for ($i = 1; $i <= $maks; $i++) {
            $h = $this->panggil($prompt);
            if ($h['ok']) return $h;

            if (!$this->sementara($h['pesan']) || $i === $maks) return $h;

            sleep($jeda);
            $jeda *= 2;   // 2 detik, lalu 4 detik
        }
        return $h;
    }

    /* ================================================================
     * PROMPT
     * ============================================================== */

    private function prompt($d)
    {
        $persen = [
            'D' => (int)$d['net_d'],
            'I' => (int)$d['net_i'],
            'S' => (int)$d['net_s'],
            'C' => (int)$d['net_c'],
        ];
        arsort($persen);
        $urut = [];
        foreach ($persen as $k => $v) $urut[] = "$k={$v}%";
        $peringkat = implode(', ', $urut);

        // Normalisasi ipsatif: selisih tiap dimensi dari rata-rata orang ini
        $rerata = array_sum($persen) / 4;
        $rel    = [];
        foreach ($persen as $k => $v) {
            $rel[] = sprintf('%s=%+.1f', $k, $v - $rerata);
        }
        $relatif = implode(', ', $rel);
        $sebar   = max($persen) - min($persen);

        $nama    = $d['nama'];
        $jabatan = $d['jabatan'] ?: 'belum diisi';

        $tabel = sprintf(
            "Dimensi | Total poin | Persentase\n" .
            "D | %d | %d%%\n" .
            "I | %d | %d%%\n" .
            "S | %d | %d%%\n" .
            "C | %d | %d%%",
            $d['most_d'], $d['net_d'],
            $d['most_i'], $d['net_i'],
            $d['most_s'], $d['net_s'],
            $d['most_c'], $d['net_c']
        );

        return <<<PROMPT
Kamu psikolog industri yang menulis interpretasi DISC untuk PT Montera Strategic Group.

DATA KARYAWAN
Nama: {$nama}
Jabatan: {$jabatan}

SKOR DISC (format skala penilaian, bukan pilihan paksa)
{$tabel}

Peringkat: {$peringkat}
Selisih dari rata-rata diri sendiri: {$relatif}
Rentang sebaran (tertinggi dikurangi terendah): {$sebar} poin persen
Tipe utama: {$d['tipe_utama']}
Tipe kedua: {$d['tipe_kedua']}

CARA MEMBACA SKOR
- Responden menilai tiap pernyataan secara terpisah pada skala. Persentase =
  total nilai dibagi nilai maksimum dimensi itu. Rentangnya 0-100%, tidak
  pernah negatif.
- Karena tiap pernyataan dinilai terpisah, angka mutlak dipengaruhi gaya
  menjawab. Orang yang murah nilai akan tinggi di semua dimensi.
- Karena itu yang paling bermakna adalah SELISIH DARI RATA-RATA DIRI SENDIRI,
  bukan persentase mentahnya. Nilai positif berarti dimensi itu menonjol
  dibanding sisi lain orang tersebut.
- Rentang sebaran menunjukkan setajam apa profilnya:
  - di bawah 8 poin persen: profil datar, tidak ada dominan yang jelas.
    WAJIB kamu sebutkan terus terang bahwa hasil ini kurang membedakan dan
    sebaiknya dibaca hati-hati atau tes diulang. Jangan memaksakan satu tipe
    seolah dominan.
  - 8 sampai 20: kecenderungan sedang, sebut sebagai kecenderungan bukan
    kepastian.
  - di atas 20: dominan jelas.
- Kalau semua dimensi di atas 90%, itu tanda responden memberi nilai tinggi
  ke hampir semua pernyataan. Sebutkan ini apa adanya di catatan_hrd.

ATURAN MENULIS
1. Gunakan angka spesifik milik orang ini. Jangan menulis kalimat yang bisa
   ditempel ke siapa saja.
2. Bahasa Indonesia, sapaan "Anda", nada hangat tapi jujur.
3. Sebut kombinasi antar dimensi, bukan cuma yang tertinggi. Terutama sorot
   dimensi dengan NET paling rendah karena itu yang paling menjelaskan
   keterbatasannya.
4. Jangan mendiagnosis kondisi kesehatan mental apa pun.
5. DISC mengukur gaya perilaku di tempat kerja, bukan kecerdasan atau nilai
   seseorang. Jangan menyimpulkan siapa yang lebih baik.
6. "catatan_hrd" ditulis untuk manajer, bukan untuk orang yang dites. Isi hal
   praktis: cara mendelegasikan, cara memberi umpan balik, risiko penempatan.

KELUARAN
Balas HANYA satu objek JSON. Tanpa markdown, tanpa pagar kode, tanpa
penjelasan di luar JSON.

{
  "subtitle": "julukan 3-6 kata untuk profil ini",
  "headline": "kalimat pembuka yang menyebut angka skornya",
  "summary": "2-3 kalimat inti profil",
  "traits": ["4-6 ciri perilaku"],
  "strengths": ["3-5 kekuatan"],
  "development_areas": ["3-4 area pengembangan"],
  "work_style": "1-2 kalimat gaya kerja",
  "cocok_di_peran": "1-2 kalimat peran yang cocok",
  "tips": ["3 saran konkret"],
  "catatan_hrd": "2-3 kalimat untuk manajer"
}
PROMPT;
    }

    /* ================================================================
     * PANGGILAN HTTP
     * ============================================================== */

    private function panggil($prompt)
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
             . rawurlencode($this->model) . ':generateContent';

        $body = [
            'contents' => [[
                'role'  => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature'      => 0.7,
                'maxOutputTokens'  => 8192,
                'responseMimeType' => 'application/json',
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->key,
            ],
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);

        $resp = curl_exec($ch);
        $kode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === FALSE) {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Koneksi gagal: ' . $err];
        }

        if ($kode === 429) {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Kuota gratis harian habis (429)'];
        }

        if ($kode !== 200) {
            $j = json_decode($resp, TRUE);
            $p = isset($j['error']['message']) ? $j['error']['message'] : substr($resp, 0, 200);
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => "HTTP {$kode}: {$p}"];
        }

        $j = json_decode($resp, TRUE);

        // Diblokir filter keamanan Gemini
        if (isset($j['promptFeedback']['blockReason'])) {
            return ['ok' => FALSE, 'data' => NULL,
                    'pesan' => 'Diblokir: ' . $j['promptFeedback']['blockReason']];
        }

        $teks = '';
        if (!empty($j['candidates'][0]['content']['parts'])) {
            foreach ($j['candidates'][0]['content']['parts'] as $p) {
                // Model penalar mengirim bagian "thought" berisi proses berpikir.
                // Itu bukan jawaban, jadi dilewati.
                if (!empty($p['thought'])) continue;
                if (isset($p['text'])) $teks .= $p['text'];
            }
        }

        // Kehabisan token sebelum JSON selesai
        if (isset($j['candidates'][0]['finishReason'])
            && $j['candidates'][0]['finishReason'] === 'MAX_TOKENS') {
            return ['ok' => FALSE, 'data' => NULL,
                    'pesan' => 'Jawaban terpotong, maxOutputTokens kurang'];
        }

        if (trim($teks) === '') {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Balasan kosong dari model'];
        }

        $data = $this->urai($teks);
        if ($data === NULL) {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Balasan bukan JSON yang sah'];
        }

        return ['ok' => TRUE, 'data' => $data, 'pesan' => ''];
    }

    /**
     * Buang pagar kode kalau model tetap menambahkannya, lalu ambil
     * objek JSON pertama sampai kurung penutup terakhir.
     */
    private function urai($teks)
    {
        $t = trim($teks);
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
        $t = preg_replace('/\s*```$/', '', $t);

        $data = json_decode($t, TRUE);
        if (is_array($data)) return $data;

        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a !== FALSE && $b !== FALSE && $b > $a) {
            $data = json_decode(substr($t, $a, $b - $a + 1), TRUE);
            if (is_array($data)) return $data;
        }
        return NULL;
    }

    /* ================================================================
     * VALIDASI & PENYIMPANAN
     * ============================================================== */

    private function sah($d)
    {
        if (!is_array($d)) return FALSE;
        foreach (['summary', 'traits', 'strengths', 'development_areas'] as $k) {
            if (empty($d[$k])) return FALSE;
        }
        return is_array($d['traits']) && is_array($d['strengths']);
    }

    private function simpan($id, $data)
    {
        $this->CI->db->where('id', (int)$id)->update('hrd_disc', [
            'ai_json'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            'ai_model' => $this->model,
            'ai_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /* ================================================================
     * CADANGAN — dipakai kalau AI gagal
     * ============================================================== */

    private function cadangan($d, $alasan = '')
    {
        $t = strtoupper($d['tipe_utama'] ?: 'D');
        $p = $this->tabel();
        $n = isset($p[$t]) ? $p[$t] : $p['D'];
        $n['_sumber'] = 'cadangan';
        $n['_alasan'] = $alasan;
        $n['headline'] = $n['subtitle'] . ' — ' . $t . ' di ' . (int)$d['net_' . strtolower($t)] . '%';
        return $n;
    }

    private function tabel()
    {
        return [
            'D' => [
                'subtitle' => 'Penggerak & Pengambil Keputusan',
                'summary'  => 'Anda berorientasi hasil, cepat mengambil keputusan, dan nyaman menghadapi tantangan langsung.',
                'traits'   => ['Tegas dan langsung ke inti', 'Berani mengambil risiko terukur', 'Fokus pada hasil akhir', 'Nyaman memimpin'],
                'strengths' => ['Eksekusi cepat', 'Berani mengambil keputusan sulit', 'Tahan tekanan tenggat', 'Inisiatif tinggi'],
                'development_areas' => ['Bisa terkesan kurang sabar', 'Perlu lebih banyak mendengarkan', 'Detail kadang terlewat'],
                'work_style' => 'Unggul ketika diberi wewenang dan target yang jelas, kurang nyaman dengan proses yang berbelit.',
                'cocok_di_peran' => 'Peran yang menuntut keputusan cepat: operasional, penjualan, kepemimpinan lini.',
                'tips' => ['Beri jeda sebelum memutuskan hal besar', 'Tanyakan pendapat tim sebelum menetapkan arah', 'Delegasikan detail ke orang yang teliti'],
                'catatan_hrd' => 'Beri sasaran dan kebebasan cara. Umpan balik sebaiknya langsung dan berbasis hasil.',
            ],
            'I' => [
                'subtitle' => 'Komunikator & Penggerak Suasana',
                'summary'  => 'Anda mendapat energi dari interaksi, mudah membangun relasi, dan menular semangatnya ke tim.',
                'traits'   => ['Antusias dan ekspresif', 'Mudah bergaul', 'Persuasif', 'Optimis'],
                'strengths' => ['Membangun jaringan dengan cepat', 'Komunikasi verbal kuat', 'Menghidupkan suasana tim', 'Luwes menghadapi orang baru'],
                'development_areas' => ['Detail kadang terlewat', 'Bisa terlalu optimis menilai risiko', 'Perlu tindak lanjut yang konsisten'],
                'work_style' => 'Unggul di peran yang banyak berhadapan dengan orang, kurang nyaman bekerja sendirian terlalu lama.',
                'cocok_di_peran' => 'Penjualan, pemasaran, layanan pelanggan, pelatihan.',
                'tips' => ['Catat komitmen supaya tidak terlupa', 'Sisihkan waktu untuk kerja fokus tanpa gangguan', 'Cek ulang detail sebelum menyerahkan hasil'],
                'catatan_hrd' => 'Beri pengakuan terbuka dan ruang berinteraksi. Pastikan ada sistem pengingat untuk tenggat.',
            ],
            'S' => [
                'subtitle' => 'Penjaga Stabilitas & Pendukung Tim',
                'summary'  => 'Anda konsisten, sabar, dan menjadi penopang yang bisa diandalkan dalam tim.',
                'traits'   => ['Sabar dan tenang', 'Setia dan dapat diandalkan', 'Pendengar yang baik', 'Menghindari konflik tidak perlu'],
                'strengths' => ['Konsistensi jangka panjang', 'Membangun kepercayaan', 'Meredakan ketegangan tim', 'Tekun pada pekerjaan berulang'],
                'development_areas' => ['Butuh waktu menyesuaikan diri dengan perubahan', 'Bisa terlalu mengalah', 'Sulit menyampaikan keberatan'],
                'work_style' => 'Unggul di lingkungan yang stabil dan terstruktur, kurang nyaman dengan perubahan mendadak.',
                'cocok_di_peran' => 'Administrasi, dukungan operasional, gudang, layanan internal.',
                'tips' => ['Latih menyampaikan keberatan lebih awal', 'Minta waktu persiapan saat ada perubahan', 'Kenali batas beban kerja sendiri'],
                'catatan_hrd' => 'Sampaikan perubahan jauh hari dengan alasan yang jelas. Tanyakan pendapatnya secara aktif karena jarang bersuara sendiri.',
            ],
            'C' => [
                'subtitle' => 'Analis Teliti & Penjaga Mutu',
                'summary'  => 'Anda bekerja berdasarkan data, memperhatikan detail, dan menjaga standar tetap terpenuhi.',
                'traits'   => ['Analitis dan teliti', 'Sistematis', 'Mengikuti prosedur', 'Berbasis fakta'],
                'strengths' => ['Akurasi tinggi', 'Kesadaran mutu', 'Berpikir kritis', 'Pendekatan terstruktur'],
                'development_areas' => ['Perfeksionis, bisa berlebihan', 'Terlalu lama menganalisis', 'Kurang lentur pada proses baru'],
                'work_style' => 'Unggul ketika standar dan ekspektasi jelas, kurang nyaman bila diminta memutuskan tanpa data cukup.',
                'cocok_di_peran' => 'Keuangan, kendali mutu, administrasi, analisis data, TI.',
                'tips' => ['Tetapkan batas waktu analisis', 'Terima bahwa "cukup baik" kadang memadai', 'Sampaikan temuan lebih awal, jangan menunggu sempurna'],
                'catatan_hrd' => 'Beri data dan alasan di balik keputusan. Hindari mendesak keputusan mendadak tanpa bahan.',
            ],
        ];
    }
}
