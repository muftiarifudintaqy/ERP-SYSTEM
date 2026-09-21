<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

/**
 * Modul HRD Montera - Data Karyawan (HRD).
 * Hak akses memakai sistem izin ERP (tabel modules + role_permissions,
 * nama modul 'hrd'), jadi diatur lewat halaman Roles seperti modul lain.
 */
class Hrd extends BaseController
{
    protected $require_permissions = true;
    protected $show_403_on_deny    = true;
    protected $public_methods      = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hrd_model', 'hrd');
        $this->load->helper(['url', 'form', 'text']);
        $this->load->library(['session', 'form_validation', 'permission', 'template']);
        $this->load->config('disc_soal', TRUE);
    }

    /**
     * Pastikan user punya izin untuk aksi tertentu pada modul 'hrd'.
     * Diatur lewat halaman Roles, bukan lewat file ini.
     */
    private function _wajib($aksi)
    {
        $uid = $_SESSION['user']['id'] ?? 0;
        if (!$this->permission->check_permission($uid, 'hrd', $aksi)) {
            $this->session->set_flashdata('gagal',
                'Kamu tidak punya izin untuk ' . $aksi . ' di modul HRD. Hubungi admin kalau ini keliru.');
            redirect('hrd');
        }
    }

    private function _view($berkas, $data = [])
    {
        $uid = $_SESSION['user']['id'] ?? 0;
        $data['user']        = $_SESSION['user'] ?? [];
        $data['judul']       = $data['judul'] ?? 'Data Karyawan (HRD)';
        $data['menu_aktif']  = $data['menu_aktif'] ?? '';
        $data['title']       = $data['judul'] . ' - Data Karyawan (HRD)';
        $data['notif']       = $data['notif'] ?? '';
        $data['boleh_tambah'] = $this->permission->check_permission($uid, 'hrd', 'create');
        $data['boleh_ubah']   = $this->permission->check_permission($uid, 'hrd', 'edit');
        $data['boleh_hapus']  = $this->permission->check_permission($uid, 'hrd', 'delete');

        $isi  = $this->load->view('hrd/_atas', $data, TRUE);
        $isi .= $this->load->view($berkas, $data, TRUE);
        $isi .= $this->load->view('hrd/_bawah', $data, TRUE);

        $data['content'] = $isi;
        $this->load->view('TemplateDashboard', $data);
    }

    /** Upload berkas ke uploads/hrd/{folder}. Kembalikan nama file atau null. */
    private function _upload($field, $folder, $izin = 'jpg|jpeg|png|pdf|webp|heic')
    {
        if (empty($_FILES[$field]['name'])) return null;
        $dir = FCPATH . 'uploads/hrd/' . $folder . '/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => $izin,
            'max_size'      => 10240,
            'encrypt_name'  => TRUE,
        ], 'up');
        $this->up->initialize([
            'upload_path'   => $dir,
            'allowed_types' => $izin,
            'max_size'      => 10240,
            'encrypt_name'  => TRUE,
        ]);
        if (!$this->up->do_upload($field)) return null;
        $d = $this->up->data();
        return $d['file_name'];
    }

    /* ============================= DASHBOARD ============================= */

    public function index()
    {
        $this->_view('hrd/dashboard', [
            'judul'       => 'Ringkasan HRD',
            'menu_aktif'  => 'dashboard',
            'r'           => $this->hrd->ringkasan(),
            'belum_disc'  => $this->hrd->karyawan_belum_disc(),
            'tertunda'    => $this->hrd->asesmen_tertunda(),
            'aktivitas'   => $this->hrd->aktivitas_terbaru(10),
        ]);
    }

    /**
     * Kirim pengingat DISC ke satu karyawan, atau ke semua yang belum
     * mengisi sekaligus (tanpa argumen).
     *
     * Dua jalur sekaligus: push ke ponsel untuk yang sudah mengizinkan
     * notifikasi, dan kartu notifikasi di ERP untuk semua -- karena baru
     * 8 dari 19 orang yang punya langganan push, dan yang lain tidak akan
     * menerima apa pun kalau hanya mengandalkan jalur itu.
     */
    public function ingatkan_disc($karyawan_id = null)
    {
        $daftar = $this->hrd->karyawan_belum_disc();

        if ($karyawan_id !== null) {
            $daftar = array_filter($daftar, function ($k) use ($karyawan_id) {
                return (int)$k['id'] === (int)$karyawan_id;
            });
        }

        if (!$daftar) {
            $this->session->set_flashdata('pesan', 'Tidak ada yang perlu diingatkan.');
            redirect('hrd');
            return;
        }

        $judul = 'Pengisian DISC Test';
        $pesan = 'Mohon luangkan waktu untuk mengisi DISC Test di ERP. '
               . 'Buka menu asesmen atau tautan yang dibagikan HRD.';
        $url   = base_url('f/disc');

        $this->load->library('PushSender');
        $kirim = 0;
        $batch = [];

        foreach ($daftar as $k) {
            if (empty($k['user_id'])) continue;

            $batch[] = [
                'user_id'       => (int)$k['user_id'],
                'title'         => $judul,
                'message'       => $pesan,
                'type'          => 'warning',
                'category'      => 'team',
                'subcategory'   => 'team_update',
                'ref_url'       => $url,
                'related_table' => 'hrd_karyawan',
                'related_id'    => (int)$k['id'],
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            try {
                $this->pushsender->kirim_ke_user((int)$k['user_id'], $judul, $pesan, $url);
            } catch (Exception $e) {
                log_message('error', 'Push pengingat DISC gagal user '
                    . $k['user_id'] . ': ' . $e->getMessage());
            }
            $kirim++;
        }

        if ($batch) $this->db->insert_batch('notifications', $batch);

        $this->session->set_flashdata('pesan',
            'Pengingat terkirim ke ' . $kirim . ' orang.');
        redirect('hrd');
    }

    /* ============================== KARYAWAN ============================= */

    public function karyawan()
    {
        $filter = [
            'q'      => $this->input->get('q', TRUE),
            'divisi' => $this->input->get('divisi', TRUE),
            'status' => $this->input->get('status', TRUE),
        ];
        $this->_view('hrd/karyawan_list', [
            'judul'      => 'Data karyawan',
            'menu_aktif' => 'karyawan',
            'rows'       => $this->hrd->karyawan_list($filter),
            'divisi'     => $this->hrd->daftar_divisi(),
            'f'          => $filter,
        ]);
    }

    /** Tarik semua akun user ERP yang belum tercatat di data karyawan. */
    public function karyawan_impor()
    {
        $this->_wajib('create');
        $h = $this->hrd->impor_dari_user();

        if ($h['baru'] === 0 && $h['sudah_ada'] === 0) {
            $this->session->set_flashdata('gagal',
                'Tidak ada data yang bisa ditarik dari akun ERP. Kemungkinan nama kolom di tabel user berbeda - tambah manual saja.');
        } elseif ($h['baru'] === 0) {
            $this->session->set_flashdata('sukses',
                'Semua ' . $h['sudah_ada'] . ' akun ERP sudah ada di data karyawan. Tidak ada yang perlu ditambah.');
        } else {
            $this->session->set_flashdata('sukses',
                $h['baru'] . ' karyawan ditambahkan dari akun ERP (' . $h['sudah_ada'] . ' sudah ada sebelumnya). '
                . 'Data mereka baru berisi nama dan jabatan - minta mereka melengkapi lewat tautan form data karyawan.');
        }
        $this->hrd->log('impor_user_erp', $h['baru'] . ' baru');
        redirect('hrd/karyawan');
    }

    public function karyawan_detail($id)
    {
        $k = $this->hrd->karyawan($id);
        if (!$k) show_404();
        $this->hrd->log('lihat_karyawan', $k['nama']);
        $this->_view('hrd/karyawan_detail', [
            'judul'      => $k['nama'],
            'menu_aktif' => 'karyawan',
            'k'          => $k,
            'barang'     => $this->hrd->inventaris_list(['karyawan_id' => $k['id']]),
            'disc'       => $this->hrd->disc_terakhir_karyawan($k['id'], $k['email']),
            'profil'     => $this->config->item('disc_profil', 'disc_soal'),
        ]);
    }

    public function karyawan_form($id = null)
    {
        $this->_view('hrd/karyawan_form', [
            'judul'      => $id ? 'Ubah data karyawan' : 'Tambah karyawan',
            'menu_aktif' => 'karyawan',
            'k'          => $id ? $this->hrd->karyawan($id) : [],
        ]);
    }

    public function karyawan_simpan()
    {
        $this->_wajib($this->input->post('id') ? 'edit' : 'create');
        $id  = $this->input->post('id') ?: null;
        $p   = $this->input->post(NULL, TRUE);
        $data = $this->_ambil_field_karyawan($p);
        $data['sumber'] = 'hrd';

        if (empty($data['nama'])) {
            $this->session->set_flashdata('gagal', 'Nama wajib diisi.');
            redirect('hrd/karyawan_form/' . ($id ?: ''));
        }

        $ktp = $this->_upload('foto_ktp', 'ktp');
        if ($ktp) $data['foto_ktp'] = $ktp;
        $foto = $this->_upload('foto_profil', 'profil', 'jpg|jpeg|png|webp');
        if ($foto) $data['foto_profil'] = $foto;

        $baru = $this->hrd->karyawan_simpan($data, $id);
        $this->hrd->log($id ? 'ubah_karyawan' : 'tambah_karyawan', $data['nama']);
        $this->session->set_flashdata('sukses', 'Data ' . $data['nama'] . ' tersimpan.');
        redirect('hrd/karyawan_detail/' . $baru);
    }

    private function _ambil_field_karyawan($p)
    {
        $kolom = ['nama','jabatan','divisi','tanggal_masuk','nik','npwp','tempat_lahir','tanggal_lahir',
            'alamat_ktp','alamat_domisili','jenis_kelamin','agama','no_hp','email','pendidikan_terakhir',
            'status_pernikahan','nama_kontak_darurat','hubungan_kontak_darurat','telp_kontak_darurat',
            'jumlah_anak','nama_bank','nama_rekening','no_rekening','status_karyawan','catatan_hrd'];
        $out = [];
        foreach ($kolom as $c) {
            if (!isset($p[$c])) continue;
            $v = trim($p[$c]);
            if (in_array($c, ['tanggal_masuk','tanggal_lahir']) && $v === '') $v = null;
            $out[$c] = $v;
        }
        if (isset($out['jumlah_anak'])) $out['jumlah_anak'] = (int)$out['jumlah_anak'];
        return $out;
    }

    public function karyawan_hapus($id)
    {
        $this->_wajib('delete');
        $k = $this->hrd->karyawan($id);
        if ($k) {
            $this->hrd->karyawan_hapus($id);
            $this->hrd->log('hapus_karyawan', $k['nama']);
            $this->session->set_flashdata('sukses', 'Data ' . $k['nama'] . ' dihapus.');
        }
        redirect('hrd/karyawan');
    }

    /** Tampilkan berkas KTP / foto - hanya untuk yang sudah login. */
    public function berkas($folder, $nama)
    {
        $folder = preg_replace('/[^a-z_]/', '', $folder);
        $nama   = basename($nama);
        $path   = FCPATH . 'uploads/hrd/' . $folder . '/' . $nama;
        if (!is_file($path)) show_404();
        $this->hrd->log('buka_berkas', $folder . '/' . $nama);
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $nama . '"');
        header('X-Robots-Tag: noindex, nofollow');
        readfile($path);
        exit;
    }

    public function export_karyawan()
    {
        $this->load->library('xlsx_writer');
        $rows = $this->hrd->karyawan_list([
            'q' => $this->input->get('q', TRUE), 'divisi' => $this->input->get('divisi', TRUE),
            'status' => $this->input->get('status', TRUE),
        ]);
        $header = ['Nama','Jabatan','Divisi','Tanggal Masuk','NIK','NPWP','Tempat Lahir','Tanggal Lahir',
            'Alamat KTP','Alamat Domisili','Jenis Kelamin','Agama','No. HP','Email','Pendidikan',
            'Status','Kontak Darurat','Hubungan','Telp Darurat','Jumlah Anak','Bank','Nama Rekening',
            'No. Rekening','Status Karyawan'];
        $baris = [];
        foreach ($rows as $r) {
            $baris[] = [$r['nama'],$r['jabatan'],$r['divisi'],$r['tanggal_masuk'],$r['nik'],$r['npwp'],
                $r['tempat_lahir'],$r['tanggal_lahir'],$r['alamat_ktp'],$r['alamat_domisili'],
                $r['jenis_kelamin'],$r['agama'],$r['no_hp'],$r['email'],$r['pendidikan_terakhir'],
                $r['status_pernikahan'],$r['nama_kontak_darurat'],$r['hubungan_kontak_darurat'],
                $r['telp_kontak_darurat'],$r['jumlah_anak'],$r['nama_bank'],$r['nama_rekening'],
                $r['no_rekening'],$r['status_karyawan']];
        }
        $this->hrd->log('export_karyawan', count($baris) . ' baris');
        $this->xlsx_writer->unduh('data-karyawan-' . date('Y-m-d') . '.xlsx', $header, $baris, 'Karyawan');
    }

    /* ============================= INVENTARIS ============================ */

    public function inventaris()
    {
        $filter = ['q' => $this->input->get('q', TRUE), 'kondisi' => $this->input->get('kondisi', TRUE)];
        $this->_view('hrd/inventaris_list', [
            'judul'      => 'Inventaris kantor',
            'menu_aktif' => 'inventaris',
            'rows'       => $this->hrd->inventaris_list($filter),
            'f'          => $filter,
        ]);
    }

    public function inventaris_form($id = null)
    {
        $this->_view('hrd/inventaris_form', [
            'judul'      => $id ? 'Ubah barang' : 'Tambah barang',
            'menu_aktif' => 'inventaris',
            'b'          => $id ? $this->hrd->inventaris($id) : [],
            'karyawan'   => $this->hrd->karyawan_list([]),
        ]);
    }

    public function inventaris_simpan()
    {
        $this->_wajib($this->input->post('id') ? 'edit' : 'create');
        $id = $this->input->post('id') ?: null;
        $p  = $this->input->post(NULL, TRUE);
        $data = [
            'karyawan_id'      => $p['karyawan_id'] ? (int)$p['karyawan_id'] : null,
            'nama_pemegang'    => trim($p['nama_pemegang']),
            'jabatan'          => trim($p['jabatan'] ?? ''),
            'jenis_barang'     => trim($p['jenis_barang']),
            'merek'            => trim($p['merek'] ?? ''),
            'serial_number'    => trim($p['serial_number'] ?? ''),
            'tipe_hp'          => trim($p['tipe_hp'] ?? ''),
            'kondisi'          => $p['kondisi'] ?? 'Baik',
            'no_hp_kantor'     => trim($p['no_hp_kantor'] ?? ''),
            'nama_wa_kantor'   => trim($p['nama_wa_kantor'] ?? ''),
            'masa_aktif_kartu' => $p['masa_aktif_kartu'] ?: null,
            'catatan'          => trim($p['catatan'] ?? ''),
        ];
        $foto = $this->_upload('foto_barang', 'barang', 'jpg|jpeg|png|webp');
        if ($foto) $data['foto_barang'] = $foto;

        $this->hrd->inventaris_simpan($data, $id);
        $this->session->set_flashdata('sukses', 'Data barang tersimpan.');
        redirect('hrd/inventaris');
    }

    public function inventaris_hapus($id)
    {
        $this->_wajib('delete');
        $this->hrd->inventaris_hapus($id);
        $this->session->set_flashdata('sukses', 'Barang dihapus.');
        redirect('hrd/inventaris');
    }

    public function export_inventaris()
    {
        $this->load->library('xlsx_writer');
        $rows = $this->hrd->inventaris_list(['q' => $this->input->get('q', TRUE), 'kondisi' => $this->input->get('kondisi', TRUE)]);
        $header = ['Nama Pemegang','Jabatan','Divisi','Jenis Barang','Merek','Serial Number','Tipe HP',
            'Kondisi','No. HP Kantor','Nama WA Kantor','Masa Aktif Kartu','Catatan'];
        $baris = [];
        foreach ($rows as $r) {
            $baris[] = [$r['nama_pemegang'],$r['jabatan'],$r['divisi'],$r['jenis_barang'],$r['merek'],
                $r['serial_number'],$r['tipe_hp'],$r['kondisi'],$r['no_hp_kantor'],$r['nama_wa_kantor'],
                $r['masa_aktif_kartu'],$r['catatan']];
        }
        $this->xlsx_writer->unduh('inventaris-kantor-' . date('Y-m-d') . '.xlsx', $header, $baris, 'Inventaris');
    }

    /* =============================== FORM =============================== */

    public function formulir()
    {
        $this->_view('hrd/formulir_list', [
            'judul'      => 'Formulir',
            'menu_aktif' => 'formulir',
            'rows'       => $this->hrd->form_list(),
        ]);
    }

    public function formulir_simpan()
    {
        $this->_wajib('edit');
        $id = $this->input->post('id') ?: null;
        $p  = $this->input->post(NULL, TRUE);
        $slug = trim($p['slug'] ?? '');
        if ($slug === '') $slug = url_title(strtolower($p['judul']), '-', TRUE);
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

        $bentrok = $this->hrd->form_slug($slug);
        if ($bentrok && (!$id || $bentrok['id'] != $id)) $slug .= '-' . rand(10, 99);

        $data = [
            'judul'         => trim($p['judul']),
            'slug'          => $slug,
            'deskripsi'     => trim($p['deskripsi'] ?? ''),
            'status'        => $p['status'] ?? 'draft',
            'satu_kali'     => isset($p['satu_kali']) ? 1 : 0,
            'pesan_selesai' => trim($p['pesan_selesai'] ?? '') ?: 'Terima kasih, jawaban kamu sudah tersimpan.',
        ];
        $baru = $this->hrd->form_simpan($data, $id);
        redirect('hrd/formulir_builder/' . $baru);
    }

    /** Buat survei contoh tentang perusahaan - semua pertanyaan pakai skala 1-10. */
    public function formulir_contoh()
    {
        $this->_wajib('create');

        $slug = 'survei-perusahaan-' . date('Y-m');
        if ($this->hrd->form_slug($slug)) $slug .= '-' . rand(10, 99);

        $id = $this->hrd->form_simpan([
            'judul'         => 'Survei Suasana Kerja ' . date('F Y'),
            'slug'          => $slug,
            'deskripsi'     => "Isi sejujurnya. Jawaban dipakai untuk memperbaiki cara kerja tim, bukan untuk menilai orang per orang.\n\nSkala 1 sampai 10: 1 berarti sangat tidak setuju, 10 berarti sangat setuju.",
            'status'        => 'draft',
            'satu_kali'     => 1,
            'pesan_selesai' => 'Terima kasih. Masukan kamu sudah tersimpan.',
        ]);

        $soal = [
            ['judul',    'Tentang pekerjaan kamu', '', ''],
            ['skala',    'Saya paham apa yang diharapkan dari pekerjaan saya', 'Tidak paham', 'Sangat paham'],
            ['skala',    'Beban kerja saya masuk akal', 'Terlalu berat', 'Pas'],
            ['skala',    'Saya punya alat dan akses yang saya butuhkan untuk bekerja', 'Kurang', 'Lengkap'],
            ['judul',    'Tentang atasan dan tim', '', ''],
            ['skala',    'Atasan saya memberi arahan yang jelas', 'Tidak jelas', 'Sangat jelas'],
            ['skala',    'Saya nyaman menyampaikan pendapat berbeda di tim', 'Tidak nyaman', 'Sangat nyaman'],
            ['skala',    'Kerja sama antar divisi berjalan lancar', 'Sulit', 'Lancar'],
            ['judul',    'Tentang perusahaan', '', ''],
            ['skala',    'Saya paham arah dan target perusahaan', 'Tidak paham', 'Sangat paham'],
            ['skala',    'Saya melihat peluang berkembang di sini', 'Tidak ada', 'Banyak'],
            ['skala',    'Saya akan merekomendasikan tempat ini ke teman', 'Tidak akan', 'Pasti'],
            ['textarea', 'Satu hal yang paling ingin kamu ubah di perusahaan', '', ''],
            ['textarea', 'Satu hal yang menurut kamu sudah bagus dan jangan diubah', '', ''],
        ];

        $urut = 1;
        foreach ($soal as $x) {
            $this->hrd->field_simpan([
                'form_id'      => $id,
                'label'        => $x[1],
                'tipe'         => $x[0],
                'opsi'         => '',
                'bantuan'      => '',
                'wajib'        => $x[0] === 'judul' ? 0 : ($x[0] === 'skala' ? 1 : 0),
                'urutan'       => $urut++,
                'skala_min'    => 1,
                'skala_maks'   => 10,
                'label_rendah' => $x[2],
                'label_tinggi' => $x[3],
            ]);
        }

        $this->session->set_flashdata('sukses', 'Survei contoh dibuat. Ubah pertanyaannya sesuka kamu, lalu ganti status jadi Terbuka untuk membagikannya.');
        redirect('hrd/formulir_builder/' . $id);
    }

    public function formulir_builder($id)
    {
        $f = $this->hrd->form($id);
        if (!$f) show_404();
        $this->_view('hrd/formulir_builder', [
            'judul'      => 'Susun: ' . $f['judul'],
            'menu_aktif' => 'formulir',
            'f'          => $f,
            'fields'     => $this->hrd->field_list($id),
        ]);
    }

    public function field_simpan()
    {
        $this->_wajib('edit');
        $p  = $this->input->post(NULL, TRUE);
        $id = $p['field_id'] ?: null;
        $min  = max(0, (int)($p['skala_min'] ?? 1));
        $maks = (int)($p['skala_maks'] ?? 10);
        if ($maks <= $min) $maks = $min + 9;
        if ($maks - $min > 10) $maks = $min + 10;

        $data = [
            'form_id'      => (int)$p['form_id'],
            'label'        => trim($p['label']),
            'tipe'         => $p['tipe'],
            'opsi'         => trim($p['opsi'] ?? ''),
            'bantuan'      => trim($p['bantuan'] ?? ''),
            'wajib'        => isset($p['wajib']) ? 1 : 0,
            'urutan'       => (int)($p['urutan'] ?? 0),
            'skala_min'    => $min,
            'skala_maks'   => $maks,
            'label_rendah' => trim($p['label_rendah'] ?? ''),
            'label_tinggi' => trim($p['label_tinggi'] ?? ''),
        ];
        if ($data['tipe'] === 'skala' && trim($data['opsi']) !== '') {
            $baris = array_filter(array_map('trim', explode("\n", $data['opsi'])));
            $perlu = $maks - $min + 1;
            if (count($baris) !== $perlu) {
                $this->session->set_flashdata('gagal',
                    'Label tiap angka harus ' . $perlu . ' baris untuk skala ' . $min . '-' . $maks
                    . ', sekarang ada ' . count($baris) . ' baris. Perbaiki dulu atau kosongkan.');
                redirect('hrd/formulir_builder/' . (int)$p['form_id']);
            }
        }

        $this->hrd->field_simpan($data, $id);
        redirect('hrd/formulir_builder/' . (int)$p['form_id']);
    }

    public function field_hapus($form_id, $field_id)
    {
        $this->hrd->field_hapus($field_id);
        redirect('hrd/formulir_builder/' . (int)$form_id);
    }

    public function formulir_hapus($id)
    {
        $this->_wajib('delete');
        $this->hrd->form_hapus($id);
        $this->session->set_flashdata('sukses', 'Formulir dihapus.');
        redirect('hrd/formulir');
    }

    public function jawaban($form_id)
    {
        $f = $this->hrd->form($form_id);
        if (!$f) show_404();
        $rows   = $this->hrd->jawaban_list($form_id);
        $fields = $this->hrd->field_list($form_id);
        $isi = [];
        foreach ($rows as $r) {
            $peta = [];
            foreach ($this->hrd->jawaban_isi($r['id']) as $x) $peta[$x['field_id']] = $x;
            $isi[$r['id']] = $peta;
        }
        // ringkasan nilai untuk pertanyaan berjenis skala
        $ringkas = [];
        foreach ($fields as $fd) {
            if ($fd['tipe'] !== 'skala') continue;
            $nilai = [];
            foreach ($rows as $r) {
                $v = $isi[$r['id']][$fd['id']]['nilai'] ?? null;
                if ($v !== null && $v !== '' && is_numeric($v)) $nilai[] = (float)$v;
            }
            $ringkas[$fd['id']] = [
                'label'    => $fd['label'],
                'min'      => (int)($fd['skala_min'] ?? 1),
                'maks'     => (int)($fd['skala_maks'] ?? 10),
                'jumlah'   => count($nilai),
                'rata'     => $nilai ? round(array_sum($nilai) / count($nilai), 2) : null,
                'terendah' => $nilai ? min($nilai) : null,
                'tertinggi'=> $nilai ? max($nilai) : null,
            ];
        }

        $this->_view('hrd/formulir_jawaban', [
            'judul'      => 'Jawaban: ' . $f['judul'],
            'menu_aktif' => 'formulir',
            'f'          => $f, 'rows' => $rows, 'fields' => $fields, 'isi' => $isi,
            'ringkas'    => $ringkas,
        ]);
    }

    public function jawaban_hapus($form_id, $id)
    {
        $this->_wajib('delete');
        $this->hrd->jawaban_hapus($id);
        redirect('hrd/jawaban/' . (int)$form_id);
    }

    public function export_jawaban($form_id)
    {
        $this->load->library('xlsx_writer');
        $f      = $this->hrd->form($form_id);
        $fields = $this->hrd->field_list($form_id);
        $rows   = $this->hrd->jawaban_list($form_id);

        $header = ['Waktu', 'Nama', 'Email'];
        $pakai  = [];
        foreach ($fields as $fd) {
            if ($fd['tipe'] === 'judul') continue;
            $header[] = $fd['label'];
            $pakai[]  = $fd['id'];
        }
        $baris = [];
        foreach ($rows as $r) {
            $peta = [];
            foreach ($this->hrd->jawaban_isi($r['id']) as $x) $peta[$x['field_id']] = $x['berkas'] ? '[berkas] ' . $x['berkas'] : $x['nilai'];
            $b = [$r['created_at'], $r['nama'], $r['email']];
            foreach ($pakai as $fid) $b[] = $peta[$fid] ?? '';
            $baris[] = $b;
        }
        $this->xlsx_writer->unduh(url_title($f['judul'], '-', TRUE) . '-' . date('Y-m-d') . '.xlsx', $header, $baris, 'Jawaban');
    }

    /* ============================== JADWAL ============================== */

    public function libur()
    {
        $tahun = (int)($this->input->get('tahun') ?: date('Y'));

        $this->_view('hrd/libur', [
            'judul'      => 'Hari Libur',
            'menu_aktif' => 'libur',
            'tahun'      => $tahun,
            'rows'       => $this->db->where('YEAR(tanggal)', $tahun)
                                     ->order_by('tanggal', 'ASC')
                                     ->get('hrd_hari_libur')->result_array(),
            'tahun_ada'  => $this->db->distinct()->select('YEAR(tanggal) AS th')
                                     ->order_by('th', 'DESC')
                                     ->get('hrd_hari_libur')->result_array(),
        ]);
    }

    public function libur_simpan()
    {
        $this->_wajib('edit');
        $p = $this->input->post(NULL, TRUE);

        $tanggal = trim($p['tanggal'] ?? '');
        $ket     = trim($p['keterangan'] ?? '');
        $jenis   = in_array($p['jenis'] ?? '', ['nasional','cuti_bersama','internal'], TRUE)
                 ? $p['jenis'] : 'internal';

        if ($tanggal === '' || $ket === '') {
            $this->session->set_flashdata('gagal', 'Tanggal dan keterangan wajib diisi.');
            redirect('hrd/libur');
        }

        // Tanggal yang sama diperbarui, bukan ditolak: lebih sering HRD
        // ingin membetulkan keterangannya daripada menambah baris kembar.
        $ada = $this->db->where('tanggal', $tanggal)->get('hrd_hari_libur')->row_array();
        if ($ada) {
            $this->db->where('id', $ada['id'])
                     ->update('hrd_hari_libur', ['keterangan' => $ket, 'jenis' => $jenis]);
            $this->session->set_flashdata('pesan', 'Hari libur diperbarui.');
        } else {
            $this->db->insert('hrd_hari_libur',
                ['tanggal' => $tanggal, 'keterangan' => $ket, 'jenis' => $jenis]);
            $this->session->set_flashdata('pesan', 'Hari libur ditambahkan.');
        }
        redirect('hrd/libur?tahun=' . date('Y', strtotime($tanggal)));
    }

    public function libur_hapus($id = 0)
    {
        $this->_wajib('edit');
        $row = $this->db->where('id', (int)$id)->get('hrd_hari_libur')->row_array();
        if ($row) {
            $this->db->where('id', (int)$id)->delete('hrd_hari_libur');
            $this->session->set_flashdata('pesan', 'Hari libur dihapus.');
        }
        redirect('hrd/libur');
    }

    public function jadwal()
    {
        // Jadwal yang sedang diedit (kalau ada ?edit=ID di URL).
        $edit_row = $this->input->get('edit')
            ? $this->db->where('id', (int)$this->input->get('edit'))->get('hrd_jadwal')->row_array()
            : NULL;

        // Jam masuk per hari milik jadwal yang diedit, supaya kotak
        // "Jam masuk per hari" di form terisi balik sesuai data yang
        // sudah tersimpan, bukan selalu kosong.
        $jh = (!empty($edit_row['jam_masuk_harian']))
            ? (json_decode($edit_row['jam_masuk_harian'], true) ?: [])
            : [];

        $this->_view('hrd/jadwal', [
            'judul'      => 'Jadwal Kerja',
            'menu_aktif' => 'jadwal',
            'rows'       => $this->db->order_by('divisi', 'DESC')
                                     ->order_by('id', 'ASC')
                                     ->get('hrd_jadwal')->result_array(),
            'edit_row'   => $edit_row,
            'jh'         => $jh,
            'karyawan'   => $this->db->select('id, full_name, role_text')
                                     ->order_by('full_name', 'ASC')
                                     ->get('user')->result_array(),
            'divisi'     => $this->db->distinct()->select('role_text')
                                     ->where('role_text IS NOT NULL')
                                     ->where('role_text !=', '')
                                     ->order_by('role_text', 'ASC')
                                     ->get('user')->result_array(),
        ]);
    }

    /**
     * Susun JSON jam masuk per hari dari input form. Hari yang
     * dikosongkan tidak ikut disimpan, artinya hari itu memakai jam
     * masuk default (kolom jam_masuk).
     */
    private function _susun_jam_harian()
    {
        $harian = [];
        foreach (range(1, 7) as $h) {
            $v = trim($this->input->post('jam_harian_' . $h) ?? '');
            if ($v !== '') $harian[$h] = $v . ':00';
        }
        return $harian ? json_encode($harian) : NULL;
    }

    public function jadwal_simpan()
    {
        $this->_wajib('edit');
        $p = $this->input->post(NULL, TRUE);

        $untuk = $p['untuk'] ?? 'user';   // 'user' atau 'divisi'
        $data = [
            'nama_jadwal'     => trim($p['nama_jadwal'] ?? '') ?: 'Reguler',
            'jam_masuk'       => $p['jam_masuk'] ?: '08:00:00',
            'jam_masuk_harian'=> $this->_susun_jam_harian(),
            'jam_pulang'      => $p['jam_pulang'] ?: '17:00:00',
            'jam_pulang_sabtu'=> $p['jam_pulang_sabtu'] ?: '12:00:00',
            'toleransi_menit' => (int)($p['toleransi_menit'] ?? 5),
            'pakai_istirahat' => empty($p['pakai_istirahat']) ? 0 : 1,
            'hari_kerja'      => implode(',', $p['hari_kerja'] ?? ['1','2','3','4','5']),
            'hari_wfh'        => !empty($p['hari_wfh']) ? implode(',', $p['hari_wfh']) : NULL,
            'aktif'           => 1,
        ];

        if ($untuk === 'divisi') {
            $data['divisi']  = trim($p['divisi'] ?? '');
            $data['user_id'] = NULL;
            if ($data['divisi'] === '') {
                $this->session->set_flashdata('gagal', 'Divisi belum dipilih.');
                redirect('hrd/jadwal');
            }
            $ada = $this->db->where('divisi', $data['divisi'])->get('hrd_jadwal')->row_array();
        } else {
            $data['user_id'] = (int)($p['user_id'] ?? 0);
            $data['divisi']  = NULL;
            if (!$data['user_id']) {
                $this->session->set_flashdata('gagal', 'Karyawan belum dipilih.');
                redirect('hrd/jadwal');
            }
            $ada = $this->db->where('user_id', $data['user_id'])->get('hrd_jadwal')->row_array();
        }

        if ($ada) {
            $this->db->where('id', $ada['id'])->update('hrd_jadwal', $data);
            $this->session->set_flashdata('pesan', 'Jadwal diperbarui.');
        } else {
            $this->db->insert('hrd_jadwal', $data);
            $this->session->set_flashdata('pesan', 'Jadwal ditambahkan.');
        }

        redirect('hrd/jadwal');
    }

    public function jadwal_hapus($id)
    {
        $this->_wajib('delete');
        $this->db->where('id', (int)$id)->delete('hrd_jadwal');
        $this->session->set_flashdata('pesan',
            'Jadwal dihapus. Yang bersangkutan kembali memakai jadwal reguler 08:00-17:00.');
        redirect('hrd/jadwal');
    }

    /* ============================= ASESMEN ============================= */

    public function asesmen()
    {
        $q = $this->input->get('q', TRUE);

        $this->db->select('a.*, s.singkatan AS stifin_singkat, s.nama AS stifin_nama,
                           d.tipe_utama AS disc_tipe,
                           b.dominan AS bf_dominan,
                           k.persen AS kar_persen, k.kategori AS kar_kategori,
                           p.benar AS apt_benar, p.total_soal AS apt_total, p.iq AS apt_iq')
                 ->from('hrd_asesmen a')
                 ->join('hrd_stifin s', 's.kode = a.stifin_kode', 'left')
                 ->join('hrd_disc d', 'd.asesmen_id = a.id', 'left')
                 ->join('hrd_bigfive b', 'b.asesmen_id = a.id', 'left')
                 ->join('hrd_karakteristik k', 'k.asesmen_id = a.id', 'left')
                 ->join('hrd_aptitude p', 'p.asesmen_id = a.id', 'left');

        if ($q !== '' && $q !== NULL) {
            $this->db->group_start()
                     ->like('a.nama', $q)->or_like('a.email', $q)->or_like('a.divisi', $q)
                     ->group_end();
        }

        $rows = $this->db->order_by('a.id', 'DESC')->get()->result_array();

        $this->_view('hrd/asesmen_list', [
            'judul'      => 'Hasil Asesmen',
            'menu_aktif' => 'asesmen',
            'rows'       => $rows,
            'q'          => $q,
        ]);
    }

    public function asesmen_detail($id)
    {
        $a = $this->db->get_where('hrd_asesmen', ['id' => (int)$id])->row_array();
        if (!$a) show_404();

        $ambil = function ($tabel) use ($a) {
            return $this->db->where('asesmen_id', $a['id'])
                            ->order_by('id', 'DESC')->limit(1)
                            ->get($tabel)->row_array();
        };

        $this->_view('hrd/asesmen_detail', [
            'judul'         => 'Asesmen: ' . $a['nama'],
            'menu_aktif'    => 'asesmen',
            'a'             => $a,
            'disc'          => $ambil('hrd_disc'),
            'bigfive'       => $ambil('hrd_bigfive'),
            'karakteristik' => $ambil('hrd_karakteristik'),
            'aptitude'      => $ambil('hrd_aptitude'),
            'stifin'        => $a['stifin_kode']
                ? $this->db->get_where('hrd_stifin', ['kode' => $a['stifin_kode']])->row_array()
                : NULL,
        ]);
    }

    /**
     * Rincian jawaban peserta, hanya untuk HRD ke atas.
     *
     * Peserta tidak pernah melihat halaman ini: menampilkan kunci kepada
     * orang yang mengerjakan sama saja membocorkan seluruh tes ke peserta
     * berikutnya. Yang perlu HRD adalah melihat bagaimana seseorang
     * berpikir, bukan hanya skor akhirnya.
     */
    public function asesmen_jawaban($id)
    {
        $a = $this->db->get_where('hrd_asesmen', ['id' => (int)$id])->row_array();
        if (!$a) show_404();

        $ambil = function ($tabel) use ($a) {
            return $this->db->where('asesmen_id', $a['id'])
                            ->order_by('id', 'DESC')->limit(1)
                            ->get($tabel)->row_array();
        };

        // --- Test IQ: ada benar dan salah ---
        $iq = $ambil('hrd_aptitude');
        $iq_baris = [];
        if ($iq && !empty($iq['jawaban_json'])) {
            $jwb = json_decode($iq['jawaban_json'], TRUE) ?: [];
            $soal = $this->db->where('aktif', 1)->order_by('nomor', 'ASC')
                             ->get('hrd_aptitude_soal')->result_array();
            foreach ($soal as $so) {
                $n = (int)$so['nomor'];
                $pilih = $jwb[$n] ?? '';
                $iq_baris[] = [
                    'nomor'      => $n,
                    'kategori'   => $so['kategori'],
                    'soal'       => $so['soal'],
                    'pilih'      => $pilih,
                    'pilih_teks' => $pilih ? ($so['opsi_' . strtolower($pilih)] ?? '') : '',
                    'kunci'      => $so['kunci'],
                    'kunci_teks' => $so['opsi_' . strtolower($so['kunci'])] ?? '',
                    'benar'      => $pilih === $so['kunci'],
                    'pembahasan' => $so['pembahasan'],
                ];
            }
        }

        // --- Karakteristik: ada bobot, jadi ada yang "paling baik" ---
        $kar = $ambil('hrd_karakteristik');
        $kar_baris = [];
        if ($kar && !empty($kar['jawaban_json'])) {
            $jwb = json_decode($kar['jawaban_json'], TRUE) ?: [];
            $soal = $this->db->where('aktif', 1)->order_by('nomor', 'ASC')
                             ->get('hrd_karakteristik_soal')->result_array();
            foreach ($soal as $so) {
                $n = (int)$so['nomor'];
                $opsi = json_decode($so['opsi_json'], TRUE) ?: [];
                $i = isset($jwb[$n]) ? (int)$jwb[$n] : -1;

                $terbaik = NULL;
                $bobot_max = 0;
                foreach ($opsi as $o) {
                    if ((int)($o['bobot'] ?? 0) > $bobot_max) {
                        $bobot_max = (int)$o['bobot'];
                        $terbaik = $o;
                    }
                }

                $kar_baris[] = [
                    'nomor'        => $n,
                    'situasi'      => $so['situasi'],
                    'pilih_teks'   => $opsi[$i]['teks'] ?? '(tidak dijawab)',
                    'pilih_bobot'  => (int)($opsi[$i]['bobot'] ?? 0),
                    'aspek'        => $opsi[$i]['aspek'] ?? '',
                    'terbaik_teks' => $terbaik['teks'] ?? '',
                    'terbaik_bobot'=> $bobot_max,
                ];
            }
        }

        // --- DISC: tidak ada benar-salah, hanya dimensi ---
        $disc = $ambil('hrd_disc');
        $disc_baris = [];
        if ($disc && !empty($disc['jawaban_json'])) {
            $isi = json_decode($disc['jawaban_json'], TRUE) ?: [];
            $jwb = $isi['jawaban'] ?? [];
            $soal = $this->db->order_by('grup', 'ASC')->order_by('posisi', 'ASC')
                             ->get('hrd_disc_soal')->result_array();
            $kel = [];
            foreach ($soal as $so) {
                $g = (int)$so['grup'];
                if (!isset($kel[$g])) {
                    $kel[$g] = ['nomor' => $g, 'situasi' => $so['pertanyaan'], 'opsi' => []];
                }
                $kel[$g]['opsi'][$so['dimensi']] = $so['kata'];
            }
            foreach ($kel as $g => $k) {
                $d = $jwb[$g] ?? '';
                $disc_baris[] = [
                    'nomor'      => $g,
                    'situasi'    => $k['situasi'],
                    'dimensi'    => $d,
                    'pilih_teks' => $d ? ($k['opsi'][$d] ?? '') : '(tidak dijawab)',
                ];
            }
        }

        $this->_view('hrd/asesmen_jawaban', [
            'judul'      => 'Jawaban: ' . $a['nama'],
            'menu_aktif' => 'asesmen',
            'a'          => $a,
            'iq'         => $iq,
            'iq_baris'   => $iq_baris,
            'kar_baris'  => $kar_baris,
            'disc_baris' => $disc_baris,
        ]);
    }

    public function asesmen_hapus($id)
    {
        $this->_wajib('delete');
        $id = (int)$id;
        foreach (['hrd_disc','hrd_bigfive','hrd_karakteristik','hrd_aptitude'] as $t) {
            $this->db->where('asesmen_id', $id)->delete($t);
        }
        $this->db->where('id', $id)->delete('hrd_asesmen');
        redirect('hrd/asesmen');
    }

    /* =============================== DISC =============================== */

    public function disc()
    {
        $filter = ['q' => $this->input->get('q', TRUE), 'tipe' => $this->input->get('tipe', TRUE)];
        $this->_view('hrd/disc_list', [
            'judul'      => 'Hasil DISC',
            'menu_aktif' => 'disc',
            'rows'       => $this->hrd->disc_list($filter),
            'profil'     => $this->config->item('disc_profil', 'disc_soal'),
            'belum'      => $this->hrd->karyawan_belum_disc(),
            'f'          => $filter,
        ]);
    }

    public function disc_detail($id)
    {
        $d = $this->hrd->disc($id);
        if (!$d) show_404();
        $this->load->library('disc_ai');
        $this->_view('hrd/disc_detail', [
            'judul'      => 'DISC: ' . $d['nama'],
            'menu_aktif' => 'disc',
            'd'          => $d,
            'ai'         => $this->disc_ai->narasi($d),
            'profil'     => $this->config->item('disc_profil', 'disc_soal'),
        ]);
    }

    public function disc_ai_ulang($id)
    {
        $d = $this->hrd->disc($id);
        if (!$d) show_404();
        $this->load->library('disc_ai');
        $this->disc_ai->narasi($d, TRUE);
        redirect('hrd/disc_detail/' . (int)$id);
    }

    public function disc_hapus($id)
    {
        $this->_wajib('delete');
        $this->hrd->disc_hapus($id);
        redirect('hrd/disc');
    }

    /* ===================== DISC: PENYUSUN SOAL (HRD) ====================== */

    public function disc_soal()
    {
        $this->_view('hrd/disc_soal', [
            'judul'      => 'Susun DISC test',
            'menu_aktif' => 'disc_soal',
            'grup'       => $this->hrd->disc_soal(),
            'profil'     => $this->config->item('disc_profil', 'disc_soal'),
            'contoh'     => $this->config->item('disc_soal', 'disc_soal'),
            'pengaturan' => [
                'judul'     => $this->hrd->pengaturan('disc_judul', 'DISC Test'),
                'deskripsi' => $this->hrd->pengaturan('disc_deskripsi', ''),
                'status'    => $this->hrd->pengaturan('disc_status', 'draft'),
                'satu_kali'   => $this->hrd->pengaturan('disc_satu_kali', '1'),
                'skala_maks'  => (int)$this->hrd->pengaturan('disc_skala_maks', 10),
            ],
            'jml_jawaban' => count($this->hrd->disc_list([])),
        ]);
    }

    public function disc_grup_simpan()
    {
        $this->_wajib('edit');
        $p    = $this->input->post(NULL, TRUE);
        $grup = $p['grup'] ?: $this->hrd->disc_grup_berikutnya();

        $kata = [];
        for ($i = 0; $i < 4; $i++) {
            $kata[] = [
                'kata'    => $p['kata'][$i] ?? '',
                'dimensi' => $p['dimensi'][$i] ?? 'D',
            ];
        }

        $terisi = array_filter(array_column($kata, 'kata'), function ($k) { return trim($k) !== ''; });
        if (count($terisi) < 4) {
            $this->session->set_flashdata('gagal', 'Satu kelompok harus berisi 4 kata. Lengkapi dulu sebelum disimpan.');
            redirect('hrd/disc_soal');
        }

        $dim = array_column($kata, 'dimensi');
        if (count(array_unique($dim)) < 4) {
            $this->session->set_flashdata('gagal', 'Tiap kelompok harus punya satu kata D, satu I, satu S, dan satu C. Sekarang ada dimensi yang dobel.');
            redirect('hrd/disc_soal');
        }

        $this->hrd->disc_grup_simpan($grup, $kata, $p['pertanyaan'] ?? '');
        $this->session->set_flashdata('sukses', 'Pertanyaan ' . $grup . ' tersimpan.');
        redirect('hrd/disc_soal');
    }

    public function disc_grup_hapus($grup)
    {
        $this->_wajib('edit');
        $this->hrd->disc_grup_hapus($grup);
        $this->session->set_flashdata('sukses', 'Pertanyaan dihapus, penomoran dirapikan.');
        redirect('hrd/disc_soal');
    }

    /** Isi cepat dengan 24 kelompok contoh - HRD tetap bisa ubah/hapus semuanya. */
    public function disc_contoh()
    {
        $this->_wajib('edit');
        if ($this->hrd->disc_jumlah_grup() > 0) {
            $this->session->set_flashdata('gagal', 'Soal sudah ada isinya. Kosongkan dulu kalau mau diganti dengan contoh.');
            redirect('hrd/disc_soal');
        }
        $contoh = $this->config->item('disc_soal', 'disc_soal');
        $tanya  = $this->config->item('disc_pertanyaan', 'disc_soal');
        foreach ($contoh as $i => $g) {
            $kata = [];
            foreach ($g as $k) $kata[] = ['dimensi' => $k[0], 'kata' => $k[1]];
            $this->hrd->disc_grup_simpan($i + 1, $kata, $tanya[$i] ?? '');
        }
        $this->session->set_flashdata('sukses', count($contoh) . ' kelompok contoh dimasukkan. Silakan ubah kata-katanya sesuai bahasa tim kamu.');
        redirect('hrd/disc_soal');
    }

    public function disc_kosongkan()
    {
        $this->_wajib('delete');
        $this->hrd->disc_soal_kosongkan();
        $this->session->set_flashdata('sukses', 'Semua soal dikosongkan. Hasil tes yang sudah masuk tidak ikut terhapus.');
        redirect('hrd/disc_soal');
    }

    public function disc_pengaturan_simpan()
    {
        $this->_wajib('edit');
        $p = $this->input->post(NULL, TRUE);
        $this->hrd->pengaturan_simpan('disc_judul', trim($p['judul']) ?: 'DISC Test');
        $this->hrd->pengaturan_simpan('disc_deskripsi', trim($p['deskripsi'] ?? ''));
        $this->hrd->pengaturan_simpan('disc_status', $p['status'] ?? 'draft');
        $this->hrd->pengaturan_simpan('disc_satu_kali', isset($p['satu_kali']) ? '1' : '0');
        $sm = (int)($p['skala_maks'] ?? 10);
        if ($sm < 3)  $sm = 3;
        if ($sm > 10) $sm = 10;
        $this->hrd->pengaturan_simpan('disc_skala_maks', (string)$sm);
        $this->session->set_flashdata('sukses', 'Pengaturan tes tersimpan.');
        redirect('hrd/disc_soal');
    }

    public function export_disc()
    {
        $this->load->library('xlsx_writer');
        $rows = $this->hrd->disc_list([]);
        $header = ['Waktu','Nama','Email','Jabatan','Tipe Utama','Tipe Kedua',
            'D poin','I poin','S poin','C poin','D %','I %','S %','C %'];
        $baris = [];
        foreach ($rows as $r) {
            $baris[] = [$r['created_at'],$r['nama'],$r['email'],$r['jabatan'],$r['tipe_utama'],$r['tipe_kedua'],
                $r['most_d'],$r['most_i'],$r['most_s'],$r['most_c'],
                $r['net_d'],$r['net_i'],$r['net_s'],$r['net_c']];
        }
        $this->xlsx_writer->unduh('hasil-disc-' . date('Y-m-d') . '.xlsx', $header, $baris, 'DISC');
    }

    /* ===== Susun Test IQ ===== */

    public function aptitude_soal()
    {
        $soal = $this->db->order_by('nomor', 'ASC')
                         ->get('hrd_aptitude_soal')->result_array();

        $this->_view('hrd/aptitude_soal', [
            'judul'      => 'Susun Test IQ',
            'menu_aktif' => 'aptitude_soal',
            'soal'       => $soal,
            'ubah'       => $this->input->get('ubah'),
        ]);
    }

    public function aptitude_simpan()
    {
        $this->_wajib('edit');
        $p = $this->input->post(NULL, TRUE);

        $isi = [
            'kategori'   => in_array($p['kategori'] ?? '', ['Numerik','Verbal','Logika','Spasial'], TRUE)
                          ? $p['kategori'] : 'Logika',
            'soal'       => trim($p['soal'] ?? ''),
            'opsi_a'     => trim($p['opsi_a'] ?? ''),
            'opsi_b'     => trim($p['opsi_b'] ?? ''),
            'opsi_c'     => trim($p['opsi_c'] ?? ''),
            'opsi_d'     => trim($p['opsi_d'] ?? ''),
            'kunci'      => in_array($p['kunci'] ?? '', ['A','B','C','D'], TRUE) ? $p['kunci'] : 'A',
            'pembahasan' => trim($p['pembahasan'] ?? ''),
            'aktif'      => empty($p['aktif']) ? 0 : 1,
        ];

        foreach (['soal','opsi_a','opsi_b','opsi_c','opsi_d'] as $w) {
            if ($isi[$w] === '') {
                $this->session->set_flashdata('gagal', 'Soal dan keempat pilihan wajib diisi.');
                redirect('hrd/aptitude_soal');
            }
        }

        $id = (int)($p['id'] ?? 0);
        if ($id) {
            $this->db->where('id', $id)->update('hrd_aptitude_soal', $isi);
            $this->session->set_flashdata('sukses', 'Soal diperbarui.');
        } else {
            // Nomor diurutkan sendiri supaya tidak bentrok dengan kunci unik.
            $terakhir = (int)$this->db->select_max('nomor')
                                      ->get('hrd_aptitude_soal')->row('nomor');
            $isi['nomor'] = $terakhir + 1;
            $this->db->insert('hrd_aptitude_soal', $isi);
            $this->session->set_flashdata('sukses', 'Soal nomor ' . $isi['nomor'] . ' ditambahkan.');
        }

        redirect('hrd/aptitude_soal');
    }

    public function aptitude_hapus($id)
    {
        $this->_wajib('edit');
        $this->db->where('id', (int)$id)->delete('hrd_aptitude_soal');

        // Penomoran dirapikan supaya peserta melihat urutan yang wajar.
        $urut = $this->db->order_by('nomor', 'ASC')
                         ->get('hrd_aptitude_soal')->result_array();
        $n = 1;
        foreach ($urut as $s) {
            $this->db->where('id', $s['id'])->update('hrd_aptitude_soal', ['nomor' => $n]);
            $n++;
        }

        $this->session->set_flashdata('sukses', 'Soal dihapus, penomoran dirapikan.');
        redirect('hrd/aptitude_soal');
    }

    /* ===== Susun Test Karakteristik ===== */

    public function karakteristik_soal()
    {
        $soal = $this->db->order_by('nomor', 'ASC')
                         ->get('hrd_karakteristik_soal')->result_array();

        // Opsi disimpan sebagai JSON; dibongkar di sini supaya view-nya
        // tidak perlu tahu bentuk penyimpanannya.
        foreach ($soal as $i => $s) {
            $soal[$i]['opsi'] = json_decode($s['opsi_json'], TRUE) ?: [];
        }

        $this->_view('hrd/karakteristik_soal', [
            'judul'      => 'Susun Karakteristik',
            'menu_aktif' => 'karakteristik_soal',
            'soal'       => $soal,
            'ubah'       => $this->input->get('ubah'),
            'aspek'      => ['kerjasama', 'integritas', 'ketahanan', 'tanggungjwb'],
        ]);
    }

    public function karakteristik_simpan()
    {
        $this->_wajib('edit');
        $p = $this->input->post(NULL, TRUE);

        $situasi = trim($p['situasi'] ?? '');
        if ($situasi === '') {
            $this->session->set_flashdata('gagal', 'Situasi wajib diisi.');
            redirect('hrd/karakteristik_soal');
        }

        $aspek_sah = ['kerjasama', 'integritas', 'ketahanan', 'tanggungjwb'];
        $aspek = in_array($p['aspek'] ?? '', $aspek_sah, TRUE) ? $p['aspek'] : 'kerjasama';

        // Lima pilihan dengan bobot 1-5. Bobot menentukan skor, jadi tiap
        // angka harus dipakai sekali -- kalau ada yang dobel, satu sisi
        // penilaian jadi tidak terwakili sama sekali.
        $opsi   = [];
        $terpakai = [];
        for ($i = 0; $i < 5; $i++) {
            $teks  = trim($p['teks'][$i] ?? '');
            $bobot = (int)($p['bobot'][$i] ?? 0);
            if ($teks === '') {
                $this->session->set_flashdata('gagal', 'Kelima pilihan harus diisi.');
                redirect('hrd/karakteristik_soal');
            }
            if ($bobot < 1 || $bobot > 5) {
                $this->session->set_flashdata('gagal', 'Bobot harus antara 1 sampai 5.');
                redirect('hrd/karakteristik_soal');
            }
            $terpakai[] = $bobot;
            $opsi[] = ['teks' => $teks, 'bobot' => $bobot, 'aspek' => $aspek];
        }

        if (count(array_unique($terpakai)) < 5) {
            $this->session->set_flashdata('gagal',
                'Tiap bobot 1 sampai 5 harus dipakai sekali. Sekarang ada yang dobel.');
            redirect('hrd/karakteristik_soal');
        }

        $isi = [
            'situasi'   => $situasi,
            'opsi_json' => json_encode($opsi, JSON_UNESCAPED_UNICODE),
            'aktif'     => empty($p['aktif']) ? 0 : 1,
        ];

        $id = (int)($p['id'] ?? 0);
        if ($id) {
            $this->db->where('id', $id)->update('hrd_karakteristik_soal', $isi);
            $this->session->set_flashdata('sukses', 'Soal diperbarui.');
        } else {
            $terakhir = (int)$this->db->select_max('nomor')
                                      ->get('hrd_karakteristik_soal')->row('nomor');
            $isi['nomor'] = $terakhir + 1;
            $this->db->insert('hrd_karakteristik_soal', $isi);
            $this->session->set_flashdata('sukses', 'Soal nomor ' . $isi['nomor'] . ' ditambahkan.');
        }

        redirect('hrd/karakteristik_soal');
    }

    public function karakteristik_hapus($id)
    {
        $this->_wajib('edit');
        $this->db->where('id', (int)$id)->delete('hrd_karakteristik_soal');

        $urut = $this->db->order_by('nomor', 'ASC')
                         ->get('hrd_karakteristik_soal')->result_array();
        $n = 1;
        foreach ($urut as $s) {
            $this->db->where('id', $s['id'])->update('hrd_karakteristik_soal', ['nomor' => $n]);
            $n++;
        }

        $this->session->set_flashdata('sukses', 'Soal dihapus, penomoran dirapikan.');
        redirect('hrd/karakteristik_soal');
    }
}
