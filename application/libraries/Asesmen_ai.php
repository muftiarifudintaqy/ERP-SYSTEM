<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asesmen_ai — narasi AI untuk Big Five, Karakteristik, Aptitude,
 * dan analisis gabungan.
 *
 * DISC tetap ditangani Disc_ai yang sudah jalan. Berkas ini sengaja
 * berdiri sendiri supaya kalau ada yang salah di sini, DISC tidak
 * ikut rusak.
 *
 * Hasil disimpan ke kolom ai_json masing-masing tabel, jadi tiap
 * orang cuma sekali memanggil API per tes.
 *
 * Catatan penting: hasil STIFIn TIDAK pernah dikirim ke AI. Tipe itu
 * diturunkan dari tanggal lahir, bukan dari jawaban. Kalau AI diberi
 * data itu, dia akan menulis penjelasan meyakinkan tentang sesuatu
 * yang tidak diukur, dan itu justru membuatnya tampak kredibel.
 */
class Asesmen_ai
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
        $this->model    = $this->env('GEMINI_MODEL', 'gemini-3.6-flash');
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
     * PINTU UTAMA — satu tes
     * ============================================================== */

    private $tabel = [
        'bigfive'       => 'hrd_bigfive',
        'karakteristik' => 'hrd_karakteristik',
        'aptitude'      => 'hrd_aptitude',
    ];

    public function narasi($jenis, $d, $a, $paksa = FALSE)
    {
        @set_time_limit(0);   // jangan dipotong PHP, biar AI sempat menjawab
        if (!isset($this->tabel[$jenis])) return $this->cadangan($jenis, $d);

        if (!$paksa && !empty($d['ai_json'])) {
            $c = json_decode($d['ai_json'], TRUE);
            if ($this->sah($c)) { $c['_sumber'] = 'tersimpan'; return $c; }
        }

        if ($this->key === '') return $this->cadangan($jenis, $d, 'Kunci API belum diisi');

        $metode = 'prompt_' . $jenis;
        $h = $this->coba_ulang($this->$metode($d, $a));

        if ($h['ok'] && $this->sah($h['data'])) {
            $this->CI->db->where('id', (int)$d['id'])->update($this->tabel[$jenis], [
                'ai_json'  => json_encode($h['data'], JSON_UNESCAPED_UNICODE),
                'ai_model' => $this->model,
                'ai_at'    => date('Y-m-d H:i:s'),
            ]);
            $h['data']['_sumber'] = 'ai';
            return $h['data'];
        }

        return $this->cadangan($jenis, $d, $h['pesan']);
    }

    /* ================================================================
     * PINTU UTAMA — gabungan
     * ============================================================== */

    public function gabungan($a, $bagian, $paksa = FALSE)
    {
        @set_time_limit(0);   // jangan dipotong PHP, biar AI sempat menjawab
        if (!$paksa && !empty($a['ai_json'])) {
            $c = json_decode($a['ai_json'], TRUE);
            if ($this->sah($c)) { $c['_sumber'] = 'tersimpan'; return $c; }
        }

        if ($this->key === '') return $this->cadangan('gabungan', NULL, 'Kunci API belum diisi');

        $h = $this->coba_ulang($this->prompt_gabungan($a, $bagian));

        if ($h['ok'] && $this->sah($h['data'])) {
            $this->CI->db->where('id', (int)$a['id'])->update('hrd_asesmen', [
                'ai_json'  => json_encode($h['data'], JSON_UNESCAPED_UNICODE),
                'ai_model' => $this->model,
                'ai_at'    => date('Y-m-d H:i:s'),
            ]);
            $h['data']['_sumber'] = 'ai';
            return $h['data'];
        }

        return $this->cadangan('gabungan', NULL, $h['pesan']);
    }

    /* ================================================================
     * PROMPT
     * ============================================================== */

    private function pembuka($a)
    {
        $nama   = $a['nama'];
        $divisi = $a['divisi'] ?: 'belum diisi';
        return "Nama: {$nama}\nDivisi: {$divisi}";
    }

    private function aturan()
    {
        return <<<TXT
SIAPA KAMU
Psikolog industri berpengalaman yang sudah mendampingi ratusan
karyawan. Kamu dikenal karena dua hal: berani menyampaikan hal yang
tidak nyaman didengar, dan menyampaikannya dengan cara yang membuat
orang justru ingin memperbaiki diri, bukan membela diri.

YANG DICARI ORANG DARI TULISANMU
Bukan pujian. Mereka ingin melihat diri sendiri dengan lebih jelas,
termasuk bagian yang selama ini mereka hindari. Tulisan yang bagus
membuat pembaca berhenti sejenak dan berkata "ini memang saya".

ATURAN MENULIS
1. Pakai angka milik orang ini. Sebut angkanya. Kalimat yang bisa
   ditempel ke siapa saja adalah kegagalan.
2. Bahasa Indonesia, sapaan "Anda", nada seperti mentor yang
   menghargai lawan bicaranya. Bukan laporan, bukan ceramah.
3. Untuk area pengembangan: sebutkan konsekuensi nyatanya di
   pekerjaan, bukan sekadar menamai sifatnya. Bukan "kurang teliti",
   tetapi "detail yang terlewat baru ketahuan saat pekerjaan sudah
   sampai ke orang lain, dan itu memakan waktu tim untuk membetulkan".
   Orang berubah setelah melihat akibatnya, bukan setelah dilabeli.
4. Setiap kelemahan yang kamu sebut harus punya pasangan langkah di
   bagian tips. Menyebut masalah tanpa jalan keluar itu melukai tanpa
   guna.
5. Tips harus bisa dikerjakan minggu ini oleh orang biasa, dengan
   alat yang sudah dia punya. Bukan "tingkatkan komunikasi", tetapi
   "sebelum menutup rapat, ulangi satu kalimat berisi keputusan yang
   diambil dan siapa yang mengerjakan".
6. KATA YANG DILARANG: rendah, lemah, buruk, kurang baik, minim,
   defisit, bermasalah, tidak mampu. Angka kecil pada sebuah dimensi
   berarti perilaku itu jarang muncul, bukan berarti orangnya kurang.
   Tulis apa adanya tanpa memberi nilai.
7. Jangan mendiagnosis kondisi kesehatan mental apa pun.
8. Jangan menyimpulkan siapa yang lebih baik atau lebih layak
   dipekerjakan.
9. Kalau datanya tipis atau tidak membedakan, katakan terus terang di
   summary. Mengaku terbatas jauh lebih berharga daripada terdengar
   meyakinkan tanpa dasar. Pembaca yang tahu hasilnya kasar akan
   memakainya dengan lebih bijak.
10. Jangan memakai kalimat pembuka basa-basi seperti "Berdasarkan
   hasil asesmen Anda". Langsung ke isinya.

KELUARAN
Balas HANYA satu objek JSON. Tanpa markdown, tanpa pagar kode.
Semua bidang wajib diisi, jangan ada yang dikosongkan.

{
  "subtitle": "julukan 3-6 kata",
  "headline": "satu kalimat pembuka yang menyebut angka spesifiknya",
  "summary": "3-4 kalimat inti profil ini",
  "traits": ["4-6 ciri perilaku yang khas"],
  "strengths": ["4-5 kekuatan"],
  "development_areas": ["3-4 hal yang perlu dikembangkan, tulis sebagai peluang bukan kekurangan"],
  "work_style": "2-3 kalimat: di lingkungan seperti apa orang ini bekerja paling baik, dan di mana dia mulai tidak nyaman",
  "cocok_di_peran": "2 kalimat: jenis peran atau tugas yang sesuai, dengan alasannya",
  "kehidupan_sehari": "2 kalimat: bagaimana kecenderungan ini terlihat dalam keseharian kerja, contoh konkret",
  "tips": ["3-4 langkah konkret yang bisa langsung dicoba minggu ini"],
  "catatan_hrd": "2-3 kalimat untuk manajer: cara mendelegasikan, cara memberi umpan balik, dan risiko penempatan. Bukan untuk ditunjukkan ke yang dites"
}
TXT;
    }

    private function prompt_bigfive($d, $a)
    {
        $p = [
            'Keterbukaan (O)'    => (int)$d['persen_o'],
            'Kehati-hatian (C)'  => (int)$d['persen_c'],
            'Ekstraversi (E)'    => (int)$d['persen_e'],
            'Keramahan (A)'      => (int)$d['persen_a'],
            'Kepekaan emosi (N)' => (int)$d['persen_n'],
        ];
        arsort($p);
        $baris = [];
        foreach ($p as $k => $v) $baris[] = "{$k}: {$v}%";
        $tabel = implode("\n", $baris);
        $sebar = (int)$d['sebaran'];
        $orang = $this->pembuka($a);
        $aturan = $this->aturan();

        return <<<PROMPT
Kamu psikolog industri yang menulis interpretasi Big Five untuk PT Montera Strategic Group.

{$orang}

SKOR BIG FIVE
{$tabel}
Rentang sebaran (tertinggi dikurangi terendah): {$sebar} poin persen

CARA MEMBACA
- Tes ini 10 skenario, tiap skenario responden memilih SATU dari lima
  opsi yang masing-masing mewakili satu dimensi. Jadi total pilihan
  hanya 10, dibagi ke lima dimensi.
- Karena itu hasilnya kasar. Dimensi bernilai 0% bukan berarti dimensi
  itu tidak ada pada orangnya, hanya tidak pernah terpilih dalam
  sepuluh situasi tersebut. Katakan ini kalau ada dimensi bernilai 0.
- Sebaran di bawah 20 poin persen berarti profilnya datar. Jangan
  memaksakan satu dimensi seolah dominan.
- Skor N tinggi bukan gangguan dan bukan kelemahan. Itu kepekaan
  terhadap risiko dan tekanan. Tulis apa adanya, jangan menghakimi.

{$aturan}
PROMPT;
    }

    private function prompt_karakteristik($d, $a)
    {
        $orang  = $this->pembuka($a);
        $aturan = $this->aturan();
        $skor   = (int)$d['skor'];
        $maks   = (int)$d['skor_maks'];
        $persen = (int)$d['persen'];
        $kat    = $d['kategori'];
        $i      = (int)$d['skor_integritas'];
        $t      = (int)$d['skor_tanggungjwb'];
        $k      = (int)$d['skor_kerjasama'];

        return <<<PROMPT
Kamu psikolog industri yang menulis interpretasi tes karakteristik kerja untuk PT Montera Strategic Group.

{$orang}

SKOR
Total: {$skor} dari {$maks} ({$persen}%) - kategori {$kat}
Integritas: {$i}
Tanggung jawab: {$t}
Kerja sama: {$k}

CARA MEMBACA
- Tes ini 10 dilema kerja. Tiap opsi punya bobot 1 sampai 5. Semakin
  tinggi bobotnya, semakin mencerminkan integritas, tanggung jawab,
  atau kerja sama.
- Batas penting yang harus kamu pegang: skor tinggi TIDAK membuktikan
  seseorang berintegritas. Orang bisa memilih jawaban yang terdengar
  benar. Yang bermakna justru skor rendah, karena orang yang memilih
  opsi berbobot rendah sedang menyampaikan sesuatu tentang dirinya.
- Karena itu, untuk skor tinggi, tulis sebagai "menunjukkan kesadaran
  akan standar yang diharapkan", bukan sebagai bukti karakter.
- Bandingkan ketiga aspek. Aspek yang jauh lebih rendah dari dua
  lainnya adalah temuan yang paling berguna.

{$aturan}
PROMPT;
    }

    private function prompt_aptitude($d, $a)
    {
        $orang  = $this->pembuka($a);
        $aturan = $this->aturan();
        $benar  = (int)$d['benar'];
        $total  = (int)$d['total_soal'];
        $iq     = (int)$d['iq'];
        $kat    = $d['kategori_iq'];
        $n = (int)$d['benar_numerik']; $v = (int)$d['benar_verbal'];
        $l = (int)$d['benar_logika'];  $s = (int)$d['benar_spasial'];
        $durasi = $d['durasi_detik'] ? round($d['durasi_detik'] / 60) . ' menit' : 'tidak tercatat';

        return <<<PROMPT
Kamu psikolog industri yang menulis interpretasi tes kemampuan umum untuk PT Montera Strategic Group.

{$orang}

HASIL
Benar {$benar} dari {$total}
Estimasi skor internal: {$iq} ({$kat})
Numerik: {$n} dari 8
Verbal: {$v} dari 8
Logika: {$l} dari 8
Spasial: {$s} dari 6
Lama mengerjakan: {$durasi}

CARA MEMBACA
- Ini BUKAN tes IQ klinis. Angka estimasinya dihitung dengan rumus
  internal perusahaan dari 30 soal, tanpa kelompok norma pembanding.
  Jangan pernah menyebutnya sebagai IQ atau tingkat kecerdasan.
  Sebut sebagai "skor kemampuan umum pada tes ini".
- Yang paling berguna bukan angka totalnya, melainkan SELISIH ANTAR
  KATEGORI. Orang yang kuat di logika tapi lemah di numerik punya
  gambaran kerja yang berbeda dari sebaliknya. Fokus ke situ.
- Kalau lama mengerjakan di bawah 8 menit untuk 30 soal, sebutkan di
  catatan_hrd bahwa kecepatannya tidak wajar dan hasilnya perlu
  diverifikasi.
- Hasil tes seperti ini dipengaruhi kebiasaan mengerjakan soal dan
  latar pendidikan, bukan hanya kemampuan bawaan. Sebutkan itu.

{$aturan}
PROMPT;
    }

    private function prompt_gabungan($a, $b)
    {
        $orang  = $this->pembuka($a);
        $aturan = $this->aturan();

        $d = $b['disc'] ?? NULL;
        $f = $b['bigfive'] ?? NULL;
        $k = $b['karakteristik'] ?? NULL;
        $p = $b['aptitude'] ?? NULL;

        $s_disc = $d
            ? sprintf("D %d%%, I %d%%, S %d%%, C %d%% (tertinggi %s)",
                $d['net_d'], $d['net_i'], $d['net_s'], $d['net_c'], $d['tipe_utama'])
            : 'belum dikerjakan';

        $s_bf = $f
            ? sprintf("O %d%%, C %d%%, E %d%%, A %d%%, N %d%% (tertinggi %s)",
                $f['persen_o'], $f['persen_c'], $f['persen_e'],
                $f['persen_a'], $f['persen_n'], $f['dominan'])
            : 'belum dikerjakan';

        $s_kar = $k
            ? sprintf("%d dari %d (%d%%, %s) - integritas %d, tanggung jawab %d, kerja sama %d",
                $k['skor'], $k['skor_maks'], $k['persen'], $k['kategori'],
                $k['skor_integritas'], $k['skor_tanggungjwb'], $k['skor_kerjasama'])
            : 'belum dikerjakan';

        $s_apt = $p
            ? sprintf("benar %d dari %d, estimasi %d - numerik %d/8, verbal %d/8, logika %d/8, spasial %d/6",
                $p['benar'], $p['total_soal'], $p['iq'],
                $p['benar_numerik'], $p['benar_verbal'],
                $p['benar_logika'], $p['benar_spasial'])
            : 'belum dikerjakan';

        return <<<PROMPT
Kamu psikolog industri yang menyusun gambaran menyeluruh dari empat tes untuk PT Montera Strategic Group.

{$orang}

HASIL EMPAT TES
DISC: {$s_disc}
Big Five: {$s_bf}
Karakteristik: {$s_kar}
Kemampuan umum: {$s_apt}

TUGASMU
Ini bukan merangkum ulang keempatnya satu per satu. Yang dicari adalah
POLA YANG MUNCUL DI LEBIH DARI SATU TES, dan yang sama pentingnya,
BAGIAN YANG SALING BERTENTANGAN.

Contoh pola yang layak disebut: DISC tinggi di C sementara Big Five
tinggi di Kehati-hatian, itu saling menguatkan. Atau DISC tinggi di D
tapi kemampuan verbal rendah, itu ketegangan yang perlu diperhatikan
kalau perannya menuntut menyampaikan keputusan ke banyak orang.

Kalau kamu tidak menemukan pola yang benar-benar didukung angkanya,
katakan begitu. Jangan mengarang keterkaitan supaya terdengar dalam.

BATASAN
- Tes kemampuan umum di sini bukan tes IQ klinis. Jangan menyebut
  kecerdasan atau IQ.
- Skor karakteristik yang tinggi bukan bukti integritas, hanya
  menunjukkan kesadaran akan standar yang diharapkan.
- DISC dan Big Five di sini masing-masing hanya 10 soal, jadi hasilnya
  kasar. Sebut keterbatasan ini kalau angkanya berdekatan.
- Jangan menyarankan keputusan mempekerjakan, memecat, atau promosi.
  Tugasmu memberi bahan diskusi, bukan memutuskan nasib orang.

{$aturan}
PROMPT;
    }

    /* ================================================================
     * PANGGILAN HTTP
     * ============================================================== */

    /**
     * Coba model utama. Kalau tetap penuh, pindah ke model cadangan.
     *
     * 503 dari Gemini berarti model itu sedang kelebihan beban, bukan
     * ada yang salah dengan permintaannya. Model lain biasanya masih
     * lengang, jadi pindah model lebih berguna daripada menyerah.
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
        $h = ['ok' => FALSE, 'data' => NULL, 'pesan' => 'belum dijalankan'];
        for ($i = 1; $i <= $maks; $i++) {
            $h = $this->panggil($prompt);
            if ($h['ok']) return $h;

            if (!$this->sementara($h['pesan']) || $i === $maks) return $h;
            sleep($jeda);
            $jeda *= 2;
        }
        return $h;
    }

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
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);

        $resp = curl_exec($ch);
        $kode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === FALSE) {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Koneksi gagal: ' . $err];
        }
        if ($kode !== 200) {
            $j = json_decode($resp, TRUE);
            $p = $j['error']['message'] ?? substr($resp, 0, 180);
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => "HTTP {$kode}: {$p}"];
        }

        $j = json_decode($resp, TRUE);

        if (isset($j['promptFeedback']['blockReason'])) {
            return ['ok' => FALSE, 'data' => NULL,
                    'pesan' => 'Diblokir: ' . $j['promptFeedback']['blockReason']];
        }

        $teks = '';
        if (!empty($j['candidates'][0]['content']['parts'])) {
            foreach ($j['candidates'][0]['content']['parts'] as $bagian) {
                if (!empty($bagian['thought'])) continue;   // proses berpikir, bukan jawaban
                if (isset($bagian['text'])) $teks .= $bagian['text'];
            }
        }

        if (($j['candidates'][0]['finishReason'] ?? '') === 'MAX_TOKENS') {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Jawaban terpotong'];
        }
        if (trim($teks) === '') {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Balasan kosong'];
        }

        $data = $this->urai($teks);
        if ($data === NULL) {
            return ['ok' => FALSE, 'data' => NULL, 'pesan' => 'Balasan bukan JSON yang sah'];
        }
        return ['ok' => TRUE, 'data' => $data, 'pesan' => ''];
    }

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

    private function sah($d)
    {
        if (!is_array($d)) return FALSE;
        foreach (['summary', 'strengths', 'development_areas'] as $k) {
            if (empty($d[$k])) return FALSE;
        }
        return is_array($d['strengths']);
    }

    /* ================================================================
     * CADANGAN
     * ============================================================== */

    private function cadangan($jenis, $d = NULL, $alasan = '')
    {
        $t = [
            'bigfive' => [
                'subtitle' => 'Profil Big Five',
                'summary'  => 'Skor tiap dimensi sudah tercatat di grafik. Penjelasan rincinya akan muncul setelah analisis otomatis berhasil dijalankan.',
                'strengths' => ['Skor mentah tersimpan lengkap dan bisa dianalisis ulang kapan saja'],
                'development_areas' => ['Analisis rinci menunggu layanan AI tersedia'],
            ],
            'karakteristik' => [
                'subtitle' => 'Profil Karakteristik Kerja',
                'summary'  => 'Skor per aspek sudah tercatat. Penjelasan rincinya akan muncul setelah analisis otomatis berhasil dijalankan.',
                'strengths' => ['Skor per aspek tersimpan dan bisa dibandingkan antar karyawan'],
                'development_areas' => ['Analisis rinci menunggu layanan AI tersedia'],
            ],
            'aptitude' => [
                'subtitle' => 'Hasil Kemampuan Umum',
                'summary'  => 'Jumlah benar per kategori sudah tercatat. Penjelasan rincinya akan muncul setelah analisis otomatis berhasil dijalankan.',
                'strengths' => ['Jawaban mentah tersimpan, jadi rumus penilaian bisa diubah tanpa tes ulang'],
                'development_areas' => ['Analisis rinci menunggu layanan AI tersedia'],
            ],
            'gabungan' => [
                'subtitle' => 'Gambaran Menyeluruh',
                'summary'  => 'Keempat hasil tes sudah tersimpan. Gambaran menyeluruhnya akan muncul setelah analisis otomatis berhasil dijalankan.',
                'strengths' => ['Semua skor tersimpan utuh'],
                'development_areas' => ['Analisis menyeluruh menunggu layanan AI tersedia'],
            ],
        ];

        $n = $t[$jenis] ?? $t['gabungan'];
        $n['_sumber'] = 'cadangan';
        $n['_alasan'] = $alasan;
        return $n;
    }
}
