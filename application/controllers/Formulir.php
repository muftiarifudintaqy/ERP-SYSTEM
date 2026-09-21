<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halaman pengisian untuk karyawan (tanpa login).
 * /f/data-karyawan   -> langsung masuk tabel hrd_karyawan
 * /f/inventaris      -> langsung masuk tabel hrd_inventaris
 * /f/disc            -> tes DISC + skoring otomatis
 * /f/{slug}          -> formulir bikinan HRD lewat form builder
 */
class Formulir extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hrd_model', 'hrd');
        $this->load->helper(['url', 'form']);
        $this->load->library(['session', 'form_validation']);
        $this->load->config('disc_soal', TRUE);
    }

    private function _view($berkas, $data = [])
    {
        $data['judul'] = $data['judul'] ?? 'Formulir Montera';
        $this->load->view('publik/_atas', $data);
        $this->load->view($berkas, $data);
        $this->load->view('publik/_bawah', $data);
    }

    private function _upload($field, $folder, $izin = 'jpg|jpeg|png|pdf|webp|heic')
    {
        if (empty($_FILES[$field]['name'])) return null;
        $dir = FCPATH . 'uploads/hrd/' . $folder . '/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $this->load->library('upload', [], 'up');
        $this->up->initialize([
            'upload_path' => $dir, 'allowed_types' => $izin,
            'max_size' => 10240, 'encrypt_name' => TRUE,
        ]);
        if (!$this->up->do_upload($field)) return null;
        $d = $this->up->data();
        return $d['file_name'];
    }

    public function selesai()
    {
        $this->_view('publik/selesai', [
            'judul' => 'Terima kasih',
            'pesan' => $this->session->flashdata('pesan') ?: 'Data kamu sudah tersimpan.',
        ]);
    }

    /* ======================== FORM DATA KARYAWAN ======================== */

    public function karyawan()
    {
        if ($this->input->method() !== 'post') {
            return $this->_view('publik/isi_karyawan', ['judul' => 'Informasi Data Karyawan']);
        }

        $p = $this->input->post(NULL, TRUE);
        $wajib = ['nama','jabatan','tanggal_masuk','nik','tempat_lahir','tanggal_lahir','alamat_ktp',
            'alamat_domisili','jenis_kelamin','agama','no_hp','email','pendidikan_terakhir',
            'status_pernikahan','nama_kontak_darurat','hubungan_kontak_darurat','telp_kontak_darurat',
            'nama_bank','nama_rekening','no_rekening'];
        $kurang = [];
        foreach ($wajib as $w) if (trim($p[$w] ?? '') === '') $kurang[] = $w;
        if ($kurang) {
            $this->session->set_flashdata('gagal', 'Masih ada isian wajib yang kosong: ' . implode(', ', $kurang));
            return $this->_view('publik/isi_karyawan', ['judul' => 'Informasi Data Karyawan', 'lama' => $p]);
        }

        $kolom = ['nama','jabatan','divisi','tanggal_masuk','nik','npwp','tempat_lahir','tanggal_lahir',
            'alamat_ktp','alamat_domisili','jenis_kelamin','agama','no_hp','email','pendidikan_terakhir',
            'status_pernikahan','nama_kontak_darurat','hubungan_kontak_darurat','telp_kontak_darurat',
            'jumlah_anak','nama_bank','nama_rekening','no_rekening'];
        $data = [];
        foreach ($kolom as $c) $data[$c] = trim($p[$c] ?? '');
        $data['jumlah_anak'] = (int)$data['jumlah_anak'];
        $data['sumber'] = 'form';

        $ktp = $this->_upload('foto_ktp', 'ktp');
        if ($ktp) $data['foto_ktp'] = $ktp;

        $id = $this->hrd->karyawan_simpan($data);
        $this->session->set_flashdata('pesan', 'Data kamu sudah masuk ke database HRD Montera. Kalau ada yang perlu diubah, hubungi HRD.');
        redirect('f/selesai');
    }

    /* ========================= FORM INVENTARIS ========================== */

    public function inventaris()
    {
        if ($this->input->method() !== 'post') {
            return $this->_view('publik/isi_inventaris', ['judul' => 'Pendataan Inventaris Kantor']);
        }

        $p = $this->input->post(NULL, TRUE);
        foreach (['nama_pemegang','jabatan','jenis_barang','merek','kondisi'] as $w) {
            if (trim($p[$w] ?? '') === '') {
                $this->session->set_flashdata('gagal', 'Isian wajib belum lengkap.');
                return $this->_view('publik/isi_inventaris', ['judul' => 'Pendataan Inventaris Kantor', 'lama' => $p]);
            }
        }

        $karyawan = $this->hrd->karyawan_by('nama', trim($p['nama_pemegang']));
        $data = [
            'karyawan_id'      => $karyawan['id'] ?? null,
            'nama_pemegang'    => trim($p['nama_pemegang']),
            'jabatan'          => trim($p['jabatan']),
            'jenis_barang'     => trim($p['jenis_barang']),
            'merek'            => trim($p['merek']),
            'serial_number'    => trim($p['serial_number'] ?? ''),
            'tipe_hp'          => trim($p['tipe_hp'] ?? ''),
            'kondisi'          => $p['kondisi'],
            'no_hp_kantor'     => trim($p['no_hp_kantor'] ?? ''),
            'nama_wa_kantor'   => trim($p['nama_wa_kantor'] ?? ''),
            'masa_aktif_kartu' => $p['masa_aktif_kartu'] ?: null,
            'catatan'          => trim($p['catatan'] ?? ''),
        ];
        $foto = $this->_upload('foto_barang', 'barang', 'jpg|jpeg|png|webp');
        if ($foto) $data['foto_barang'] = $foto;

        $this->hrd->inventaris_simpan($data);
        $this->session->set_flashdata('pesan', 'Data barang sudah tercatat. Kalau kamu pegang lebih dari satu barang, isi lagi form ini untuk barang berikutnya.');
        redirect('f/selesai');
    }

    /* ============================== DISC =============================== */

    public function disc()
    {
        $soal   = $this->hrd->disc_soal();
        $status = $this->hrd->pengaturan('disc_status', 'draft');
        $judul  = $this->hrd->pengaturan('disc_judul', 'DISC Test');
        $maks   = (int)$this->hrd->pengaturan('disc_skala_maks', 10);
        if ($maks < 3 || $maks > 10) $maks = 10;

        $bawaan = [
            'judul'      => $judul,
            'deskripsi'  => $this->hrd->pengaturan('disc_deskripsi', ''),
            'soal'       => $soal,
            'skala_maks' => $maks,
        ];

        if (!$soal || $status !== 'terbuka') {
            return $this->_view('publik/tertutup', [
                'judul' => $judul,
                'f'     => ['judul' => $judul, 'status' => $soal ? $status : 'draft'],
            ]);
        }

        if ($this->input->method() !== 'post') {
            return $this->_view('publik/isi_disc', $bawaan);
        }

        $p     = $this->input->post(NULL, TRUE);
        $nama  = trim($p['nama'] ?? '');
        $email = trim($p['email'] ?? '');
        if ($nama === '' || $email === '') {
            $this->session->set_flashdata('gagal', 'Nama dan email wajib diisi.');
            return $this->_view('publik/isi_disc', $bawaan + ['lama' => $p]);
        }

        if ($this->hrd->pengaturan('disc_satu_kali', '1') === '1' && $this->hrd->disc_sudah_isi($email)) {
            $this->session->set_flashdata('pesan', 'Email ini sudah pernah mengikuti tes. Kalau perlu mengulang, hubungi HRD.');
            redirect('f/selesai');
        }

        // Tiap pernyataan dinilai sendiri. Nilai dijumlahkan per dimensi.
        $total = ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];
        $rekam = [];
        $urut  = 0;

        foreach ($soal as $nomor => $grup) {
            $urut++;
            foreach ($grup as $i => $kata) {
                $v = $p['n_' . $nomor . '_' . $i] ?? null;
                if ($v === null || $v === '') {
                    $this->session->set_flashdata('gagal',
                        'Pertanyaan nomor ' . $urut . ' belum lengkap. Semua pernyataan di dalamnya harus diberi nilai.');
                    return $this->_view('publik/isi_disc', $bawaan + ['lama' => $p]);
                }
                $v = (int)$v;
                if ($v < 1)     $v = 1;
                if ($v > $maks) $v = $maks;

                $dim = $kata['dimensi'];
                if (isset($total[$dim])) $total[$dim] += $v;
                $rekam[] = ['grup' => $nomor, 'kata' => $kata['kata'], 'dimensi' => $dim, 'nilai' => $v];
            }
        }

        // Persentase terhadap nilai maksimum tiap dimensi
        $per_dimensi = [];
        foreach ($soal as $grup) {
            foreach ($grup as $k) {
                $per_dimensi[$k['dimensi']] = ($per_dimensi[$k['dimensi']] ?? 0) + 1;
            }
        }
        $persen = [];
        foreach (['D','I','S','C'] as $h) {
            $maksimum  = ($per_dimensi[$h] ?? 0) * $maks;
            $persen[$h] = $maksimum > 0 ? (int)round($total[$h] / $maksimum * 100) : 0;
        }

        arsort($persen);
        $urutan = array_keys($persen);

        $karyawan = $this->hrd->karyawan_by('email', $email);
        $id = $this->hrd->disc_simpan([
            'karyawan_id' => $karyawan['id'] ?? null,
            'nama'        => $nama,
            'email'       => $email,
            'jabatan'     => trim($p['jabatan'] ?? ($karyawan['jabatan'] ?? '')),
            'most_d' => $total['D'], 'most_i' => $total['I'],
            'most_s' => $total['S'], 'most_c' => $total['C'],
            'least_d' => 0, 'least_i' => 0, 'least_s' => 0, 'least_c' => 0,
            'net_d' => $persen['D'], 'net_i' => $persen['I'],
            'net_s' => $persen['S'], 'net_c' => $persen['C'],
            'tipe_utama' => $urutan[0],
            'tipe_kedua' => $urutan[1],
            'jawaban_json' => json_encode([
                'format'     => 'skala',
                'skala_maks' => $maks,
                'total'      => $total,
                'persen'     => $persen,
                'jawaban'    => $rekam,
            ]),
        ]);

        $this->_view('publik/hasil_disc', [
            'judul'  => 'Hasil DISC kamu',
            'd'      => $this->hrd->disc($id),
            'profil' => $this->config->item('disc_profil', 'disc_soal'),
        ]);
    }

    /* ==================== FORM BIKINAN HRD (BUILDER) ==================== */

    public function isi($slug)
    {
        $f = $this->hrd->form_slug($slug);
        if (!$f) show_404();
        if ($f['status'] !== 'terbuka') {
            return $this->_view('publik/tertutup', ['judul' => $f['judul'], 'f' => $f]);
        }
        $fields = $this->hrd->field_list($f['id']);

        if ($this->input->method() !== 'post') {
            return $this->_view('publik/isi_form', ['judul' => $f['judul'], 'f' => $f, 'fields' => $fields]);
        }

        $p     = $this->input->post(NULL, TRUE);
        $nama  = trim($p['_nama'] ?? '');
        $email = trim($p['_email'] ?? '');

        if ($f['satu_kali'] && $this->hrd->jawaban_sudah_isi($f['id'], $email)) {
            $this->session->set_flashdata('pesan', 'Email ini sudah pernah mengisi formulir tersebut. Kalau perlu ubah jawaban, hubungi HRD.');
            redirect('f/selesai');
        }

        $isi = [];
        foreach ($fields as $fd) {
            if ($fd['tipe'] === 'judul') continue;
            $key = 'f_' . $fd['id'];
            if ($fd['tipe'] === 'file') {
                $berkas = $this->_upload($key, 'form');
                if ($fd['wajib'] && !$berkas) {
                    $this->session->set_flashdata('gagal', 'Berkas "' . $fd['label'] . '" wajib diunggah.');
                    return $this->_view('publik/isi_form', ['judul' => $f['judul'], 'f' => $f, 'fields' => $fields, 'lama' => $p]);
                }
                $isi[] = ['field_id' => $fd['id'], 'nilai' => null, 'berkas' => $berkas];
                continue;
            }
            $v = $p[$key] ?? '';
            if (is_array($v)) $v = implode(', ', $v);
            $v = trim($v);
            if ($fd['wajib'] && $v === '') {
                $this->session->set_flashdata('gagal', '"' . $fd['label'] . '" wajib diisi.');
                return $this->_view('publik/isi_form', ['judul' => $f['judul'], 'f' => $f, 'fields' => $fields, 'lama' => $p]);
            }
            $isi[] = ['field_id' => $fd['id'], 'nilai' => $v, 'berkas' => null];
        }

        $karyawan = $this->hrd->karyawan_by('email', $email);
        $this->hrd->jawaban_simpan($f['id'], [
            'karyawan_id' => $karyawan['id'] ?? null,
            'nama'  => $nama, 'email' => $email,
            'ip'    => $this->input->ip_address(),
        ], $isi);

        $this->session->set_flashdata('pesan', $f['pesan_selesai']);
        redirect('f/selesai');
    }
}
