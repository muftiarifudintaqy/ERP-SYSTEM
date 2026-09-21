<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Alur asesmen karyawan, tanpa login.
 *
 *   /asesmen                       form data diri
 *   /asesmen/tes/{kode}/{jenis}    halaman soal
 *   /asesmen/hasil/{kode}/{jenis}  hasil satu tes
 *   /asesmen/selesai/{kode}        hasil gabungan
 *
 * Urutan tes: disc -> bigfive -> karakteristik -> aptitude.
 * Kolom `tahap` di hrd_asesmen yang menjaga urutannya, jadi peserta
 * boleh menutup browser di tengah jalan lalu membuka link yang sama.
 *
 * Memakai routing bawaan CodeIgniter, jadi routes.php tidak disentuh.
 */
class Asesmen extends CI_Controller
{
    /** Urutan tes. Mengubah urutan cukup di sini. */
    // Big Five dikeluarkan atas keputusan Pak Vikram. Tabel dan data
    // lamanya sengaja dibiarkan tersimpan, bukan dihapus.
    private $urutan = ['disc', 'karakteristik', 'aptitude'];

    private $judul = [
        'disc'          => 'Test DISC',
        'bigfive'       => 'Test Big Five',
        'karakteristik' => 'Test Karakteristik',
        'aptitude'      => 'Test IQ',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form']);
        $this->load->library(['session', 'asesmen_skor']);
    }

    private function _view($berkas, $data = [])
    {
        $data['judul'] = $data['judul'] ?? 'Asesmen Montera';
        $this->load->view('publik/_atas', $data);
        $this->load->view($berkas, $data);
        $this->load->view('publik/_bawah', $data);
    }

    /* ================================================================
     * 1. DATA DIRI
     * ============================================================== */

    public function index()
    {
        $this->_view('publik/asesmen_mulai', [
            'judul' => 'Asesmen Karyawan Montera',
            'lama'  => $this->session->flashdata('lama') ?: [],
        ]);
    }

    public function mulai()
    {
        $p = $this->input->post(NULL, TRUE);

        $nama    = trim($p['nama'] ?? '');
        $divisi  = trim($p['divisi'] ?? '');
        $email   = trim($p['email'] ?? '');
        $lahir   = trim($p['tanggal_lahir'] ?? '');
        $goldar  = trim($p['gol_darah'] ?? '');

        $salah = [];
        if ($nama === '')                          $salah[] = 'Nama wajib diisi.';
        if ($email === '')                         $salah[] = 'Email wajib diisi.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
                                                   $salah[] = 'Format email tidak benar.';
        if ($lahir === '')                         $salah[] = 'Tanggal lahir wajib diisi.';
        elseif (!date_create($lahir))              $salah[] = 'Tanggal lahir tidak terbaca.';
        // "Tidak tahu" ikut diterima: sebagian orang memang belum pernah
        // memeriksanya, dan memaksa menebak hanya membuat datanya salah.
        if (!in_array($goldar, ['A','B','AB','O','Tidak tahu'], TRUE))
                                                   $salah[] = 'Golongan darah wajib dipilih.';

        if ($salah) {
            $this->session->set_flashdata('gagal', implode(' ', $salah));
            $this->session->set_flashdata('lama', $p);
            redirect('asesmen');
        }

        // Sudah pernah mengisi dan selesai? tunjukkan hasil lamanya.
        $ada = $this->db->select('kode, tahap')
                        ->where('email', $email)
                        ->order_by('id', 'DESC')->limit(1)
                        ->get('hrd_asesmen')->row_array();
        if ($ada && $ada['tahap'] === 'selesai') {
            $this->session->set_flashdata('pesan',
                'Email ini sudah pernah menyelesaikan asesmen. Kalau perlu mengulang, hubungi HRD.');
            redirect('asesmen/selesai/' . $ada['kode']);
        }
        // Belum selesai? lanjutkan yang lama, jangan bikin baru.
        if ($ada) {
            redirect('asesmen/lanjut/' . $ada['kode']);
        }

        $st = $this->asesmen_skor->stifin($lahir);

        $karyawan = $this->db->select('id, jabatan')
                             ->where('email', $email)->limit(1)
                             ->get('hrd_karyawan')->row_array();

        $kode = $this->_kode_baru();

        $this->db->insert('hrd_asesmen', [
            'kode'          => $kode,
            'nama'          => $nama,
            'divisi'        => $divisi ?: ($karyawan['jabatan'] ?? NULL),
            'email'         => $email,
            'tanggal_lahir' => date('Y-m-d', strtotime($lahir)),
            'gol_darah'     => $goldar,
            'karyawan_id'   => $karyawan['id'] ?? NULL,
            'stifin_kode'   => $st['kode'] ?? NULL,
            'angka_nama'    => $this->asesmen_skor->angka_nama($nama),
            'tahap'         => 'disc',
        ]);

        redirect('asesmen/tes/' . $kode . '/disc');
    }

    /** Buka link lama, lompat ke tahap yang belum selesai. */
    public function lanjut($kode = NULL)
    {
        if (!$kode) redirect('asesmen');
        $a = $this->_asesmen($kode);
        if ($a['tahap'] === 'selesai') redirect('asesmen/selesai/' . $kode);
        redirect('asesmen/tes/' . $kode . '/' . $a['tahap']);
    }

    /* ================================================================
     * 2. HALAMAN SOAL
     * ============================================================== */

    public function tes($kode = NULL, $jenis = NULL)
    {
        if (!$kode || !$jenis) redirect('asesmen');
        $a = $this->_asesmen($kode);
        if (!in_array($jenis, $this->urutan, TRUE)) show_404();

        if ($a['tahap'] === 'selesai') redirect('asesmen/selesai/' . $kode);

        // Tidak boleh melompati tahap.
        if (array_search($jenis, $this->urutan, TRUE)
            > array_search($a['tahap'], $this->urutan, TRUE)) {
            redirect('asesmen/tes/' . $kode . '/' . $a['tahap']);
        }

        // Sudah pernah dikerjakan? tampilkan hasilnya, jangan diulang.
        if ($this->_sudah($a['id'], $jenis)) {
            redirect('asesmen/hasil/' . $kode . '/' . $jenis);
        }

        if ($jenis === 'aptitude' && !$this->session->userdata('apt_mulai_' . $a['id'])) {
            $this->session->set_userdata('apt_mulai_' . $a['id'], time());
        }

        $this->_view('publik/asesmen_tes', [
            'judul'    => $this->judul[$jenis],
            'a'        => $a,
            'jenis'    => $jenis,
            'soal'     => $this->_soal($jenis, $kode),
            'nomor'    => array_search($jenis, $this->urutan, TRUE) + 1,
            'dari'     => count($this->urutan),
        ]);
    }

    /* ================================================================
     * 3. SIMPAN JAWABAN
     * ============================================================== */

    public function simpan($kode = NULL, $jenis = NULL)
    {
        if (!$kode || !$jenis) redirect('asesmen');
        $a = $this->_asesmen($kode);
        if (!in_array($jenis, $this->urutan, TRUE)) show_404();

        $soal   = $this->_soal($jenis, $kode);
        $kirim  = $this->input->post(NULL, TRUE);
        $jawaban = [];

        foreach ($soal as $s) {
            $n = (int)($s['grup'] ?? $s['nomor']);
            $v = $kirim['j_' . $n] ?? '';
            if ($v === '' || $v === NULL) {
                $this->session->set_flashdata('gagal',
                    'Soal nomor ' . $n . ' belum dijawab. Semua soal wajib diisi.');
                redirect('asesmen/tes/' . $kode . '/' . $jenis);
            }
            $jawaban[$n] = $v;
        }

        $metode = '_simpan_' . $jenis;
        $this->$metode($a, $jawaban, $soal);

        // Naikkan tahap
        $i = array_search($jenis, $this->urutan, TRUE);
        $berikut = $this->urutan[$i + 1] ?? 'selesai';
        $ubah = ['tahap' => $berikut];
        if ($berikut === 'selesai') $ubah['selesai_at'] = date('Y-m-d H:i:s');

        // Ditambahkan, bukan ditimpa: satu peserta melewati tiga tes, dan
        // yang ingin diketahui HRD adalah jumlah seluruh sesi -- bukan
        // hanya tes terakhir. Angka ini catatan, bukan tuduhan: berpindah
        // tab bisa berarti membuka AI, bisa juga notifikasi masuk.
        $tab = (int)($kirim['pindah_tab'] ?? 0);
        if ($tab > 0) {
            $ubah['pindah_tab'] = (int)($a['pindah_tab'] ?? 0) + $tab;
        }
        $this->db->where('id', $a['id'])->update('hrd_asesmen', $ubah);

        redirect('asesmen/hasil/' . $kode . '/' . $jenis);
    }

    private function _simpan_disc($a, $jawaban, $soal)
    {
        $h = $this->asesmen_skor->disc($jawaban);
        $this->db->insert('hrd_disc', [
            'asesmen_id' => $a['id'],
            'karyawan_id'=> $a['karyawan_id'],
            'nama'       => $a['nama'],
            'email'      => $a['email'],
            'jabatan'    => $a['divisi'],
            'most_d' => $h['most']['D'], 'most_i' => $h['most']['I'],
            'most_s' => $h['most']['S'], 'most_c' => $h['most']['C'],
            'least_d'=> 0, 'least_i' => 0, 'least_s' => 0, 'least_c' => 0,
            'net_d'  => $h['persen']['D'], 'net_i' => $h['persen']['I'],
            'net_s'  => $h['persen']['S'], 'net_c' => $h['persen']['C'],
            'tipe_utama' => $h['utama'],
            'tipe_kedua' => $h['kedua'],
            'jawaban_json' => json_encode(['format' => 'skenario', 'jawaban' => $jawaban]),
        ]);
    }

    private function _simpan_bigfive($a, $jawaban, $soal)
    {
        $h = $this->asesmen_skor->bigfive($jawaban);
        $this->db->insert('hrd_bigfive', [
            'asesmen_id' => $a['id'],
            'nama'  => $a['nama'],
            'email' => $a['email'],
            'skor_o' => $h['skor']['O'], 'skor_c' => $h['skor']['C'],
            'skor_e' => $h['skor']['E'], 'skor_a' => $h['skor']['A'],
            'skor_n' => $h['skor']['N'],
            'persen_o' => $h['persen']['O'], 'persen_c' => $h['persen']['C'],
            'persen_e' => $h['persen']['E'], 'persen_a' => $h['persen']['A'],
            'persen_n' => $h['persen']['N'],
            'dominan'   => $h['dominan'],
            'pendukung' => $h['pendukung'],
            'sebaran'   => $h['sebar'],
            'jawaban_json' => json_encode($jawaban),
        ]);
    }

    private function _simpan_karakteristik($a, $jawaban, $soal)
    {
        $h = $this->asesmen_skor->karakteristik($jawaban, $soal);
        $this->db->insert('hrd_karakteristik', [
            'asesmen_id' => $a['id'],
            'nama'  => $a['nama'],
            'email' => $a['email'],
            'skor'      => $h['skor'],
            'skor_maks' => $h['maks'],
            'persen'    => $h['persen'],
            'kategori'  => $h['kategori'],
            'skor_integritas'  => $h['aspek']['integritas'],
            'skor_tanggungjwb' => $h['aspek']['tanggungjwb'],
            'skor_kerjasama'   => $h['aspek']['kerjasama'],
            'skor_ketahanan'   => $h['aspek']['ketahanan'],
            'jawaban_json' => json_encode($jawaban),
        ]);
    }

    private function _simpan_aptitude($a, $jawaban, $soal)
    {
        $h = $this->asesmen_skor->aptitude($jawaban, $soal);
        $mulai = $this->session->userdata('apt_mulai_' . $a['id']);
        $durasi = $mulai ? (time() - (int)$mulai) : NULL;

        $this->db->insert('hrd_aptitude', [
            'asesmen_id' => $a['id'],
            'nama'  => $a['nama'],
            'email' => $a['email'],
            'benar'  => $h['benar'], 'salah' => $h['salah'],
            'kosong' => $h['kosong'], 'total_soal' => $h['total'],
            'iq' => $h['iq'], 'kategori_iq' => $h['kategori_iq'],
            'benar_numerik' => $h['per_kategori']['Numerik'],
            'benar_verbal'  => $h['per_kategori']['Verbal'],
            'benar_logika'  => $h['per_kategori']['Logika'],
            'benar_spasial' => $h['per_kategori']['Spasial'],
            'mulai_at'     => $mulai ? date('Y-m-d H:i:s', (int)$mulai) : NULL,
            'selesai_at'   => date('Y-m-d H:i:s'),
            'durasi_detik' => $durasi,
            'jawaban_json' => json_encode($jawaban),
        ]);
    }

    /* ================================================================
     * 4. HASIL
     * ============================================================== */

    public function hasil($kode = NULL, $jenis = NULL)
    {
        if (!$kode || !$jenis) redirect('asesmen');
        $a = $this->_asesmen($kode);
        if (!in_array($jenis, $this->urutan, TRUE)) show_404();

        $d = $this->_ambil_hasil($a['id'], $jenis);
        if (!$d) redirect('asesmen/tes/' . $kode . '/' . $jenis);

        $i = array_search($jenis, $this->urutan, TRUE);
        $berikut = $this->urutan[$i + 1] ?? NULL;

        $this->_view('publik/asesmen_hasil', [
            'judul'   => 'Hasil ' . $this->judul[$jenis],
            'a'       => $a,
            'jenis'   => $jenis,
            'd'       => $d,
            'berikut' => $berikut,
            'nomor'   => $i + 1,
            'dari'    => count($this->urutan),
        ]);
    }

    public function selesai($kode = NULL)
    {
        if (!$kode) redirect('asesmen');
        $a = $this->_asesmen($kode);

        $bagian = [
            'disc'          => $this->_ambil_hasil($a['id'], 'disc'),
            'bigfive'       => $this->_ambil_hasil($a['id'], 'bigfive'),
            'karakteristik' => $this->_ambil_hasil($a['id'], 'karakteristik'),
            'aptitude'      => $this->_ambil_hasil($a['id'], 'aptitude'),
        ];

        // Gambaran menyeluruh hanya disusun kalau keempatnya sudah ada.
        // Menyusunnya dari data setengah jadi menghasilkan kesimpulan
        // yang salah, dan hasilnya tersimpan permanen di cache.
        $lengkap = TRUE;
        foreach ($bagian as $b) if (!$b) $lengkap = FALSE;

        $this->_view('publik/asesmen_selesai', [
            'judul'         => 'Hasil Asesmen',
            'a'             => $a,
            'disc'          => $bagian['disc'],
            'bigfive'       => $bagian['bigfive'],
            'karakteristik' => $bagian['karakteristik'],
            'aptitude'      => $bagian['aptitude'],
            'stifin'        => $a['stifin_kode']
                ? $this->db->get_where('hrd_stifin', ['kode' => $a['stifin_kode']])->row_array()
                : NULL,
        ]);
    }

    public function narasi($kode = NULL, $jenis = NULL)
    {
        // Lepas kunci berkas sesi. Tanpa ini, selama AI bekerja semua
        // permintaan lain dari browser yang sama ikut tertahan.
        if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }

        $this->output->set_content_type('application/json');
        if (!$kode || !$jenis) return $this->output->set_output(json_encode(['ok' => FALSE]));

        $a = $this->db->get_where('hrd_asesmen', ['kode' => $kode])->row_array();
        if (!$a) return $this->output->set_output(json_encode(['ok' => FALSE]));

        if ($jenis === 'gabungan') {
            $bagian = [];
            foreach ($this->urutan as $j) {
                $bagian[$j] = $this->_ambil_hasil($a['id'], $j);
                if (!$bagian[$j]) return $this->output->set_output(
                    json_encode(['ok' => FALSE, 'pesan' => 'Belum semua test selesai']));
            }
            $this->load->library('asesmen_ai');
            $n = $this->asesmen_ai->gabungan($a, $bagian);
            return $this->output->set_output(json_encode(['ok' => TRUE, 'ai' => $n]));
        }

        if (!in_array($jenis, $this->urutan, TRUE))
            return $this->output->set_output(json_encode(['ok' => FALSE]));

        $d = $this->_ambil_hasil($a['id'], $jenis);
        if (!$d) return $this->output->set_output(json_encode(['ok' => FALSE]));

        if ($jenis === 'disc') {
            $this->load->library('disc_ai');
            $n = $this->disc_ai->narasi($d);
        } else {
            $this->load->library('asesmen_ai');
            $n = $this->asesmen_ai->narasi($jenis, $d, $a);
        }
        return $this->output->set_output(json_encode(['ok' => TRUE, 'ai' => $n]));
    }

    /* ================================================================
     * PEMBANTU
     * ============================================================== */

    private function _asesmen($kode)
    {
        $a = $this->db->get_where('hrd_asesmen', ['kode' => $kode])->row_array();
        if (!$a) show_404();

        // Jangan percaya kolom tahap begitu saja. Kalau hasil sebuah tes
        // terhapus tapi tahapnya tidak diturunkan, peserta akan melompati
        // tes itu tanpa ada yang menyadarinya. Yang benar: test pertama
        // yang belum punya baris hasil, itulah tahap sebenarnya.
        $asli = $this->_tahap_asli($a['id']);
        if ($asli !== $a['tahap']) {
            $this->db->where('id', $a['id'])->update('hrd_asesmen', ['tahap' => $asli]);
            $a['tahap'] = $asli;
        }
        return $a;
    }

    private function _tahap_asli($asesmen_id)
    {
        foreach ($this->urutan as $j) {
            if (!$this->_sudah($asesmen_id, $j)) return $j;
        }
        return 'selesai';
    }

    private function _kode_baru()
    {
        do {
            $k = substr(str_replace(['+', '/', '='], '',
                 base64_encode(random_bytes(12))), 0, 12);
            $ada = $this->db->where('kode', $k)->count_all_results('hrd_asesmen');
        } while ($ada > 0);
        return $k;
    }

    private function _tabel($jenis)
    {
        return [
            'disc'          => 'hrd_disc',
            'bigfive'       => 'hrd_bigfive',
            'karakteristik' => 'hrd_karakteristik',
            'aptitude'      => 'hrd_aptitude',
        ][$jenis];
    }

    private function _sudah($asesmen_id, $jenis)
    {
        return $this->db->where('asesmen_id', $asesmen_id)
                        ->count_all_results($this->_tabel($jenis)) > 0;
    }

    private function _ambil_hasil($asesmen_id, $jenis)
    {
        return $this->db->where('asesmen_id', $asesmen_id)
                        ->order_by('id', 'DESC')->limit(1)
                        ->get($this->_tabel($jenis))->row_array();
    }

    /** Ambil soal, bentuknya diseragamkan supaya view-nya satu saja. */
    /**
     * Urutan soal diacak per peserta, dikunci pada kode asesmennya.
     *
     * Benihnya kode asesmen, bukan waktu: urutan harus tetap sama selama
     * orang itu mengerjakan. Kalau diacak ulang tiap halaman dimuat,
     * jawaban yang sudah diisi akan menempel pada soal yang berbeda.
     *
     * Ini juga yang membuat tangkapan layar orang lain tidak berguna --
     * hal yang tidak bisa dicegah browser, tapi bisa dibuat sia-sia.
     */
    private function _acak($daftar, $kode)
    {
        if (empty($kode)) return $daftar;
        mt_srand(crc32($kode));
        $kunci = [];
        foreach ($daftar as $i => $_) { $kunci[$i] = mt_rand(); }
        asort($kunci);
        $hasil = [];
        foreach (array_keys($kunci) as $i) { $hasil[] = $daftar[$i]; }
        mt_srand();
        return $hasil;
    }

    private function _soal($jenis, $kode = '')
    {
        if ($jenis === 'disc') {
            $baris = $this->db->order_by('grup', 'ASC')->order_by('posisi', 'ASC')
                              ->get('hrd_disc_soal')->result_array();
            $kel = [];
            foreach ($baris as $b) {
                $g = (int)$b['grup'];
                if (!isset($kel[$g])) {
                    $kel[$g] = ['nomor' => $g, 'grup' => $g,
                                'situasi' => $b['pertanyaan'], 'opsi' => []];
                }
                $kel[$g]['opsi'][] = ['nilai' => $b['dimensi'], 'teks' => $b['kata']];
            }
            return $this->_acak(array_values($kel), $kode);
        }

        if ($jenis === 'bigfive' || $jenis === 'karakteristik') {
            $tabel = $jenis === 'bigfive' ? 'hrd_bigfive_soal' : 'hrd_karakteristik_soal';
            $baris = $this->db->where('aktif', 1)->order_by('nomor', 'ASC')
                              ->get($tabel)->result_array();
            $keluar = [];
            foreach ($baris as $b) {
                $opsi = json_decode($b['opsi_json'], TRUE) ?: [];
                $daftar = [];
                foreach ($opsi as $i => $o) {
                    // Big Five kirim huruf dimensi, Karakteristik kirim indeks
                    $daftar[] = [
                        'nilai' => $jenis === 'bigfive' ? $o['dimensi'] : $i,
                        'teks'  => $o['teks'],
                    ];
                }
                $keluar[] = ['nomor' => (int)$b['nomor'], 'situasi' => $b['situasi'],
                             'opsi' => $daftar, 'opsi_json' => $b['opsi_json']];
            }
            return $this->_acak($keluar, $kode);
        }

        // aptitude
        $baris = $this->db->where('aktif', 1)->order_by('nomor', 'ASC')
                          ->get('hrd_aptitude_soal')->result_array();
        $keluar = [];
        foreach ($baris as $b) {
            $keluar[] = [
                'nomor'    => (int)$b['nomor'],
                'kategori' => $b['kategori'],
                'situasi'  => $b['soal'],
                'kunci'    => $b['kunci'],
                // Urutan pilihan ikut diacak, tapi 'nilai' tetap menempel
                // pada isinya: pilihan yang tadinya A tetap dikirim sebagai
                // A ke mana pun ia bergeser. Dengan begitu penilaian tidak
                // perlu tahu soal pengacakan sama sekali, dan kunci di
                // database tetap sah. Yang berubah hanya letaknya di layar,
                // sehingga "jawabannya B semua" tidak berarti apa-apa.
                'opsi'     => $this->_acak([
                    ['nilai' => 'A', 'teks' => $b['opsi_a']],
                    ['nilai' => 'B', 'teks' => $b['opsi_b']],
                    ['nilai' => 'C', 'teks' => $b['opsi_c']],
                    ['nilai' => 'D', 'teks' => $b['opsi_d']],
                ], $kode . '-' . $b['nomor']),
            ];
        }
        return $this->_acak($keluar, $kode);
    }
}
